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
 * Class courseinfo_sync
 *
 * Defines the methods to update the courseinfo database table with internal and external course informations.
 */
class courseinfo_sync {
    /**
     * Run the sync.
     *
     * Compares all stored courseinfo entries with the fields loaded in courseinfo.php
     * - Add new courseinfo entries.
     * - Update existing entries, except the fields that are marked as edited.
     * - Delete entries not found anymore, if an entry has been edited,
     * it will be marked as deleted, so that no information is lost.
     *
     * @param int $courseid Moodle courseid
     * @return void
     * @throws \dml_exception
     */
    public function sync_course(int $courseid): void {
        $metadata = new courseinfo();
        $oldcourses = $metadata->load_metadata_from_database($courseid);
        $newcourses = $metadata->generate_metadata($courseid);
        $sync = [
                'create' => [],
                'update' => [],
                'markdeleted' => [],
        ];
        foreach ($newcourses as $newcourse) {
            $found = false;
            foreach ($oldcourses as $key => $oldcourse) {
                if ($newcourse->coursecode == $oldcourse->coursecode) {
                    $found = true;
                    [$updatecourse, $update] = $this->compare_course($oldcourse, $newcourse);
                    if ($update) {
                        $sync['update'][] = $updatecourse;
                    }
                    unset($oldcourses[$key]);
                }
            }
            if (!$found) {
                $sync['create'][] = $newcourse;
            }
        }
        if (count($oldcourses) > 0) {
            foreach ($oldcourses as $course) {
                $sync['markdeleted'][] = $course;
            }
        }
        global $DB;
        foreach ($sync['create'] as $course) {
            $course->customfields = empty($course->customfields) ? null : json_encode($course->customfields);
            $DB->insert_record('local_oer_courseinfo', $course);
        }
        foreach ($sync['update'] as $course) {
            $course->deleted = 0;
            $DB->update_record('local_oer_courseinfo', $course);
        }
        foreach ($sync['markdeleted'] as $course) {
            $delete = false;
            if ($course->coursename_edited == 0 &&
                    $course->structure_edited == 0 &&
                    $course->description_edited == 0 &&
                    $course->objectives_edited == 0 &&
                    $course->organisation_edited == 0 &&
                    $course->language_edited == 0 &&
                    $course->lecturer_edited == 0) {
                $delete = true;
            }
            if ($delete) {
                $DB->delete_records('local_oer_courseinfo', ['courseid' => $course->courseid, 'coursecode' => $course->coursecode]);
            } else {
                if ($course->deleted == 1 && $course->ignored == 1) {
                    // Course is just revisited, and already set to delete - skip.
                    continue;
                }
                $course->deleted = 1;
                $course->ignored = 1;
                $course->timemodified = time();
                $DB->update_record('local_oer_courseinfo', $course);
            }
        }
    }

    /**
     * Compare two course objects.
     * Stores the difference into a new stdClass object
     * Returns a stdClass with the updated fields, and a bool if an update is necessary.
     * Edited fields are not changed.
     *
     * @param \stdClass $oldcourse
     * @param \stdClass $newcourse
     * @return array
     */
    private function compare_course($oldcourse, $newcourse): array {
        $updatecourse = courseinfo::get_default_metadata_object($oldcourse->courseid);
        $updateneeded = false;
        $updatecourse->id = $oldcourse->id;
        $updatecourse->courseid = $oldcourse->courseid;
        $updatecourse->coursecode = $oldcourse->coursecode;
        $updatecourse->deleted = $oldcourse->deleted;
        $updatecourse->ignored = $oldcourse->ignored;
        $updatecourse->external_courseid = $oldcourse->external_courseid;
        $updatecourse->external_sourceid = $oldcourse->external_sourceid;
        $updatecourse->coursename = $oldcourse->coursename_edited == 1 ? $oldcourse->coursename : $newcourse->coursename;
        $updatecourse->coursename_edited = $oldcourse->coursename_edited;
        $updatecourse->structure = $oldcourse->structure_edited == 1 ? $oldcourse->structure : $newcourse->structure;
        $updatecourse->structure_edited = $oldcourse->structure_edited;
        $updatecourse->description = $oldcourse->description_edited == 1 ? $oldcourse->description :
                $newcourse->description;
        $updatecourse->description_edited = $oldcourse->description_edited;
        $updatecourse->objectives = $oldcourse->objectives_edited == 1 ? $oldcourse->objectives : $newcourse->objectives;
        $updatecourse->objectives_edited = $oldcourse->objectives_edited;
        $updatecourse->organisation = $oldcourse->organisation_edited == 1 ? $oldcourse->organisation :
                $newcourse->organisation;
        $updatecourse->organisation_edited = $oldcourse->organisation_edited;
        $updatecourse->language = $oldcourse->language_edited == 1 ? $oldcourse->language : $newcourse->language;
        $updatecourse->language_edited = $oldcourse->language_edited;
        $updatecourse->lecturer = $oldcourse->lecturer_edited == 1 ? $oldcourse->lecturer : $newcourse->lecturer;
        $updatecourse->lecturer_edited = $oldcourse->lecturer_edited;
        $updatecourse->subplugin = $oldcourse->subplugin;
        $updatecourse->usermodified = $newcourse->usermodified;
        $updatecourse->timecreated = $oldcourse->timecreated;
        $updatecourse->timemodified = $newcourse->timemodified;
        // This check has to be made against newcourse, oldcourse may not exist yet.
        if ($newcourse->subplugin == courseinfo::BASETYPE) {
            [$updatecourse->customfields, $updateneeded] = $this->compare_customfields($oldcourse->customfields,
                    $newcourse->customfields);
        }

        if ($oldcourse->coursename_edited == 0 && $oldcourse->coursename != $newcourse->coursename) {
            $updateneeded = true;
        }
        if ($oldcourse->structure_edited == 0 && $oldcourse->structure != $newcourse->structure) {
            $updateneeded = true;
        }
        if ($oldcourse->description_edited == 0 && $oldcourse->description != $newcourse->description) {
            $updateneeded = true;
        }
        if ($oldcourse->objectives_edited == 0 && $oldcourse->objectives != $newcourse->objectives) {
            $updateneeded = true;
        }
        if ($oldcourse->organisation_edited == 0 && $oldcourse->organisation != $newcourse->organisation) {
            $updateneeded = true;
        }
        if ($oldcourse->language_edited == 0 && $oldcourse->language != $newcourse->language) {
            $updateneeded = true;
        }
        if ($oldcourse->lecturer_edited == 0 && $oldcourse->lecturer != $newcourse->lecturer) {
            $updateneeded = true;
        }
        if ($oldcourse->deleted == 1) {
            $updateneeded = true;
        }

        return [$updatecourse, $updateneeded];
    }

    /**
     * Customfields are added in a different way than the normal course fields. Customfields cannot be changed
     * in the OER plugin, so no overwrite is possible. That means only need to check if something has changed to determine
     * if an update is needed.
     *
     * @param array|null $oldfields
     * @param array|null $newfields
     * @return array
     */
    public function compare_customfields(?array $oldfields, ?array $newfields): array {
        if (empty($newfields)) {
            return [null, !empty($oldfields)];
        }
        $oldfields = json_encode($oldfields);
        $newfields = json_encode($newfields);

        $oldhash = hash('sha256', $oldfields);
        $newhash = hash('sha256', $newfields);

        return [$newfields, $oldhash != $newhash];
    }
}
