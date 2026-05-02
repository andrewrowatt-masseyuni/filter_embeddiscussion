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
 * Initialise (if necessary) and return an embedded discussion thread.
 *
 * @package    filter_embeddiscussion
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_thread extends external_api {
    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_TEXT, 'Thread name as written in the filter token'),
            'contextid' => new external_value(PARAM_INT, 'Context id where the filter is applied'),
            'anonymous' => new external_value(
                PARAM_BOOL,
                'Anonymous keyword present on the filter token',
                VALUE_DEFAULT,
                false
            ),
            'locked' => new external_value(
                PARAM_BOOL,
                'Locked keyword present on the filter token',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

    /**
     * Initialise / return a thread for the current viewer, syncing
     * the thread's anonymous/locked flags to the values on the filter token.
     *
     * @param string $name
     * @param int $contextid
     * @param bool $anonymous
     * @param bool $locked
     * @return array
     */
    public static function execute(string $name, int $contextid, bool $anonymous = false, bool $locked = false): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'contextid' => $contextid,
            'anonymous' => $anonymous,
            'locked' => $locked,
        ]);

        $context = \context::instance_by_id($params['contextid']);
        self::validate_context($context);

        $thread = manager::get_or_create_thread($params['name'], $context);
        $thread = manager::sync_settings_from_token($thread, [
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
