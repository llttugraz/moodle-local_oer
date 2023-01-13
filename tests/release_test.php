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
        $metadata  = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $this->assert_metadata_default_fields($metadata);
        // Test tags subarray.
        $this->assertIsArray($metadata['tags']);
        $this->assertEmpty($metadata['tags']);
        // Add some tags to metadata.
        $tags = 'Impressive,UnitTest,Tags';
        $DB->set_field('local_oer_files', 'tags', $tags, ['courseid' => $course->id, 'contenthash' => $contenthash]);
        $snapshot->create_snapshot_of_course_files();
        $snapshots = $snapshot->get_latest_course_snapshot();
        $metadata  = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $this->assert_metadata_default_fields($metadata);
        $this->assertIsArray($metadata['tags']);
        $this->assertCount(3, $metadata['tags']);

        // Test license shortname replacement.
        $license = $metadata['license'];
        $this->assertEquals('cc', $license['shortname']);
        $replacement = "xx=>replacement\r\nabc=>otherreplacement\r\ncc=>replacedtextintest\r\nlast=>lastline";
        set_config('uselicensereplacement', 1, 'local_oer');
        set_config('licensereplacement', $replacement, 'local_oer');
        $metadata = $releasemetadata->invoke($release, $file, $snapshots[$contenthash]);
        $license  = $metadata['license'];
        $this->assertEquals('replacedtextintest', $license['shortname']);

        // TODO:
        // Test course customfields - configurate customfields and test if data is present in release.
        // TODO:
        // Test courseinfo from multiple moodle courses that use the same file.

        // The section for additional data has to be tested by the subplugins that add data.
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
        $this->assertCount(1, $metadata['courses'], 'Only the moodle course is present');
        $cm = reset($metadata['courses']);
        $cm = (array) $cm;
        $this->assertCount(10, $cm, 'Only the default fields should be present');
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
        $this->assertCount(2, $metadata['persons']);
        foreach ($metadata['persons'] as $person) {
            $person = (array) $person;
            $this->assertCount(3, $person);
            $this->assertArrayHasKey('firstname', $person);
            $this->assertArrayHasKey('lastname', $person);
            $this->assertArrayHasKey('role', $person);
        }
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
