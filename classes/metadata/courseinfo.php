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
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\metadata;

/**
 * Class courseinfo
 *
 * Prepare and load course metadata.
 */
class courseinfo {
    /**
     * Defines the string for the moodle course metadata identifier.
     */
    const BASETYPE = 'base';

    /**
     * Load the metadata of a course.
     * A moodle course can have multiple course metadata.
     * External systems could be linked to the course.
     *
     * @param int $courseid Moodle courseid
     * @return array
     * @throws \dml_exception
     */
    public function load_metadata_from_database(int $courseid): array {
        global $DB;
        $records = $DB->get_records('local_oer_courseinfo', ['courseid' => $courseid], 'id ASC');
        foreach ($records as $key => $record) {
            if (!is_null($record->customfields)) {
                $records[$key]->customfields = json_decode($record->customfields, true);
            }
        }
        return $records;
    }

    /**
     * The default metadata object for a course.
     * Defines all fields a course can have.
     * Sets the default values.
     *
     * @param int $courseid Moodle courseid
     * @return \stdClass
     */
    public static function get_default_metadata_object(int $courseid): \stdClass {
        global $USER;
        $info = new \stdClass();
        $info->courseid = $courseid;
        $info->coursecode = '';
        $info->deleted = 0;
        $info->ignored = 0;
        $info->external_courseid = 0;
        $info->external_sourceid = 0;
        $info->coursename = '';
        $info->coursename_edited = 0;
        $info->structure = '';
        $info->structure_edited = 0;
        $info->description = '';
        $info->description_edited = 0;
        $info->objectives = '';
        $info->objectives_edited = 0;
        $info->organisation = '';
        $info->organisation_edited = 0;
        $info->language = '';
        $info->language_edited = 0;
        $info->lecturer = '';
        $info->lecturer_edited = 0;
        $info->customfields = null;
        $info->subplugin = self::BASETYPE;
        $info->usermodified = $USER->id;
        $info->timecreated = time();
        $info->timemodified = time();
        return $info;
    }

    /**
     * Create the metadata array.
     * At least the metadata from the moodle course is returned.
     * If an external plugin is installed and selected, the external
     * information is added by calling the load_data method from the subplugin.
     *
     * @param int $courseid
     * @return array
     * @throws \dml_exception
     */
    public function generate_metadata(int $courseid): array {
        global $DB, $CFG;
        $course = get_course($courseid);
        $allnames = \core_user\fields::for_name()->get_required_fields();
        foreach ($allnames as $key => $name) {
            $allnames[$key] = 'u.' . $name;
        }
        $sqlallnames = implode(',', $allnames);
        $sql = 'SELECT DISTINCT(u.id),' . $sqlallnames . ' FROM {user} u
                    JOIN {user_enrolments} ue ON ue.userid = u.id
                    JOIN {enrol} e ON e.id = ue.enrolid
                    JOIN {context} ctx ON ctx.instanceid = e.courseid
                    JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = u.id
                    WHERE ra.roleid = 3 AND e.status = 0 AND e.courseid = :courseid
                    AND ue.status = 0 AND ctx.contextlevel = 50';
        $users = $DB->get_records_sql($sql, ['courseid' => $courseid]);
        $teachers = [];
        foreach ($users as $user) {
            $teachers[] = fullname($user);
        }
        $info = self::get_default_metadata_object($courseid);
        $info->coursecode = 'moodlecourse-' . $courseid;
        $info->coursename = $course->fullname;
        $info->structure = '';
        $info->description = is_null($course->summary) ? '' : self::simple_html_to_text_reduction($course->summary);
        $info->lecturer = implode(', ', $teachers);
        $infos = ['default' => $info];
        $customfields = coursecustomfield::get_course_customfields_with_applied_config($courseid);
        $info->customfields = empty($customfields) ? null : $customfields;

        $external = get_config('local_oer', 'metadataaggregator');
        if ($external != 'no_value') {
            $file = $CFG->dirroot . '/local/oer/metadata/' . $external . '/classes/info.php';
            require_once($file);
            $class = 'oercourseinfo_' . $external . '\info';
            $info = new $class();
            $info->load_data($courseid, $infos);
        }

        return $infos;
    }

    /**
     * This is a very basic function to add some formatting to the reduced text fields.
     * The format is based on some html tags that are found in the document.
     *
     * - Replace some closing texts with newline
     * - Images are not used and are removed
     * - Ordered lists are reduced to unordered lists
     * - Urls: href will replace the text of the anchor
     *
     * @param string $text Text to remove html tags
     * @return string
     * @throws \Exception
     */
    public static function simple_html_to_text_reduction(string $text): string {
        preg_match_all('/(<a[^>]*>[^<]+<\/a>)/', $text, $urls);
        foreach ($urls[0] as $key => $url) {
            $a = new \SimpleXMLElement($url);
            $text = str_replace($urls[1][$key], $a['href'] . ' ', $text);
        }
        $breakline = ['</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</p>', '<br>', '</li>', '</ul>', '</ol>'];
        $text = str_replace($breakline, "\r\n", $text);
        $text = str_replace('<li>', '* ', $text);
        $text = html_entity_decode($text, ENT_COMPAT);
        return trim(strip_tags($text));
    }
}
