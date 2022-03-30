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
                        'courseid'    => new \external_value(PARAM_INT, 'Moodle course id', VALUE_REQUIRED),
                        'contenthash' => new \external_value(PARAM_ALPHANUM, 'Moodle course id', VALUE_REQUIRED),
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
                        'context'  => new \external_value(PARAM_INT, 'Moodle course context id'),
                        'file'     => self::external_file_return_value()
                ]);
    }

    /**
     * Ajax function to call.
     * Returns all informations the frontend needs to display one file.
     *
     * @param int    $courseid
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
        $file    = filelist::get_simple_file($courseid, $contenthash);
        $context = \context_course::instance($courseid);
        return [
                'courseid' => $courseid,
                'context'  => $context->id,
                'file'     => $file,
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
                        'id'             => new \external_value(PARAM_INT, 'DB id of oer file entry'),
                        'contenthash'    => new \external_value(PARAM_ALPHANUM, 'Contenthash of file'),
                        'title'          => new \external_value(PARAM_TEXT, 'Title or filename'),
                        'mimetype'       => new \external_value(PARAM_TEXT, 'Mimetype'),
                        'icon'           => new \external_value(PARAM_RAW, 'File icon'),
                        'icontype'       => new \external_value(PARAM_ALPHA, 'File icon type'),
                        'iconisimage'    => new \external_value(PARAM_BOOL, 'Bool if icon is image'),
                        'timemodified'   => new \external_value(PARAM_TEXT, 'Readable time'),
                        'timeuploaded'   => new \external_value(PARAM_TEXT, 'Uploaded time'),
                        'timeuploadedts' => new \external_value(PARAM_INT, 'Uploaded timestamp'),
                        'upload'         => new \external_value(PARAM_BOOL, 'File ready for upload'),
                        'ignore'         => new \external_value(PARAM_BOOL, 'File ignored'),
                        'licensecorrect' => new \external_value(PARAM_BOOL, 'File uses CC license type'),
                        'license'        => new \external_value(PARAM_TEXT, 'Fullname of license'),
                        'personmissing'  => new \external_value(PARAM_BOOL, 'No persons defined'),
                        'contextset'     => new \external_value(PARAM_BOOL, 'Context has been selected'),
                        'deleted'        => new \external_value(PARAM_BOOL,
                                                                'File deleted, orphaned metadata'),
                        'modules'        => new \external_multiple_structure(
                                new \external_single_structure(
                                        [
                                                'moduleurl'  => new \external_value(PARAM_URL,
                                                                                    'Url to moodle activity'),
                                                'modulename' => new \external_value(PARAM_TEXT,
                                                                                    'Name of course module'),
                                        ]
                                )),
                        'sections'       => new \external_multiple_structure(
                                new \external_single_structure(
                                        [
                                                'sectionnum'  => new \external_value(PARAM_INT,
                                                                                     'Number of section in course'),
                                                'sectionname' => new \external_value(PARAM_TEXT,
                                                                                     'Name of section in course'),
                                        ]
                                )),

                ]);
    }
}
