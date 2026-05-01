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

namespace filter_embeddiscussion;

/**
 * embeddiscussion filter
 *
 * Replaces tokens of the form {embeddeddiscussion:Name of thread} with a
 * skeleton container that the JS module populates asynchronously.
 *
 * @package    filter_embeddiscussion
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    /** Pattern matches both {embeddeddiscussion:..} and {embeddiscussion:..}. */
    const PATTERN = '/\{embedd(?:eddi|i)scussion:([^}]+)\}/i';

    /** @var bool Module/page resources requested. */
    protected static $requirementsdone = false;

    /**
     * Filter text replacing the token with a skeleton container.
     *
     * @param string $text some HTML content to process.
     * @param array $options options passed to the filters
     * @return string the HTML content after the filtering has been applied.
     */
    public function filter($text, array $options = []) {
        global $PAGE;

        if (!\is_string($text) || \strpos($text, 'iscussion:') === false) {
            return $text;
        }

        if (!preg_match(self::PATTERN, $text)) {
            return $text;
        }

        $contextid = $this->context->id;

        $text = preg_replace_callback(self::PATTERN, function ($matches) use ($contextid) {
            $name = trim($matches[1]);
            if ($name === '') {
                return $matches[0];
            }
            $uid = uniqid('embeddisc_', true);
            $attrs = [
                'class' => 'filter-embeddiscussion',
                'data-region' => 'filter-embeddiscussion',
                'id' => $uid,
                'data-thread-name' => $name,
                'data-contextid' => $contextid,
            ];
            $skeleton = \html_writer::tag(
                'div',
                \html_writer::tag('div', '', ['class' => 'embeddisc-skeleton-line embeddisc-skeleton-title']) .
                \html_writer::tag('div', '', ['class' => 'embeddisc-skeleton-line']) .
                \html_writer::tag('div', '', ['class' => 'embeddisc-skeleton-line']) .
                \html_writer::tag(
                    'div',
                    get_string('threadnotinitialised', 'filter_embeddiscussion'),
                    ['class' => 'sr-only']
                ),
                ['class' => 'embeddisc-skeleton', 'aria-hidden' => 'true']
            );
            return \html_writer::tag('div', $skeleton, $attrs);
        }, $text);

        // Request the JS bootstrapper once per page.
        if (!self::$requirementsdone) {
            self::$requirementsdone = true;
            $PAGE->requires->js_call_amd('filter_embeddiscussion/discussion', 'init');
        }

        return $text;
    }
}
