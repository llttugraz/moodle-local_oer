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
 * Helper to test if metadata fulfill all necessary criterias to be released.
 *
 * @package    local_oer
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2017 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\helper;

/**
 * Class requirements
 */
class requirements {
    /**
     * Test for the needed requirements of the metadata.
     * Some requirements are fixed (title, license, persons).
     * All other requirements can be set in the plugin settings.
     * Also the classification subplugins can be set as required.
     *
     * @param \stdClass $metadata
     * @return array
     * @throws \dml_exception
     */
    public static function metadata_fulfills_all_requirements(\stdClass $metadata) {
        $reqarray              = [];
        $licenseobject         = license::get_license_by_shortname($metadata->license);
        $reqarray['title']     = !empty($metadata->title);
        $reqarray['license']   = license::test_license_correct_for_upload($metadata->license) || is_null($licenseobject);
        $people                = json_decode($metadata->persons);
        $reqarray['persons']   = !empty($people->persons);
        $required              = explode(',', get_config('local_oer', 'requiredfields'));
        $storedclassifications = json_decode($metadata->classification);
        foreach ($required as $field) {
            switch ($field) {
                case 'description':
                    $reqarray['description'] = !empty($metadata->description);
                    break;
                case 'context':
                    $reqarray['context'] = $metadata->context > 0;
                    break;
                case 'tags':
                    $reqarray['tags'] = !empty($metadata->tags);
                    break;
                case 'language':
                    $reqarray['language'] = !empty($metadata->language) && $metadata->language != "0";
                    break;
                case 'resourcetype':
                    $reqarray['resourcetype'] = $metadata->resourcetype > 0;
                    break;
                default:
                    if (strpos($field, 'oerclassification') !== false) {
                        $name = explode('_', $field);
                        unset($name[0]);
                        $name             = implode($name);
                        $reqarray[$field] = isset($storedclassifications->$name) && !empty($storedclassifications->$name);
                    }
            }
        }

        $release    = $metadata->state == 1;
        $releasable = true;
        foreach ($reqarray as $value) {
            if ($value === false) {
                $releasable = false;
            }
        }

        return [$reqarray, $releasable, $release && $releasable];
    }

    /**
     * en the requirements change, the files that already have been set to release have to be tested against the
     * new requirements and the state has to be set to 0 if the file does not meet the new requirements settings.
     * Does not affect already made releases/snapshots as the requirements had other values back then.
     *
     * Also send a notification to affected users that the requirements have changed and some files have to be revisited.
     *
     * This function has been moved from settings.php to this helper. In the settings.php a wrapper remains, as there is a
     * callback necessary for a setting.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function reset_releasestate_if_necessary() {
        global $DB, $USER;
        $files   = $DB->get_records('local_oer_files', ['state' => 1], 'id ASC');
        $courses = [];
        foreach ($files as $file) {
            [$reqarray, $releasable, $release] = static::metadata_fulfills_all_requirements($file);
            if (!$release) {
                $courses[$file->courseid][] = $file;
                $file->state                = 0;
                $file->usermodified         = $USER->id;
                $file->timemodified         = time();
                $DB->update_record('local_oer_files', $file);
            }
        }
        if (!empty($courses)) {
            foreach ($courses as $course => $files) {
                // Update 19.10.2022 Christian. Check if file exists, do not send message if not.
                $filelist = \local_oer\filelist::get_course_files($course);
                foreach ($files as $key => $file) {
                    if (!isset($filelist[$file->contenthash])) {
                        unset($files[$key]);
                    }
                }
                if (empty($files)) {
                    continue;
                }
                $coursecontext = context_course::instance($course);
                $sql           = "SELECT u.id FROM {user} u " .
                                 "JOIN {local_oer_userlist} ul ON u.id = ul.userid " .
                                 "JOIN {user_enrolments} ue ON u.id = ue.userid " .
                                 "JOIN {enrol} e ON e.id = ue.enrolid " .
                                 "WHERE ul.type ='allow' AND e.courseid = :courseid";
                $users         = $DB->get_records_sql($sql, ['courseid' => $course]);
                foreach ($users as $userid) {
                    if (has_capability('local/oer:edititems', $coursecontext, $userid->id)) {
                        $user = $DB->get_record('user', ['id' => $userid->id]);
                        \local_oer\message::send_requirementschanged($user, $files, $course);
                    }
                }
            }
        }
    }
}
