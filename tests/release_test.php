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

use local_oer\metadata\coursetofile;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helper/testcourse.php');

/**
 * Class release_testcase
 *
 * @coversDefaultClass \local_oer\release
 */
class release_test extends \advanced_testcase {
    /**
     * Test if the snapshot and release classes are working together.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::get_released_files
     * @covers ::__construct
     */
    public function test_get_released_files() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['courseid' => $course->id]),
                          'There should be at least one courseinfo entry for testcourse');
        $snapshot = new snapshot($course->id);
        $release  = new release($course->id);
        $files    = $release->get_released_files();
        $this->assertTrue(empty($files), 'No files have been marked for release yet');
        $helper->set_files_to($course->id, 1, true);
        $files = $release->get_released_files();
        $this->assertTrue(empty($files), 'No files have been marked for release yet');
        $snapshot->create_snapshot_of_course_files();
        $files = $release->get_released_files();
        $this->assertEquals(1, count($files), 'One file should be ready for release');
        $helper->set_files_to($course->id, 5, true);
        $snapshot->create_snapshot_of_course_files();
        $files = $release->get_released_files();
        $this->assertEquals(5, count($files), 'All five files should be ready to release');
        $helper->set_files_to($course->id, 1, false);
        $snapshot->create_snapshot_of_course_files();
        $files = $release->get_released_files();
        $this->assertEquals(5, count($files),
                            'One file has been set to non-release, but the files already have been released, so 5 are found');
    }

    /**
     * The method under test delivers the released metadata for a file. There are plenty of possibilities how this metadata
     * can be extended, so the tests will focus on certain aspects if some fields are present or not.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::get_file_release_metadata_json
     */
    public function test_get_file_release_metadata_json() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $helper->set_files_to($course->id, 1, true);
        $contenthash = $helper->get_contenthash_of_first_found_file($course);
        $files       = $DB->get_records('files', ['contenthash' => $contenthash]);
        $this->assertCount(2, $files, 'One file should be found, the second entry is the folder.');
        $fileid = 0;
        foreach ($files as $file) {
            if ($file->filename != '.') {
                $fileid = $file->id;
            }
        }
        $fs   = get_file_storage();
        $file = $fs->get_file_by_id($fileid);
        $this->assertTrue(is_a($file, 'stored_file'));

        $release         = new release($course->id);
        $releasemetadata = new \ReflectionMethod($release, 'get_file_release_metadata_json');
        $releasemetadata->setAccessible(true);

        $snapshot = new snapshot($course->id);
        $snapshot->create_snapshot_of_course_files();
        $snapshots = $snapshot->get_latest_course_snapshot();
        $this->assertCount(1, $snapshots);
        $keys = array_keys($snapshots);
        $this->assertEquals($contenthash, reset($keys));
        $metadata       = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $expectedcounts = [
                'general' => 16,
                'persons' => 2,
                'tags'    => 0,
                'courses' => 1,
                'course'  => [10],
        ];
        $this->assert_count_metadata($metadata, $expectedcounts);
        $this->assert_metadata_default_fields($metadata);
        // Add some tags to metadata.
        $tags                   = 'Impressive,UnitTest,Tags';
        $expectedcounts['tags'] = 3;
        $DB->set_field('local_oer_files', 'tags', $tags, ['courseid' => $course->id, 'contenthash' => $contenthash]);
        $snapshot->create_snapshot_of_course_files();
        $snapshots = $snapshot->get_latest_course_snapshot();
        $metadata  = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $this->assert_count_metadata($metadata, $expectedcounts);
        $this->assert_metadata_default_fields($metadata);

        // Test license shortname replacement.
        $license = $metadata['license'];
        $this->assertEquals('cc-4.0', $license['shortname'], 'Updated 2023-11-02 due to Moodle licence change.');
        $replacement = "xx=>replacement\r\nabc=>otherreplacement\r\ncc-4.0=>replacedtextintest\r\nlast=>lastline";
        set_config('uselicensereplacement', 1, 'local_oer');
        set_config('licensereplacement', $replacement, 'local_oer');
        $metadata = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $license  = $metadata['license'];
        $this->assertEquals('replacedtextintest', $license['shortname']);

        // Test multiple course metadata in same moodle course.
        $courseinfo                      = new \stdClass();
        $courseinfo->courseid            = $course->id;
        $courseinfo->coursecode          = 'ADD1COURSE';
        $courseinfo->deleted             = 0;
        $courseinfo->ignored             = 0;
        $courseinfo->external_courseid   = 123;
        $courseinfo->external_sourceid   = 234;
        $courseinfo->coursename          = 'External courseinfo added';
        $courseinfo->coursename_edited   = 0;
        $courseinfo->structure           = 'VO';
        $courseinfo->structure_edited    = 0;
        $courseinfo->description         = 'Add additional courseinfo to test multiple courseinfos in metadata';
        $courseinfo->description_edited  = 0;
        $courseinfo->objectives          = 'Unit test';
        $courseinfo->objectives_edited   = 0;
        $courseinfo->organisation        = 'LLT';
        $courseinfo->organisation_edited = 0;
        $courseinfo->language            = 'en';
        $courseinfo->language_edited     = 0;
        $courseinfo->lecturer            = 'Christian Ortner';
        $courseinfo->lecturer_edited     = 0;
        $courseinfo->customfields        = null;
        $courseinfo->subplugin           = 'tugraz';
        $courseinfo->usermodified        = 2;
        $courseinfo->timecreated         = time();
        $courseinfo->timemodified        = time();
        $DB->insert_record('local_oer_courseinfo', $courseinfo);
        $courseinfo2                    = clone($courseinfo);
        $courseinfo2->coursecode        = 'ADD2COURSE';
        $courseinfo2->external_courseid = 345;
        $courseinfo2->ignored           = 1;
        $DB->insert_record('local_oer_courseinfo', $courseinfo2);

        $expectedcounts['courses'] = 2;
        $expectedcounts['course']  = [10, 10];

        $snapshot->create_snapshot_of_course_files();
        $snapshots = $snapshot->get_latest_course_snapshot();
        $metadata  = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $this->assert_count_metadata($metadata, $expectedcounts);
        $this->assert_metadata_default_fields($metadata);

        // Add additional data to latest snapshot, to test if additional data is added correctly to result.
        $modifiedsnapshot                 = $snapshots[$contenthash];
        $additionaldata                   = [
                'semester'     => 'WS',
                'hoursperunit' => 10,
                'data'         => 5,
                'persons'      => 'Conflict', // This conflicts with existing array, should not do anything.
        ];
        $modifiedsnapshot->additionaldata = json_encode($additionaldata);
        $DB->update_record('local_oer_snapshot', $modifiedsnapshot);
        $metadata                  = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $expectedcounts['general'] = $expectedcounts['general'] + 3; // Three new fields have been added.
        $this->assert_count_metadata($metadata, $expectedcounts);
        $this->assert_metadata_default_fields($metadata);
        $this->assertArrayHasKey('semester', $metadata);
        $this->assertArrayHasKey('hoursperunit', $metadata);
        $this->assertArrayHasKey('data', $metadata);

        // Test course customfields - configurate customfields and test if data is present in release.
        $customcat1 = $this->getDataGenerator()->create_custom_field_category(['name' => 'First Category']);
        $this->getDataGenerator()->create_custom_field(
                [
                        'name'       => 'semester',
                        'shortname'  => 'sem',
                        'type'       => 'text',
                        'categoryid' => $customcat1->get('id'),
                        'configdata' => [
                                'visibility'   => 0,
                                'defaultvalue' => 'nosemester'
                        ]
                ]);
        $handler               = \core_course\customfield\course_handler::create();
        $data                  = new \stdClass();
        $data->id              = $course->id;
        $data->customfield_sem = 'WS';
        $handler->instance_form_save($data);
        set_config('coursecustomfields', 1, 'local_oer');
        set_config('coursecustomfieldsvisibility', 0, 'local_oer');
        set_config('coursecustomfieldsignored', '', 'local_oer');
        $helper->sync_course_info($course->id);
        $snapshot->create_snapshot_of_course_files();
        $snapshots                 = $snapshot->get_latest_course_snapshot();
        $metadata                  = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $expectedcounts['general'] = 16; // New release should not have injected additional fields.
        $expectedcounts['courses'] = 1;
        $expectedcounts['course']  = [11]; // Moodle course now has additional customfield.
        $this->assert_count_metadata($metadata, $expectedcounts);
        $this->assert_metadata_default_fields($metadata);

        $DB->insert_record('local_oer_courseinfo', $courseinfo);
        $DB->insert_record('local_oer_courseinfo', $courseinfo2);

        $snapshot->create_snapshot_of_course_files();
        $snapshots                 = $snapshot->get_latest_course_snapshot();
        $metadata                  = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $expectedcounts['general'] = 16; // New release should not have injected additional fields.
        $expectedcounts['courses'] = 2;
        $expectedcounts['course']  = [10, true, 1]; // Moodle course now has additional customfield.
        $this->assert_count_metadata($metadata, $expectedcounts);
        $this->assert_metadata_default_fields($metadata);
        $moodlecourse = null;
        foreach ($metadata['courses'] as $course) {
            if (strpos($course->identifier, 'moodlecourse') !== false) {
                $moodlecourse = $course;
            }
        }
        $this->assertIsArray($moodlecourse->customfields);
        $customfield = $moodlecourse->customfields[0];
        $this->assertCount(1, $moodlecourse->customfields);
        $this->assertEquals('sem', $customfield->shortname);
        $this->assertEquals('semester', $customfield->fullname);
        $this->assertEquals('text', $customfield->type);
        $this->assertEquals('WS', $customfield->data);
        $this->assertEquals($customcat1->get('name'), $customfield->category);

        // Test courseinfo from multiple moodle courses that use the same file.
        set_config('coursetofile', '1', 'local_oer');
        $course2 = $this->getDataGenerator()->create_course();
        $helper->generate_resource($course2, $this->getDataGenerator(), $file->get_filename(), null, $file->get_content());
        $helper->sync_course_info($course2->id);
        $coursetofile               = new \stdClass();
        $coursetofile->contenthash  = $contenthash;
        $coursetofile->courseid     = $course2->id;
        $coursetofile->coursecode   = 'moodlecourse-' . $course2->id;
        $coursetofile->state        = coursetofile::COURSETOFILE_ENABLED;
        $coursetofile->usermodified = 2;
        $coursetofile->timecreated  = time();
        $coursetofile->timemodified = time();
        $DB->insert_record('local_oer_coursetofile', $coursetofile);
        $snapshot->create_snapshot_of_course_files();
        $snapshots                 = $snapshot->get_latest_course_snapshot();
        $metadata                  = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $expectedcounts['courses'] = 3;
        $expectedcounts['course']  = [10, true, 1];
        $this->assert_count_metadata($metadata, $expectedcounts);
        $this->assert_metadata_default_fields($metadata);
        $this->assertEquals('nosemester', $metadata['courses'][2]->customfields[0]->data);
    }

    /**
     * Count the array and nested arrays in the metadata returnvalue.
     * Only dynamic values are tested with this method. License and persons always have
     * 3 fields and are tested below in the field assert method.
     *
     * $expectedcounts = [
     *   general => x,
     *   persons => x,
     *   tags => x,
     *   courses => x,
     *   course => [ // fields for each course, can differ when using customfields.
     *     x, y,
     *   ]
     * ]
     *
     * @param array $metadata
     * @param array $expectedcounts
     * @return void
     */
    private function assert_count_metadata(array $metadata, array $expectedcounts) {
        $this->assertIsArray($metadata);
        $this->assertIsArray($expectedcounts);
        $this->assertCount($expectedcounts['general'], $metadata);
        $this->assertCount($expectedcounts['persons'], $metadata['persons']);
        $this->assertCount($expectedcounts['tags'], $metadata['tags']);
        $this->assertCount($expectedcounts['courses'], $metadata['courses']);
        $fields       = $expectedcounts['course'][0];
        $customfield  = $expectedcounts['course'][1] ?? false;
        $amountfields = $expectedcounts['course'][2] ?? 0;
        foreach ($metadata['courses'] as $course) {
            if ($customfield && strpos($course->identifier, 'moodlecourse') !== false) {
                $this->assertCount($fields + $amountfields, (array) $course);
            } else {
                $this->assertCount($fields, (array) $course);
            }
        }
    }

    /**
     * Called multiple times, so it has been separated from test.
     *
     * @param array $metadata
     * @return void
     */
    private function assert_metadata_default_fields(array $metadata) {
        // Test default file metadata.
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('title', $metadata);
        $this->assertArrayHasKey('contenthash', $metadata);
        $this->assertArrayHasKey('fileurl', $metadata);
        $this->assertArrayHasKey('abstract', $metadata);
        $this->assertArrayHasKey('license', $metadata);
        $this->assertArrayHasKey('context', $metadata);
        $this->assertArrayHasKey('resourcetype', $metadata);
        $this->assertArrayHasKey('language', $metadata);
        $this->assertArrayHasKey('persons', $metadata);
        $this->assertArrayHasKey('tags', $metadata);
        $this->assertArrayHasKey('mimetype', $metadata);
        $this->assertArrayHasKey('filesize', $metadata);
        $this->assertArrayHasKey('filecreationtime', $metadata);
        $this->assertArrayHasKey('timereleased', $metadata);
        $this->assertArrayHasKey('classification', $metadata);
        $this->assertArrayHasKey('courses', $metadata);
        // Test license fields.
        $this->assertIsArray($metadata['license']);
        $this->assertCount(3, $metadata['license']);
        $this->assertArrayHasKey('shortname', $metadata['license']);
        $this->assertArrayHasKey('fullname', $metadata['license']);
        $this->assertArrayHasKey('source', $metadata['license']);
        // Test course metadata fields.
        // This test is the most basic variant of added course metadata. Only the moodle course where the file is in is present.
        // Courses in this array can come from additional subplugins for course metadata.
        // Tests for this scenario have to be implemented in the subplugins delivering the data.
        // Also, the file could be used in different courses, and the editor could add the metadata of those courses to the file.
        $this->assertIsArray($metadata['courses']);
        $cm = reset($metadata['courses']);
        $cm = (array) $cm;
        $this->assertArrayHasKey('identifier', $cm);
        $this->assertArrayHasKey('courseid', $cm);
        $this->assertArrayHasKey('sourceid', $cm);
        $this->assertArrayHasKey('coursename', $cm);
        $this->assertArrayHasKey('structure', $cm);
        $this->assertArrayHasKey('description', $cm);
        $this->assertArrayHasKey('objective', $cm);
        $this->assertArrayHasKey('organisation', $cm);
        $this->assertArrayHasKey('courselanguage', $cm);
        $this->assertArrayHasKey('lecturer', $cm);
        // Test the person subarray.
        $this->assertIsArray($metadata['persons']);
        foreach ($metadata['persons'] as $person) {
            $person = (array) $person;
            $this->assertCount(3, $person);
            $this->assertArrayHasKey('firstname', $person);
            $this->assertArrayHasKey('lastname', $person);
            $this->assertArrayHasKey('role', $person);
        }
        $this->assertIsArray($metadata['tags']);
    }

    /**
     * The base plugin does not add any classification data by itself. There is a subplugin type that can be implemented to add
     * this kind of information to the metadata. So this test only focuses on the base plugin and the expected result without
     * additional classification plugins.
     *
     * To test if the data of any classification plugin is processed correctly, the subplugin has to implement a test for it.
     *
     * @return void
     * @throws \ReflectionException
     * @covers ::prepare_classification_fields
     */
    public function test_prepare_classification_fields() {
        $this->resetAfterTest();
        $course                = $this->getDataGenerator()->create_course();
        $release               = new release($course->id);
        $prepareclassification = new \ReflectionMethod($release, 'prepare_classification_fields');
        $prepareclassification->setAccessible(true);
        // The default case for the classification field data in snapshot table is 'null'.
        $this->assertIsArray($prepareclassification->invoke($release, null));
        $this->assertEmpty($prepareclassification->invoke($release, null));
        // Also when it is an empty string, an empty array should be returned.
        $this->assertIsArray($prepareclassification->invoke($release, ''));
        $this->assertEmpty($prepareclassification->invoke($release, ''));
        // As there exist no subplugin to run the next lines of code in the base plugin, this test remains incomplete.
    }
}
