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
 * Backup definition for filter_embeddiscussion.
 *
 * @package    filter_embeddiscussion
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backs up embedded discussion threads, posts, votes and anonymous handles
 * attached to a course.
 */
class backup_filter_embeddiscussion_plugin extends backup_filter_plugin {

    /**
     * Course-level backup structure.
     *
     * @return void
     */
    protected function define_course_plugin_structure() {
        $userinfo = $this->get_setting_value('users');

        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());

        $threads = new backup_nested_element('embeddiscussion_threads');
        $thread = new backup_nested_element('embeddiscussion_thread', ['id'], [
            'name', 'namehash', 'contextid', 'courseid',
            'anonymous', 'locked', 'handleoffset',
            'timecreated', 'timemodified',
        ]);

        $posts = new backup_nested_element('embeddiscussion_posts');
        $post = new backup_nested_element('embeddiscussion_post', ['id'], [
            'parentid', 'userid', 'content',
            'edited', 'deleted', 'timecreated', 'timemodified',
        ]);

        $votes = new backup_nested_element('embeddiscussion_votes');
        $vote = new backup_nested_element('embeddiscussion_vote', ['id'], [
            'userid', 'vote', 'timecreated',
        ]);

        $handles = new backup_nested_element('embeddiscussion_handles');
        $handle = new backup_nested_element('embeddiscussion_handle', ['id'], [
            'userid', 'handleindex', 'timecreated',
        ]);

        $plugin->add_child($wrapper);
        $wrapper->add_child($threads);
        $threads->add_child($thread);
        $thread->add_child($posts);
        $posts->add_child($post);
        $post->add_child($votes);
        $votes->add_child($vote);
        $thread->add_child($handles);
        $handles->add_child($handle);

        $thread->set_source_table(
            'filter_embeddiscussion_thread',
            ['courseid' => backup::VAR_COURSEID]
        );

        // Posts, votes and anonymous handles are user-generated content; only
        // include them when the backup is configured to capture user data.
        if ($userinfo) {
            $post->set_source_table(
                'filter_embeddiscussion_post',
                ['threadid' => backup::VAR_PARENTID]
            );
            $vote->set_source_table(
                'filter_embeddiscussion_vote',
                ['postid' => backup::VAR_PARENTID]
            );
            $handle->set_source_table(
                'filter_embeddiscussion_handle',
                ['threadid' => backup::VAR_PARENTID]
            );

            $post->annotate_ids('user', 'userid');
            $vote->annotate_ids('user', 'userid');
            $handle->annotate_ids('user', 'userid');
        }
    }
}
