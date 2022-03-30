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

use local_oer\metadata\courseinfo_sync;

/**
 * Class testcourse
 *
 * Helper class for unit tests to prepare a course with modules and files for oer use cases.
 */
class testcourse {
    /**
     * Generate a course with resource modules and files.
     *
     * TODO: add folder modules.
     *
     * @param \testing_data_generator $generator Object for generating testdata
     * @return \stdClass
     */
    public function generate_testcourse(\testing_data_generator $generator) {
        $course = $generator->create_course();
        $this->generate_resource($course, $generator);
        $this->generate_resource($course, $generator);
        $this->generate_resource($course, $generator);
        $this->generate_resource($course, $generator);
        $this->generate_resource($course, $generator);
        return $course;
    }

    /**
     * Sync the course info for this course to ensure there is at least one entry in the courseinfo table.
     *
     * @param int $courseid
     * @return void
     * @throws \dml_exception
     */
    public function sync_course_info(int $courseid) {
        $syncer = new courseinfo_sync();
        $syncer->sync_course($courseid);
    }

    /**
     * Contains all fields from oer files table with a non-release default value.
     *
     * @param int          $courseid
     * @param \stored_file $file
     * @return \stdClass
     */
    public function generate_oer_non_release_metadata(int $courseid, \stored_file $file) {
        $metadata                 = new \stdClass();
        $metadata->courseid       = $courseid;
        $metadata->contenthash    = $file->get_contenthash();
        $metadata->title          = $file->get_filename();
        $metadata->description    = '';
        $metadata->context        = 0;
        $metadata->license        = 'allrightsreserved';
        $metadata->persons        = '';
        $metadata->tags           = '';
        $metadata->language       = 'en';
        $metadata->resourcetype   = 0;
        $metadata->classification = null;
        $metadata->state          = 0;
        $metadata->preference     = 0;
        $metadata->usermodified   = 2;
        $metadata->timemodified   = time();
        $metadata->timecreated    = time();
        return $metadata;
    }

    /**
     * Overwrite the fields that are required with a certain value for release.
     * Returns filesize for comparison.
     *
     * @param int          $courseid
     * @param \stored_file $file
     * @return int
     * @throws \dml_exception
     */
    public function set_file_to_release(int $courseid, \stored_file $file) {
        $metadata          = $this->generate_oer_non_release_metadata($courseid, $file);
        $metadata->context = 1;
        $metadata->license = 'cc';
        $metadata->persons = '{"persons":[{"role":"Author","lastname":"Ortner","firstname":"Christian"}, ' .
                             '{"role":"Publisher","lastname":"Other","firstname":"Name"}]}';
        $metadata->state   = 1;
        $this->update_db($metadata);
        return $file->get_filesize();
    }

    /**
     * Set files to release or non-release.
     * The files will be modified in that order they come from filelist::get_course_files.
     *
     * @param int  $courseid
     * @param int  $amount  How many files of this course should be changed
     * @param bool $release Set the file to release or non-release
     * @return int
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function set_files_to(int $courseid, int $amount, bool $release = false) {
        $coursefiles = filelist::get_course_files($courseid);
        $i           = 0;
        $size        = 0;
        foreach ($coursefiles as $coursefile) {
            if ($i == $amount) {
                break;
            }
            $i++;
            if ($release) {
                $size += $this->set_file_to_release($courseid, $coursefile[0]['file']);
            } else {
                $this->set_file_to_non_release($courseid, $coursefile[0]['file']);
            }
        }
        return $size;
    }

    /**
     * Helper to prevent copy-paste code.
     *
     * @param \stdClass $metadata
     * @return void
     * @throws \dml_exception
     */
    private function update_db(\stdClass $metadata) {
        global $DB;
        if ($DB->record_exists('local_oer_files', ['courseid' => $metadata->courseid, 'contenthash' => $metadata->contenthash])) {
            $metadata->id = $DB->get_field('local_oer_files', 'id',
                                           ['courseid' => $metadata->courseid, 'contenthash' => $metadata->contenthash]);
            $DB->update_record('local_oer_files', $metadata);
        } else {
            $DB->insert_record('local_oer_files', $metadata);
        }
    }

    /**
     * Similar to the set_release function - but set a file to non-release.
     *
     * @param int          $courseid
     * @param \stored_file $file
     * @return void
     * @throws \dml_exception
     */
    public function set_file_to_non_release(int $courseid, \stored_file $file) {
        $metadata = $this->generate_oer_non_release_metadata($courseid, $file);
        $this->update_db($metadata);
    }

    /**
     * Generate a resource in given course.
     *
     * @param \stdClass               $course
     * @param \testing_data_generator $generator
     * @return \stdClass
     */
    public function generate_resource(\stdClass $course, \testing_data_generator $generator) {
        $record         = new \stdClass();
        $record->course = $course;
        $record->files  = $this->generate_file();
        return $generator->create_module('resource', $record);
    }

    /**
     * Generate a file with moodle file_storage.
     * Only the draft id is required for the module generators.
     *
     * @param string   $filename
     * @param int|null $draftid
     * @return int
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function generate_file(string $filename = '', ?int $draftid = null) {
        global $USER;
        if ($filename == '') {
            $filename = 'Testfile' . rand(1000000, 1000000000);
        }

        $fs          = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $draftid     = !is_null($draftid) ? $draftid : file_get_unused_draft_itemid();
        $content     = random_bytes(rand(1, 1000));

        $filerecord = array(
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea'  => 'draft',
                'itemid'    => $draftid,
                'filepath'  => '/',
                'filename'  => $filename,
                'sortorder' => 1,
        );
        $file       = $fs->create_file_from_string($filerecord, $content);
        return $draftid;
    }
}
