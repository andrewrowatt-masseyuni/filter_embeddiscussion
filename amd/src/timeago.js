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
 * Light-weight relative time formatter and ticker.
 *
 * @module     filter_embeddiscussion/timeago
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SECOND = 1;
const MINUTE = 60;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;
const MONTH = 30 * DAY;
const YEAR = 365 * DAY;

let started = false;

/**
 * Format an age in seconds as a short relative string.
 * Coarse, uses Intl.RelativeTimeFormat for locale awareness when available.
 *
 * @param {number} ageSeconds difference between now and timestamp, in seconds
 * @return {string}
 */
export const format = (ageSeconds) => {
    const age = Math.max(0, Math.floor(ageSeconds));
    if (age < 30 * SECOND) {
        return 'a few seconds ago';
    }
    const rtf = (typeof Intl !== 'undefined' && Intl.RelativeTimeFormat)
        ? new Intl.RelativeTimeFormat(undefined, {numeric: 'auto'})
        : null;
    const fmt = (n, unit) => rtf ? rtf.format(-n, unit) : `${n} ${unit}${n === 1 ? '' : 's'} ago`;
    if (age < MINUTE) {
        return fmt(age, 'second');
    }
    if (age < HOUR) {
        return fmt(Math.floor(age / MINUTE), 'minute');
    }
    if (age < DAY) {
        return fmt(Math.floor(age / HOUR), 'hour');
    }
    if (age < MONTH) {
        return fmt(Math.floor(age / DAY), 'day');
    }
    if (age < YEAR) {
        return fmt(Math.floor(age / MONTH), 'month');
    }
    return fmt(Math.floor(age / YEAR), 'year');
};

/**
 * Refresh all visible relative-time stamps once.
 */
const tick = () => {
    const now = Date.now() / 1000;
    document.querySelectorAll('[data-region="time-ago"][data-timestamp]').forEach(el => {
        const ts = parseInt(el.dataset.timestamp, 10);
        if (Number.isNaN(ts)) {
            return;
        }
        el.textContent = format(now - ts);
    });
};

/**
 * Start the page-wide ticker if not already running. Refreshes every 30 seconds.
 */
export const startTicker = () => {
    if (started) {
        return;
    }
    started = true;
    setInterval(tick, 30 * 1000);
};
