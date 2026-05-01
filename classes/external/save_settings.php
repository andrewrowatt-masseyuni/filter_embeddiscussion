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

namespace filter_embeddiscussion\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use filter_embeddiscussion\manager;

/**
 * Update thread admin settings (anonymous, locked).
 *
 * @package    filter_embeddiscussion
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_settings extends external_api {
    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'threadid' => new external_value(PARAM_INT, 'Thread id'),
            'contextid' => new external_value(PARAM_INT, 'Context id'),
            'anonymous' => new external_value(PARAM_BOOL, 'Anonymous mode flag'),
            'locked' => new external_value(PARAM_BOOL, 'Locked flag'),
        ]);
    }

    /**
     * Save settings.
     *
     * @param int $threadid
     * @param int $contextid
     * @param bool $anonymous
     * @param bool $locked
     * @return array
     */
    public static function execute(int $threadid, int $contextid, bool $anonymous, bool $locked): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'threadid' => $threadid,
            'contextid' => $contextid,
            'anonymous' => $anonymous,
            'locked' => $locked,
        ]);

        $context = \context::instance_by_id($params['contextid']);
        self::validate_context($context);

        $thread = $DB->get_record(
            'filter_embeddiscussion_thread',
            ['id' => $params['threadid']],
            '*',
            MUST_EXIST
        );

        $thread = manager::update_settings($thread, $context, [
            'anonymous' => $params['anonymous'],
            'locked' => $params['locked'],
        ]);

        return manager::get_thread_view($thread, $context);
    }

    /**
     * Return value definition.
     *
     * @return \core_external\external_description
     */
    public static function execute_returns() {
        return helper::thread_structure();
    }
}
