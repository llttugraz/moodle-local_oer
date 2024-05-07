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
 * @package    local_oer
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

/**
 * Class testcourse
 *
 * Helper class for unit tests to prepare a course with modules and files for oer use cases.
 */
class fromform {
    /**
     * The format of the returned array is similar to the form submit from frontend.
     *
     * @param int $courseid
     * @param string $identifier
     * @param string $title
     * @param string $description
     * @param int $context
     * @param string $license
     * @param string $language
     * @param int $resourcetype
     * @param array $persons every array entry is a JSON string with format:
     *                        {"role":"Author","firstname":"Christian","lastname":"Ortner"}
     * @param int $upload
     * @param int $ignore
     * @param array $tags Tags in array format ['tag a','tag b'].
     * @return array
     * @throws \Exception
     */
    public static function fileinfoform_submit(int $courseid, string $identifier, string $title, string $description,
            int $context, string $license, string $language, int $resourcetype, array $persons,
            int $upload, int $ignore, array $tags = []) {
        if ($upload == 1 && $ignore == 1) {
            throw new \Exception('Upload and ignore cannot be set at the same time.');
        }
        $retval = [];
        $retval['courseid'] = "$courseid";
        $retval['identifier'] = $identifier;
        $retval['storedperson'] = '{"persons":[' . implode(',', $persons) . ']}';
        $retval['storedtags'] = implode(',', $tags);
        $retval['sesskey'] = "sess" . rand(100, 100000);
        $retval['_qf_local_oer_forms_fileinfo_form'] = "1";
        $retval['title'] = $title;
        $retval['description'] = $description;
        $retval['context'] = "$context";
        $retval['license'] = $license;
        $retval['tags'] = '';
        $retval['language'] = $language;
        $retval['resourcetype'] = "$resourcetype";
        $retval['creator'] = 'oermod_resource\module';
        if ($upload == 1) {
            $retval['upload'] = "$upload";
        }
        if ($ignore == 1) {
            $retval['ignore'] = "$ignore";
        }
        // Classification plugins have to test with extended fromform array.
        return $retval;
    }
}
