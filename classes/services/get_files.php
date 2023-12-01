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
 * Ajax functions to load files and metadata for frontend
 *
 * @package    local_oer
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\services;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");

use local_oer\filelist;

/**
 * Class get_files
 *
 * Ajax service to load basic information of all files from a course.
 */
class get_files extends \external_api {
    /**
     * Defines the names and types of the incoming parameters from ajax call.
     *
     * @return \external_function_parameters
     */
    public static function service_parameters() {
        return new \external_function_parameters(
                [
                        'courseid' => new \external_value(PARAM_INT, 'Moodle course id', VALUE_REQUIRED),
                ]
        );
    }

    /**
     * Defines the structure, names and types of the return values.
     *
     * @return \external_single_structure
     */
    public static function service_returns() {
        return new \external_single_structure(
                [
                        'courseid' => new \external_value(PARAM_INT, 'Moodle courseid'),
                        'context' => new \external_value(PARAM_INT, 'Moodle course context id'),
                        'origin' => new \external_multiple_structure(
                                new \external_single_structure(
                                        [
                                                'origin' => new \external_value(PARAM_ALPHANUMEXT, 'Shortname of origin'),
                                                'originname' => new \external_value(PARAM_TEXT, 'Language string of origin'),
                                        ]
                                )),
                        'files' => new \external_multiple_structure(get_file::external_file_return_value()),
                ]);
    }

    /**
     * Ajax function to call.
     *
     * Returns information the frontend needs to display all files of one course.
     *
     * @param int $courseid Moodle courseid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function service(int $courseid) {
        [$files, $origin] = filelist::get_simple_filelist($courseid);
        $context = \context_course::instance($courseid);
        return [
                'courseid' => $courseid,
                'context' => $context->id,
                'origin' => $origin,
                'files' => $files,
        ];
    }
}
