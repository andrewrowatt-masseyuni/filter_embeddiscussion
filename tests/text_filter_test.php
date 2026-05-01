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
 * Tests for the text filter.
 *
 * @package    filter_embeddiscussion
 * @category   test
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \filter_embeddiscussion\text_filter
 */
final class text_filter_test extends \advanced_testcase {
    public function test_filter_no_token_passes_through(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $filter = new text_filter($context, []);
        $input = '<p>Hello world</p>';
        $this->assertSame($input, $filter->filter($input));
    }

    public function test_filter_replaces_token_with_skeleton(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $filter = new text_filter($context, []);
        $output = $filter->filter('Before {embeddeddiscussion:My thread} after');
        $this->assertStringContainsString('data-region="filter-embeddiscussion"', $output);
        $this->assertStringContainsString('data-thread-name="My thread"', $output);
        $this->assertStringContainsString('Before', $output);
        $this->assertStringContainsString('after', $output);
    }

    public function test_filter_supports_alternate_spelling(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $filter = new text_filter($context, []);
        $output = $filter->filter('{embeddiscussion:Alt}');
        $this->assertStringContainsString('data-thread-name="Alt"', $output);
    }

    public function test_filter_handles_multiple_tokens(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $filter = new text_filter($context, []);
        $output = $filter->filter('{embeddeddiscussion:A} mid {embeddeddiscussion:B}');
        $this->assertSame(2, substr_count($output, 'data-region="filter-embeddiscussion"'));
        $this->assertStringContainsString('data-thread-name="A"', $output);
        $this->assertStringContainsString('data-thread-name="B"', $output);
    }

    public function test_filter_ignores_empty_name(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $filter = new text_filter($context, []);
        $input = '{embeddeddiscussion:}';
        $this->assertSame($input, $filter->filter($input));
    }
}
