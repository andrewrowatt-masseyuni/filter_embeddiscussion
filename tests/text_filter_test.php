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
        $this->assertStringContainsString('data-anonymous="0"', $output);
        $this->assertStringContainsString('data-locked="0"', $output);
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

    public function test_filter_emits_anonymous_and_locked_data_attributes(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $filter = new text_filter($context, []);
        $output = $filter->filter('{embeddeddiscussion:Demo,anonymous,locked}');
        $this->assertStringContainsString('data-thread-name="Demo"', $output);
        $this->assertStringContainsString('data-anonymous="1"', $output);
        $this->assertStringContainsString('data-locked="1"', $output);
    }

    /**
     * Verify keyword/name parsing from the bracketed token body.
     *
     * @dataProvider parse_token_body_provider
     * @param string $body the text inside the braces (after the prefix)
     * @param array $expected expected ['name' => string, 'anonymous' => bool, 'locked' => bool]
     */
    public function test_parse_token_body(string $body, array $expected): void {
        $this->assertSame($expected, text_filter::parse_token_body($body));
    }

    /**
     * Cases for parse_token_body covering keyword combinations and edge cases.
     *
     * @return array
     */
    public static function parse_token_body_provider(): array {
        return [
            'plain name' => [
                'Evaluating Premises - Māramatanga - Understanding',
                ['name' => 'Evaluating Premises - Māramatanga - Understanding', 'anonymous' => false, 'locked' => false],
            ],
            'anon only' => [
                'Evaluating Premises - Māramatanga - Understanding,anonymous',
                ['name' => 'Evaluating Premises - Māramatanga - Understanding', 'anonymous' => true, 'locked' => false],
            ],
            'anon then locked' => [
                'Evaluating Premises - Māramatanga - Understanding,anonymous,locked',
                ['name' => 'Evaluating Premises - Māramatanga - Understanding', 'anonymous' => true, 'locked' => true],
            ],
            'locked then anon' => [
                'Evaluating Premises - Māramatanga - Understanding,locked,anonymous',
                ['name' => 'Evaluating Premises - Māramatanga - Understanding', 'anonymous' => true, 'locked' => true],
            ],
            'name with commas plus keywords' => [
                'Evaluating, understanding, and reviewing premises,anonymous,locked',
                ['name' => 'Evaluating, understanding, and reviewing premises', 'anonymous' => true, 'locked' => true],
            ],
            'name with commas plus short keywords with extra spaces' => [
                'Evaluating, understanding, and reviewing premises, anon  ,  lock',
                ['name' => 'Evaluating, understanding, and reviewing premises', 'anonymous' => true, 'locked' => true],
            ],
            'trailing comma trimmed' => [
                'Evaluating, understanding, and reviewing premises,',
                ['name' => 'Evaluating, understanding, and reviewing premises', 'anonymous' => false, 'locked' => false],
            ],
            'mixed case keywords' => [
                'Demo,LOCKED,Anon',
                ['name' => 'Demo', 'anonymous' => true, 'locked' => true],
            ],
            'short forms only' => [
                'Demo,lock,anon',
                ['name' => 'Demo', 'anonymous' => true, 'locked' => true],
            ],
            'unknown trailing word stays in name' => [
                'Demo, unknown',
                ['name' => 'Demo, unknown', 'anonymous' => false, 'locked' => false],
            ],
        ];
    }
}
