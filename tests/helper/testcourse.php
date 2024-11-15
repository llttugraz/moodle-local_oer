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

use local_oer\helper\filehelper;
use local_oer\metadata\courseinfo_sync;
use local_oer\modules\element;

/**
 * Class testcourse
 *
 * Helper class for unit tests to prepare a course with modules and files for oer use cases.
 */
class testcourse {
    /**
     * Generate a course with resource modules and files.
     *
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
     * Generate a course with folder module.
     *
     *
     * @param \testing_data_generator $generator Object for generating testdata
     * @return \stdClass
     */
    public function generate_testcourse_with_folder(\testing_data_generator $generator) {
        $course = $generator->create_course();
        $this->generate_folder($course, $generator);
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
     * @param int $courseid
     * @param \stored_file $file
     * @return \stdClass
     * @throws \coding_exception
     */
    public function generate_oer_non_release_metadata(int $courseid, \stored_file $file) {
        $metadata = new \stdClass();
        $metadata->type = element::OERTYPE_MOODLEFILE;
        $metadata->courseid = $courseid;
        $metadata->identifier = $this->generate_identifier($file->get_contenthash());
        $metadata->title = $file->get_filename();
        $metadata->description = '';
        $metadata->context = 0;
        $metadata->license = 'allrightsreserved';
        $metadata->persons = '';
        $metadata->tags = null;
        $metadata->language = 'en';
        $metadata->resourcetype = 0;
        $metadata->classification = null;
        $metadata->releasestate = 0;
        $metadata->preference = 0;
        $metadata->type = element::OERTYPE_MOODLEFILE;
        $metadata->typedata = null;
        $metadata->usermodified = 2;
        $metadata->timemodified = time();
        $metadata->timecreated = time();
        return $metadata;
    }

    /**
     * Get the element object for a test file.
     *
     * @param \stored_file $file
     * @return element
     * @throws \coding_exception
     */
    public function get_element_for_file(\stored_file $file): element {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_identifier($this->generate_identifier($file->get_contenthash()));
        $element->set_title($file->get_filename());
        $element->set_license($file->get_license());
        $element->set_mimetype($file->get_mimetype());
        $element->set_filesize($file->get_filesize());
        $element->set_origin('mod_resource', 'pluginname', 'mod_resource');
        $element->set_source(filehelper::get_file_url($file, true));
        return $element;
    }

    /**
     * Overwrite the fields that are required with a certain value for release.
     * Returns filesize for comparison.
     *
     * @param int $courseid
     * @param element $element
     * @return int
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function set_file_to_release(int $courseid, element $element) {
        $file = $this->find_file_by_element($element);
        $metadata = $this->generate_oer_non_release_metadata($courseid, $file);
        $metadata->context = 1;
        $metadata->license = 'cc-4.0'; // Updated 2023-11-02 due to Moodle licence change.
        $metadata->persons = '{"persons":[{"role":"Author","lastname":"Ortner","firstname":"Christian"}, ' .
                '{"role":"publisher","lastname":"Other","firstname":"Name"}]}';
        $metadata->typedata = json_encode([
                'mimetype' => $file->get_mimetype(),
                'filesize' => $file->get_filesize(),
                'filecreationtime' => $file->get_timecreated(),
        ]);
        $metadata->releasestate = 1;
        $this->update_db($metadata);
        return $file->get_filesize();
    }

    /**
     * Find a stored file based on the information stored in element class.
     *
     * @param element $element
     * @return \stored_file
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function find_file_by_element(element $element): \stored_file {
        global $DB;
        $decompose = identifier::decompose($element->get_identifier());
        // All entries with the same contenthash are the same file.
        // Maybe some metadata in the table differs, but that is of no concern for the tests.
        $records = $DB->get_records('files', ['contenthash' => $decompose->value]);
        $file = null;
        foreach ($records as $record) {
            if ($record->filename != '.') {
                $file = $record;
                break;
            }
        }
        $fs = get_file_storage();
        return $fs->get_file_by_id($file->id);
    }

    /**
     * Set files to release or non-release.
     * The files will be modified in that order they come from filelist::get_course_files.
     *
     * @param int $courseid
     * @param int $amount How many files of this course should be changed
     * @param bool $release Set the file to release or non-release
     * @return int
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function set_files_to(int $courseid, int $amount, bool $release = false) {
        $coursefiles = filelist::get_course_files($courseid);
        $i = 0;
        $size = 0;
        foreach ($coursefiles as $coursefile) {
            if ($i == $amount) {
                break;
            }
            $i++;
            if ($release) {
                $size += $this->set_file_to_release($courseid, $coursefile);
            } else {
                $this->set_file_to_non_release($courseid, $coursefile);
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
        if ($record = $DB->get_record('local_oer_elements', [
                'courseid' => $metadata->courseid,
                'identifier' => $metadata->identifier,
        ])) {
            $metadata->id = $record->id;
            $DB->update_record('local_oer_elements', $metadata);
        } else {
            $DB->insert_record('local_oer_elements', $metadata);
        }
    }

    /**
     * Similar to the set_release function - but set a file to non-release.
     *
     * @param int $courseid
     * @param element $element
     * @return void
     * @throws \dml_exception
     */
    public function set_file_to_non_release(int $courseid, element $element) {
        $file = $this->find_file_by_element($element);
        $metadata = $this->generate_oer_non_release_metadata($courseid, $file);
        $this->update_db($metadata);
    }

    /**
     * Generate a resource in given course.
     *
     * @param \stdClass $course
     * @param \testing_data_generator $generator
     * @param string $filename If empty, random filename is generated
     * @param int|null $draftid If null, moodle is asked for unused draft id
     * @param string $content If empty, random bytes are written
     * @return \stdClass
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function generate_resource(\stdClass $course, \testing_data_generator $generator, string $filename = '',
            ?int $draftid = null, string $content = '') {
        $record = new \stdClass();
        $record->course = $course;
        [$draftid, $file] = $this->generate_file($filename, $draftid, $content);
        $record->files = $draftid;
        return $generator->create_module('resource', $record);
    }

    /**
     * Generate a folder in given course.
     *
     * @param \stdClass $course
     * @param \testing_data_generator $generator
     * @param string $filename If empty, random filename is generated
     * @param int|null $draftid If null, moodle is asked for unused draft id
     * @param string $content If empty, random bytes are written
     * @return \stdClass
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function generate_folder(\stdClass $course, \testing_data_generator $generator, string $filename = '',
            ?int $draftid = null, string $content = '') {
        $record = new \stdClass();
        $record->course = $course;

        [$draftid, $file] = $this->generate_file($filename, $draftid, $content);
        $record->files = $draftid;
        return $generator->create_module('folder', $record);
    }


    /**
     * Generate a file with moodle file_storage.
     * Only the draft id is required for the module generators.
     *
     * @param string $filename If empty, random filename is generated
     * @param int|null $draftid If null, moodle is asked for unused draft id
     * @param string $content If empty, random bytes are written
     * @return array
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function generate_file(string $filename = '', ?int $draftid = null, string $content = ''): array {
        global $USER;
        if ($filename == '') {
            $filename = 'Testfile' . rand(1000000, 1000000000);
        }

        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $draftid = !is_null($draftid) ? $draftid : file_get_unused_draft_itemid();
        $content = $content == '' ? random_bytes(rand(1, 1000)) : $content;

        $filerecord = [
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftid,
                'filepath' => '/',
                'filename' => $filename,
                'sortorder' => 1,
                'license' => 'allrightsreserved',
        ];
        $file = $fs->create_file_from_string($filerecord, $content);
        return [$draftid, $file];
    }

    /**
     * Returns the contenthash of the first found
     *
     * @param \stdClass $course Course object of Moodle test data generator.
     * @return string|null
     * @throws \dml_exception
     */
    public function get_contenthash_of_first_found_file(\stdClass $course): ?string {
        global $DB;
        $module = $DB->get_record('modules', ['name' => 'resource']);
        $cms = $DB->get_records('course_modules', ['course' => $course->id, 'module' => $module->id], 'id ASC');
        $resource = reset($cms);
        $context = \context_module::instance($resource->id);
        // There is also a dot entry. So there are at least two records.
        $files = $DB->get_records('files', ['contextid' => $context->id], 'id ASC');
        $contenthash = null;
        foreach ($files as $file) {
            if ($file->filename != '.') {
                $contenthash = $file->contenthash;
            }
        }
        return $contenthash;
    }

    /**
     * Wrapper for get_contenthash_of_first_found_file to create an identifier for the contenthash.
     *
     * @param \stdClass $course Course object of Moodle test data generator.
     * @return string|null
     */
    public function get_identifier_of_first_found_file(\stdClass $course): ?string {
        $contenthash = $this->get_contenthash_of_first_found_file($course);
        return $this->generate_identifier($contenthash);
    }

    /**
     * To ensure the identifier in all tests has the same format, use this function in testcourse functions.
     *
     * @param string $contenthash
     * @return string
     * @throws \coding_exception
     */
    public function generate_identifier(string $contenthash): string {
        global $CFG;
        return identifier::compose('moodle', $CFG->wwwroot, 'file', 'contenthash', $contenthash);
    }
}
