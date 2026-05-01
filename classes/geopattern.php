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
 * Lightweight geopattern-style SVG avatar generator.
 *
 * Produces a deterministic, repeatable SVG pattern for any string seed.
 * Inspired by the GitHub geopattern algorithm.
 *
 * @package    filter_embeddiscussion
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class geopattern {
    /**
     * Generate an SVG data-URI for the given seed.
     *
     * @param string $seed
     * @param int $size pixel size of the square avatar
     * @return string a data: URI suitable for use in an img src
     */
    public static function data_uri(string $seed, int $size = 80): string {
        $svg = self::svg($seed, $size);
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    /**
     * Generate the SVG markup for the given seed.
     *
     * @param string $seed
     * @param int $size
     * @return string
     */
    public static function svg(string $seed, int $size = 80): string {
        $hash = sha1($seed);

        // Hue in degrees 0-359; saturation/lightness pulled from a brand-friendly band.
        $hue = hexdec(substr($hash, 0, 4)) % 360;
        $sat = 35 + (hexdec(substr($hash, 4, 2)) % 25); // 35-60.
        $lit = 35 + (hexdec(substr($hash, 6, 2)) % 15); // 35-50.

        $bg = "hsl({$hue},{$sat}%,{$lit}%)";
        $fglight = sprintf('rgba(255,255,255,%.2f)', 0.18);
        $fgdark = sprintf('rgba(0,0,0,%.2f)', 0.18);

        // 4x4 grid of triangles/squares whose presence is driven by hash bytes.
        $cells = 4;
        $cell = $size / $cells;

        $shapes = '';
        for ($y = 0; $y < $cells; $y++) {
            for ($x = 0; $x < $cells; $x++) {
                $idx = $y * $cells + $x;
                $byte = hexdec($hash[$idx % 40] . $hash[($idx + 1) % 40]);
                $fill = ($byte & 1) ? $fglight : $fgdark;
                $opacity = 0.4 + (($byte >> 1) & 0x1F) / 64.0;

                $cx = $x * $cell;
                $cy = $y * $cell;

                if (($byte >> 6) & 1) {
                    // Triangle.
                    $points = sprintf(
                        '%.2f,%.2f %.2f,%.2f %.2f,%.2f',
                        $cx,
                        $cy + $cell,
                        $cx + $cell / 2,
                        $cy,
                        $cx + $cell,
                        $cy + $cell
                    );
                    $shapes .= sprintf(
                        '<polygon points="%s" fill="%s" fill-opacity="%.2f"/>',
                        $points,
                        $fill,
                        $opacity
                    );
                } else {
                    $shapes .= sprintf(
                        '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="%s" fill-opacity="%.2f"/>',
                        $cx,
                        $cy,
                        $cell,
                        $cell,
                        $fill,
                        $opacity
                    );
                }
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 %1$d %1$d">' .
            '<rect width="%1$d" height="%1$d" fill="%2$s"/>%3$s</svg>',
            $size,
            $bg,
            $shapes
        );
    }
}
