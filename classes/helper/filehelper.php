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
 * Open Educational Resources Plugin
 *
 * Single item from Database
 *
 * @package    local_oer
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2017 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/user/lib.php");

/**
 * Class filehelper
 */
class filehelper {
    /**
     * Format an integer to a readable filesize
     *
     * TODO: Decimal point for byte is useless, should this be changed?
     * TODO: Should there be some error handling for zero and negative numbers?
     * (The method works for such integers, but it does not make any sense)
     *
     * @param int $bytes
     * @param bool $short
     * @return string
     */
    public static function get_readable_filesize(int $bytes, bool $short = false): string {
        $unit = [' Byte', ' Kilobyte', ' Megabyte', ' Gigabyte', ' Terabyte'];
        $unitshort = [' b', ' Kb', ' Mb', ' Gb', ' Tb'];
        $divisor = 1000;
        $i = 0;
        while (($bytes / $divisor) > 1 && $i < count($unit) - 1) {
            $bytes = $bytes / $divisor;
            $i++;
        }
        return number_format(round($bytes, 1), 1) . ($short ? $unitshort[$i] : $unit[$i]);
    }

    /**
     * Create a moodle url from a file
     *
     * TODO: Refactor! Remove mixed return type. Return type should be either string or moodle url.
     *
     * @param \stored_file $file
     * @param bool $string
     * @return \moodle_url|string
     */
    public static function get_file_url(\stored_file $file, bool $string = false) {
        $url = \moodle_url::make_pluginfile_url($file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename());
        return $string ? $url->out() : $url;
    }
}
