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
 * Replaces tokens of the form {embeddeddiscussion:Name of thread[,keyword...]}
 * with a skeleton container that the JS module populates asynchronously.
 *
 * Optional trailing keywords (case-insensitive, any order):
 *   - lock | locked     - the thread is locked (no new posts or edits).
 *   - anon | anonymous  - student posts are shown with anonymous handles.
 *
 * Legacy syntaxes (drop-in replacement for filter_disqus and the {comments} block) are
 * also recognised and rewritten to the canonical token before processing:
 *   - [[filter_disqus]]                 -> {embeddiscussion:<page name>}
 *   - [[filter_disqus:<url_segment>]]   -> {embeddiscussion:<page name> (<url_segment>)}
 *   - {comments}                        -> {embeddiscussion:<page name>}
 * where <page name> is the current $PAGE->title with any trailing
 * " | <site fullname>" or " | <site shortname>" segment stripped off.
 *
 * @package    filter_embeddiscussion
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    /** Pattern matches both {embeddeddiscussion:..} and {embeddiscussion:..}. */
    const PATTERN = '/\{embedd(?:eddi|i)scussion:([^}]+)\}/i';

    /** Pattern matches the legacy [[filter_disqus]] / [[filter_disqus:segment]] / {comments} tokens. */
    const LEGACY_PATTERN = '/\[\[filter_disqus(?::([^\]]*))?\]\]|\{comments\}/i';

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
        global $PAGE, $OUTPUT;

        if (!\is_string($text)) {
            return $text;
        }

        // Rewrite any legacy filter_disqus / {comments} tokens to the canonical token first.
        if (preg_match(self::LEGACY_PATTERN, $text)) {
            $text = self::convert_legacy_tokens($text);
        }

        if (\strpos($text, 'iscussion:') === false) {
            return $text;
        }

        if (!preg_match(self::PATTERN, $text)) {
            return $text;
        }

        $context = $this->context;

        $text = preg_replace_callback(self::PATTERN, function ($matches) use ($context, $OUTPUT) {
            $parsed = self::parse_token_body($matches[1]);
            if ($parsed['name'] === '') {
                return $matches[0];
            }
            // Resolve the thread server-side so the browser only learns the thread id.
            // anonymous/locked are token-authored settings — never trust them from the client.
            try {
                $thread = manager::get_or_create_thread($parsed['name'], $context);
                $thread = manager::sync_settings_from_token($thread, [
                    'anonymous' => $parsed['anonymous'],
                    'locked' => $parsed['locked'],
                ]);
            } catch (\Throwable $e) {
                return $matches[0];
            }
            return $OUTPUT->render_from_template('filter_embeddiscussion/placeholder', [
                'uid' => uniqid('embeddisc_', true),
                'threadid' => (int)$thread->id,
                'contextid' => $context->id,
            ]);
        }, $text);

        // Request the JS bootstrapper once per page.
        if (!self::$requirementsdone) {
            self::$requirementsdone = true;
            $PAGE->requires->js_call_amd('filter_embeddiscussion/discussion', 'init');
        }

        return $text;
    }

    /**
     * Parse the body of an embeddiscussion token into a name and trailing keywords.
     *
     * The body is split on commas. Trailing parts that match a recognised keyword
     * (lock/locked/anon/anonymous, case-insensitive) or are empty are stripped
     * from the right; the remaining parts are rejoined with ", " to form the name.
     * Spaces around delimiters are trimmed; keyword order is unimportant.
     *
     * @param string $body the text between "embeddeddiscussion:" and "}"
     * @return array{name: string, anonymous: bool, locked: bool}
     */
    public static function parse_token_body(string $body): array {
        $anonymous = false;
        $locked = false;

        if (strpos($body, ',') === false) {
            return ['name' => trim($body), 'anonymous' => false, 'locked' => false];
        }

        $parts = array_map('trim', explode(',', $body));

        // Strip trailing empties and recognised keywords from the right.
        while (!empty($parts)) {
            $tail = end($parts);
            if ($tail === '') {
                array_pop($parts);
                continue;
            }
            $key = strtolower($tail);
            if ($key === 'lock' || $key === 'locked') {
                $locked = true;
                array_pop($parts);
                continue;
            }
            if ($key === 'anon' || $key === 'anonymous') {
                $anonymous = true;
                array_pop($parts);
                continue;
            }
            break;
        }

        $name = trim(implode(', ', $parts));
        return ['name' => $name, 'anonymous' => $anonymous, 'locked' => $locked];
    }

    /**
     * Rewrite legacy filter_disqus / {comments} tokens in text to the canonical
     * {embeddiscussion:...} token, deriving the thread name from $PAGE->title.
     *
     * If the page title is unavailable (for example, when the filter runs in a
     * context without a fully-initialised page), the legacy tokens are left
     * untouched so that the original text is preserved.
     *
     * @param string $text the text being filtered
     * @return string the text with any legacy tokens rewritten
     */
    public static function convert_legacy_tokens(string $text): string {
        global $PAGE, $SITE;

        $pagetitle = '';
        $sitenames = [];

        if (isset($PAGE) && is_object($PAGE)) {
            $pagetitle = (string) ($PAGE->title ?? '');
        }
        if (isset($SITE) && is_object($SITE)) {
            $sitenames[] = (string) ($SITE->fullname ?? '');
            $sitenames[] = (string) ($SITE->shortname ?? '');
        }

        $pagename = self::derive_page_name($pagetitle, $sitenames);
        if ($pagename === '') {
            return $text;
        }

        $text = preg_replace_callback(
            '/\[\[filter_disqus(?::([^\]]*))?\]\]/i',
            function ($matches) use ($pagename) {
                $segment = isset($matches[1]) ? trim($matches[1]) : '';
                $threadname = $segment !== '' ? $pagename . ' (' . $segment . ')' : $pagename;
                return '{embeddiscussion:' . self::sanitise_thread_name($threadname) . '}';
            },
            $text
        );

        $text = preg_replace_callback(
            '/\{comments\}/i',
            function () use ($pagename) {
                return '{embeddiscussion:' . self::sanitise_thread_name($pagename) . '}';
            },
            $text
        );

        return $text;
    }

    /**
     * Strip the trailing site fullname or shortname segment from a page title.
     *
     * Moodle pages are titled "<page name> | <site name>" (see
     * moodle_page::TITLE_SEPARATOR), so this removes the trailing
     * " | <site fullname>" or " | <site shortname>" segment if present and
     * returns the leading portion. If neither matches, the trimmed title is
     * returned unchanged.
     *
     * @param string $pagetitle the raw $PAGE->title value
     * @param array $sitenames candidate site name strings (fullname, shortname)
     * @return string the page name with any site name suffix removed
     */
    public static function derive_page_name(string $pagetitle, array $sitenames): string {
        $pagetitle = trim($pagetitle);
        if ($pagetitle === '') {
            return '';
        }

        $separator = ' | ';
        foreach ($sitenames as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $suffix = $separator . $name;
            $suffixlen = strlen($suffix);
            if (strlen($pagetitle) > $suffixlen && substr($pagetitle, -$suffixlen) === $suffix) {
                return trim(substr($pagetitle, 0, -$suffixlen));
            }
        }

        return $pagetitle;
    }

    /**
     * Remove characters that would prematurely terminate the canonical token or
     * be misinterpreted by parse_token_body() when a thread name is composed
     * from a page title.
     *
     * @param string $name the thread name being built from page metadata
     * @return string the sanitised thread name
     */
    protected static function sanitise_thread_name(string $name): string {
        // The canonical token ends at the first '}' so strip any literal closing brace.
        // Commas would otherwise cause parse_token_body() to look for trailing keywords.
        return trim(str_replace(['}', ','], ['', ' '], $name));
    }
}
