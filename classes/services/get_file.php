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
 * Class get_file
 *
 * Ajax service to load basic information of all files from a course.
 */
class get_file extends \external_api {
    /**
     * Defines the names and types of the incoming parameters from ajax call.
     *
     * @return \external_function_parameters
     */
    public static function service_parameters() {
        return new \external_function_parameters(
                [
                        'courseid' => new \external_value(PARAM_INT, 'Moodle course id', VALUE_REQUIRED),
                        'identifier' => new \external_value(PARAM_TEXT, 'Moodle course id', VALUE_REQUIRED),
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
                        'file' => self::external_file_return_value(),
                ]);
    }

    /**
     * Ajax function to call.
     * Returns information the frontend needs to display one file.
     *
     * @param int $courseid
     * @param string $contenthash
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function service(int $courseid, string $contenthash) {
        if (empty($contenthash)) {
            throw new \moodle_exception('Contenthash cannot be empty for single file aquirement.');
        }
        $file = filelist::get_simple_file($courseid, $contenthash);
        $context = \context_course::instance($courseid);
        return [
                'courseid' => $courseid,
                'context' => $context->id,
                'file' => $file,
        ];
    }

    /**
     * Shared function for get_files and get_file.
     * Defines the names and types of the return values for one file.
     * Called in service_returns() method of both services.
     *
     * @return \external_single_structure
     */
    public static function external_file_return_value() {
        return new \external_single_structure(
                [
                        'id' => new \external_value(PARAM_INT, 'DB id of oer file entry'),
                        'contenthash' => new \external_value(PARAM_ALPHANUM, 'Contenthash of file'),
                        'identifier' => new \external_value(PARAM_TEXT, 'Unique identifier for element'),
                        'idhash' => new \external_value(PARAM_ALPHANUM, 'SHA1 hash of identifier for html ids'),
                        'title' => new \external_value(PARAM_TEXT, 'Title or filename'),
                        'mimetype' => new \external_value(PARAM_TEXT, 'Mimetype'),
                        'icon' => new \external_value(PARAM_RAW, 'File icon'),
                        'timemodified' => new \external_value(PARAM_TEXT, 'Readable time'),
                        'timeuploaded' => new \external_value(PARAM_TEXT, 'Uploaded time'),
                        'timeuploadedts' => new \external_value(PARAM_INT, 'Uploaded timestamp'),
                        'upload' => new \external_value(PARAM_BOOL, 'File ready for upload'),
                        'ignore' => new \external_value(PARAM_BOOL, 'File ignored'),
                        'deleted' => new \external_value(PARAM_BOOL,
                                'File deleted, orphaned metadata'),
                        'information' => new \external_multiple_structure(
                                new \external_single_structure(
                                        [
                                                'area' => new \external_value(PARAM_TEXT,
                                                        'Area of the information'),
                                                'fields' => new \external_multiple_structure(
                                                        new \external_single_structure(
                                                                [
                                                                        'infoname' => new \external_value(PARAM_TEXT,
                                                                                'Name of information'),
                                                                        'infourl' => new \external_value(PARAM_URL,
                                                                                'Url to information'),
                                                                        'infohasurl' => new \external_value(PARAM_BOOL,
                                                                                'Boolean if url is available'),
                                                                        'last' => new \external_value(PARAM_BOOL,
                                                                                'Last element in array, ' .
                                                                                'relevant for comma in mustache'),
                                                                ]
                                                        )),
                                        ]
                                )),
                        'requirementsmet' => new \external_value(PARAM_BOOL,
                                'Boolean if all requirements for release are fulfilled'),
                        'state' => new \external_value(PARAM_INT, 'State of file as defined in filestate class.'),
                        'multiple' => new \external_value(PARAM_BOOL, 'File is used in multiple courses'),
                        'editor' => new \external_value(PARAM_INT, 'Courseid where file is edited.'),
                        'courses' => new \external_multiple_structure(
                                new \external_single_structure(
                                        [
                                                'id' => new \external_value(PARAM_INT, 'Id of course where file is used'),
                                                'name' => new \external_value(PARAM_TEXT,
                                                        'Name of course where file is used'),
                                                'editor' => new \external_value(PARAM_BOOL,
                                                        'True if the file is edited in this course'),
                                        ]
                                )
                        ),
                        'writable' => new \external_value(PARAM_BOOL, 'The metadata is writable in the current context'),
                        'coursetofile' => new \external_value(PARAM_BOOL, 'Setting is activated and this course is the editor'),
                        'wwwroot' => new \external_value(PARAM_URL, 'wwwroot of moodle'),
                        'origins' => new \external_multiple_structure(
                                new \external_single_structure(
                                        [
                                                'origin' => new \external_value(PARAM_ALPHANUMEXT, 'Origin of element'),
                                                'originname' => new \external_value(PARAM_TEXT, 'Language string of origin'),
                                        ]
                                )
                        ),
                ]);
    }
}
