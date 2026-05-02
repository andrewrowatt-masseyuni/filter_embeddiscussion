<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Restore definition for filter_embeddiscussion.
 *
 * @package    filter_embeddiscussion
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores embedded discussion threads, posts, votes and anonymous handles.
 *
 * Threads are re-anchored on the destination course context (or the closest
 * mapped context where one exists). Duplicate (namehash, contextid) threads
 * are reused rather than re-inserted so that re-restoring into the same site
 * does not break the unique index.
 */
class restore_filter_embeddiscussion_plugin extends restore_filter_plugin {
    /**
     * Old post id => old parent post id, deferred until all posts inserted.
     *
     * @var array<int,int>
     */
    private $pendingparents = [];

    /**
     * New thread id => old context id, deferred until activities are restored.
     *
     * Course-level plugin restore runs before activities, so module contexts
     * are not yet in the mapping table. We re-anchor in after_restore_course().
     *
     * @var array<int,int>
     */
    private $pendingthreadcontexts = [];

    /**
     * Define paths under the course element.
     *
     * @return array
     */
    protected function define_course_plugin_structure() {
        return [
            new restore_path_element(
                'embeddiscussion_thread',
                $this->get_pathfor('/embeddiscussion_threads/embeddiscussion_thread')
            ),
            new restore_path_element(
                'embeddiscussion_post',
                $this->get_pathfor(
                    '/embeddiscussion_threads/embeddiscussion_thread' .
                    '/embeddiscussion_posts/embeddiscussion_post'
                )
            ),
            new restore_path_element(
                'embeddiscussion_vote',
                $this->get_pathfor(
                    '/embeddiscussion_threads/embeddiscussion_thread' .
                    '/embeddiscussion_posts/embeddiscussion_post' .
                    '/embeddiscussion_votes/embeddiscussion_vote'
                )
            ),
            new restore_path_element(
                'embeddiscussion_handle',
                $this->get_pathfor(
                    '/embeddiscussion_threads/embeddiscussion_thread' .
                    '/embeddiscussion_handles/embeddiscussion_handle'
                )
            ),
        ];
    }

    /**
     * Process a thread.
     *
     * @param array|object $data
     * @return void
     */
    public function process_embeddiscussion_thread($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $oldcontextid = (int)$data->contextid;

        // Course plugin restore runs before activities, so module context
        // mappings may not yet exist. Insert with the course context as a
        // placeholder and re-anchor in after_restore_course().
        $newcontextid = $this->get_mappingid('context', $oldcontextid);
        $deferred = false;
        if (!$newcontextid) {
            $newcontextid = \context_course::instance($this->task->get_courseid())->id;
            $deferred = true;
        }

        $data->contextid = (int)$newcontextid;
        $data->courseid = (int)$this->task->get_courseid();

        $existing = $DB->get_record('filter_embeddiscussion_thread', [
            'namehash' => $data->namehash,
            'contextid' => $data->contextid,
        ]);
        if ($existing) {
            $newid = (int)$existing->id;
        } else {
            unset($data->id);
            $newid = $DB->insert_record('filter_embeddiscussion_thread', $data);
        }

        $this->set_mapping('embeddiscussion_thread', $oldid, $newid);

        if ($deferred) {
            $this->pendingthreadcontexts[$newid] = $oldcontextid;
        }
    }

    /**
     * Process a post. Parent post mapping is deferred to after_execute_course.
     *
     * @param array|object $data
     * @return void
     */
    public function process_embeddiscussion_post($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $oldparent = (int)$data->parentid;

        $data->threadid = $this->get_new_parentid('embeddiscussion_thread');
        $data->userid = $this->get_mappingid('user', (int)$data->userid) ?: (int)$data->userid;
        // Temporarily clear parent. Re-mapped in after_execute_course().
        $data->parentid = 0;

        unset($data->id);
        $newid = $DB->insert_record('filter_embeddiscussion_post', $data);
        $this->set_mapping('embeddiscussion_post', $oldid, $newid);

        if ($oldparent > 0) {
            $this->pendingparents[$oldid] = $oldparent;
        }
    }

    /**
     * Process a vote.
     *
     * @param array|object $data
     * @return void
     */
    public function process_embeddiscussion_vote($data) {
        global $DB;

        $data = (object)$data;
        $data->postid = $this->get_new_parentid('embeddiscussion_post');
        $data->userid = $this->get_mappingid('user', (int)$data->userid) ?: (int)$data->userid;

        if (
            $DB->record_exists('filter_embeddiscussion_vote', [
            'postid' => $data->postid,
            'userid' => $data->userid,
            ])
        ) {
            return;
        }

        unset($data->id);
        $DB->insert_record('filter_embeddiscussion_vote', $data);
    }

    /**
     * Process an anonymous handle assignment.
     *
     * @param array|object $data
     * @return void
     */
    public function process_embeddiscussion_handle($data) {
        global $DB;

        $data = (object)$data;
        $data->threadid = $this->get_new_parentid('embeddiscussion_thread');
        $data->userid = $this->get_mappingid('user', (int)$data->userid) ?: (int)$data->userid;

        if (
            $DB->record_exists('filter_embeddiscussion_handle', [
            'threadid' => $data->threadid,
            'userid' => $data->userid,
            ])
        ) {
            return;
        }

        unset($data->id);
        $DB->insert_record('filter_embeddiscussion_handle', $data);
    }

    /**
     * Re-link reply parents now that all posts have new ids.
     *
     * @return void
     */
    protected function after_execute_course() {
        global $DB;

        foreach ($this->pendingparents as $oldpostid => $oldparentid) {
            $newpostid = $this->get_mappingid('embeddiscussion_post', $oldpostid);
            $newparentid = $this->get_mappingid('embeddiscussion_post', $oldparentid);
            if ($newpostid && $newparentid) {
                $DB->set_field('filter_embeddiscussion_post', 'parentid', $newparentid, ['id' => $newpostid]);
            }
        }
        $this->pendingparents = [];
    }

    /**
     * Re-anchor threads on their activity contexts once activities have been
     * restored and their context mappings are available.
     *
     * @return void
     */
    public function after_restore_course() {
        global $DB;

        foreach ($this->pendingthreadcontexts as $newthreadid => $oldcontextid) {
            $newcontextid = $this->get_mappingid('context', $oldcontextid);
            if (!$newcontextid) {
                continue;
            }
            $thread = $DB->get_record('filter_embeddiscussion_thread', ['id' => $newthreadid]);
            if (!$thread || (int)$thread->contextid === (int)$newcontextid) {
                continue;
            }
            // If a thread with the same name already exists at the activity
            // context, redirect this thread's posts/handles into it and drop
            // the placeholder rather than violating the unique index.
            $duplicate = $DB->get_record('filter_embeddiscussion_thread', [
                'namehash' => $thread->namehash,
                'contextid' => $newcontextid,
            ]);
            if ($duplicate) {
                $DB->set_field(
                    'filter_embeddiscussion_post',
                    'threadid',
                    $duplicate->id,
                    ['threadid' => $newthreadid]
                );
                $DB->set_field(
                    'filter_embeddiscussion_handle',
                    'threadid',
                    $duplicate->id,
                    ['threadid' => $newthreadid]
                );
                $DB->delete_records('filter_embeddiscussion_thread', ['id' => $newthreadid]);
            } else {
                $DB->set_field(
                    'filter_embeddiscussion_thread',
                    'contextid',
                    (int)$newcontextid,
                    ['id' => $newthreadid]
                );
            }
        }
        $this->pendingthreadcontexts = [];
    }
}
