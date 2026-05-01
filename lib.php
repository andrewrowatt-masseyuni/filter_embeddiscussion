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
 * Library functions for filter_embeddiscussion.
 *
 * @package    filter_embeddiscussion
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add a course-level navigation entry when there are threads to manage.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function filter_embeddiscussion_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
) {
    global $DB;

    if (!has_capability('filter/embeddiscussion:managethreads', $context)) {
        return;
    }

    if (!$DB->record_exists('filter_embeddiscussion_thread', ['courseid' => $course->id])) {
        return;
    }

    $url = new moodle_url('/filter/embeddiscussion/index.php', ['courseid' => $course->id]);
    $node = navigation_node::create(
        get_string('threadsincourse', 'filter_embeddiscussion'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'embeddiscussion_threads',
        new pix_icon('i/messages', '')
    );
    $navigation->add_node($node);
}
