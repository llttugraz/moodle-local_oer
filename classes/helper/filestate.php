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

namespace local_oer\helper;

/**
 * Class filestate
 *
 * Calculate the state of a file, to show additional information on the filecard
 * in view.
 * If the file is used in more than one course, it can also be locked in other courses
 * to prevent multiple edits with different metadata.
 */
class filestate {
    /**
     * File has not been edited for OER yet.
     * If it is used in more than one course, just show in every course that it is also
     * used in other courses.
     */
    const STATE_FILE_NOT_EDITED = 1;

    /**
     * File has been edited in one course. The metadata is locked in all other courses.
     * The course where the file is edited is marked.
     */
    const STATE_FILE_EDITED = 2;

    /**
     * File has been released. The metadata is locked in all courses.
     * The course where it has been released is marked.
     */
    const STATE_FILE_RELEASED = 3;

    /**
     * File may be in an ambiguous state. Maybe it has been edited in multiple courses.
     * In this state an administrator needs to examine what's wrong and clean up the mess.
     */
    const STATE_FILE_ERROR = 0;

    public static function calculate_file_state(string $contenthash) {
        $courses = [];
        global $DB;
        // Step 1: Load usage of contenthash.
        $sql    = "SELECT DISTINCT(contextid) FROM {files} " .
                  "WHERE contenthash = :contenthash " .
                  "AND (component = 'mod_resource' OR component = 'mod_folder')";
        $usages = $DB->get_records_sql($sql, ['contenthash' => $contenthash]);
        if (!$usages) {
            // Woah, how did this happen?
            throw new \coding_exception('Something really unexpected happened, ' .
                                        'a file contenthash (' . $contenthash .
                                        ') has been searched that is not used anywhere');
        }

        // Step 2: Extract courseids from contexts.
        // As this are module contexts we need to find the parent course of it.
        foreach ($usages as $contextid => $usage) {
            list(, $course,) = get_context_info_array($contextid);
            $courses[$course->id] = $course->fullname;
        }

        // Step 3: Determine OER file state. Is file being edited or already released?
        // There should only be one entry possible, but it is also tested if there.
        // Is ambiguous information in the table about this file.
        $state    = self::STATE_FILE_NOT_EDITED;
        $editorid = 0;
        $oerfiles = $DB->get_records('local_oer_files', ['contenthash' => $contenthash], 'id ASC', 'courseid');
        if ($oerfiles && count($oerfiles) > 1) {
            // TODO: should there be any notification for administrators to look into this problem?
            return [self::STATE_FILE_ERROR, null];
        } else if ($oerfiles && count($oerfiles) == 1) {
            $cid   = array_key_first($oerfiles);
            $state = self::STATE_FILE_EDITED;
            if (isset($courses[$cid])) {
                $editorid = $cid;
            } else {
                // Why is the course not set for this file? Probably it has been deleted in this context.
                // So it will be inherited to the first course in the list.
                $editorid = array_key_first($courses);
                $DB->set_field('local_oer_files', 'courseid', $editorid, ['contenthash' => $contenthash]);
            }
        }

        // TODO: should the snapshot(s) also be inherited?
        if ($DB->record_exists('local_oer_snapshot', ['contenthash' => $contenthash])) {
            $state = self::STATE_FILE_RELEASED;
        }

        return [$state, $editorid, $courses];
    }
}