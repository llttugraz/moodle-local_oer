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
use local_oer\metadata\coursetofile;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helper/testcourse.php');

/**
 * Class snapshot_test
 *
 * @coversDefaultClass \local_oer\snapshot
 */
final class snapshot_test extends \advanced_testcase {
    /**
     * Setup test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test get latest course snapshot
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::get_latest_course_snapshot
     * @covers ::__construct
     */
    public function test_get_latest_course_snapshot(): void {
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $snapshot = new snapshot($course->id);
        $this->assertEmpty($snapshot->get_latest_course_snapshot());
        $helper->set_files_to($course->id, 3, true);
        $snapshot->create_snapshot_of_course_files(1);
        $result = $snapshot->get_latest_course_snapshot();
        $this->assertCount(3, $result);
    }

    /**
     * Test if file snapshots are correctly taken from a testcourse.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::create_snapshot_of_course_files
     * @covers ::create_file_snapshot
     * @covers ::add_type_data
     */
    public function test_create_snapshot_of_course_files(): void {
        global $DB;
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['courseid' => $course->id]),
                'There should be at least one courseinfo entry for testcourse');
        $this->assertEmpty($DB->get_records('local_oer_snapshot'));
        $snapshot = new snapshot($course->id);
        $snapshot->create_snapshot_of_course_files(1);
        $this->assertEmpty($DB->get_records('local_oer_snapshot'), 'No files are set for release yet.');
        $helper->set_files_to($course->id, 1, true);
        $snapshot->create_snapshot_of_course_files(2);
        $snapshot->create_snapshot_of_course_files(3);
        $this->assertEquals(1, $DB->count_records('local_oer_snapshot'),
                'Although the release is called 2 times, only one file should be released because nothing changed');
        $helper->set_files_to($course->id, 2, true);
        $snapshot->create_snapshot_of_course_files(4);
        $this->assertEquals(2, $DB->count_records('local_oer_snapshot'), 'Two files have been released.');
    }

    /**
     * Test the creation of a file snapshot.
     *
     * TODO: define test steps.
     *
     * @return void
     * @covers ::create_file_snapshot
     */
    public function test_create_file_snapshot(): void {
        $this->resetAfterTest();
        // MDL-0 TODO: write test.
    }

    /**
     * Subplugins can add additional metadata. However, the base plugin does not add anything extra.
     * The function returns null when only using the base plugin.
     *
     * This function has also to be tested in subplugins to test if the metadata that should be added
     * is added correctly.
     *
     * @return void
     * @throws \ReflectionException
     * @covers ::add_external_metadata
     */
    public function test_add_external_metadata(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $snapshot = new snapshot($course->id);
        $setstate = new \ReflectionMethod($snapshot, 'add_external_metadata');
        $setstate->setAccessible(true);
        $this->assertNull($setstate->invoke($snapshot));
    }

    /**
     * Test if the courseinfo metadata of the editing course is read correctly.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \dml_exception
     * @covers ::get_active_courseinfo_metadata
     */
    public function test_get_active_courseinfo_metadata(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $snapshot = new snapshot($course->id);
        $setstate = new \ReflectionMethod($snapshot, 'get_active_courseinfo_metadata');
        $setstate->setAccessible(true);
        // Test 1: Courseinfo sync has not been run yet. There is no active courseinfo in this course.
        [$courses, $courseinfo] = $setstate->invoke($snapshot);
        $this->assertEmpty($courses);
        $this->assertEmpty($courseinfo);

        // Test 2: After courseinfo has been synced, the moodle course is in array.
        $sync = new courseinfo_sync();
        $sync->sync_course($course->id);
        [$courses, $courseinfo] = $setstate->invoke($snapshot);
        $this->assertCount(1, $courses);
        $this->assertCount(1, $courseinfo);

        // Test 3: To emulate additional metadata added through subplugins a courseinfo entry will be added.
        $entry = $this->set_additional_courseinfoentry($course->id);
        [$courses, $courseinfo] = $setstate->invoke($snapshot);
        $this->assertCount(2, $courses);
        $this->assertCount(2, $courseinfo);
        global $DB;
        $DB->set_field('local_oer_courseinfo', 'ignored', 1, ['coursecode' => 'moodlecourse-' . $course->id]);
        [$courses, $courseinfo] = $setstate->invoke($snapshot);
        $this->assertCount(1, $courses);
        $this->assertCount(1, $courseinfo);
        $this->assertEquals('ExternalCourse', reset($courseinfo)['identifier']);
    }

    /**
     * Files can overwrite the courseinfo set global in course. So there are multiple things to test here.
     *
     * - Setting disabled, nothing changes.
     * - File enables/disables courseinfo from editor course.
     * - File enables additional courseinfo from other course where file is used.
     *
     * @covers ::get_overwritten_courseinfo_metadata
     *
     * @return void
     * @throws \Random\RandomException
     * @throws \ReflectionException
     * @throws \dml_exception
     */
    public function test_get_overwritten_courseinfo_metadata(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $contenthash = substr(hash('sha256', random_bytes(10)), 0, 40);
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $snapshot = new snapshot($course->id);
        $active = new \ReflectionMethod($snapshot, 'get_active_courseinfo_metadata');
        $active->setAccessible(true);
        $overwritten = new \ReflectionMethod($snapshot, 'get_overwritten_courseinfo_metadata');
        $overwritten->setAccessible(true);
        $sync = new courseinfo_sync();
        $sync->sync_course($course->id);
        // Test 1: Setting disabled.
        set_config('coursetofile', 0, 'local_oer');
        [$courses, $courseinfo] = $active->invoke($snapshot);
        $courseinfo = $overwritten->invoke($snapshot, $courseinfo, $contenthash, $courses);
        $this->assertCount(1, $courses);
        $this->assertCount(1, $courseinfo);
        $this->set_additional_courseinfoentry($course->id);
        [$courses, $courseinfo] = $active->invoke($snapshot);
        $courseinfo = $overwritten->invoke($snapshot, $courseinfo, $contenthash, $courses);
        $this->assertCount(2, $courses);
        $this->assertCount(2, $courseinfo);
        // Test 2: Setting enabled, no state has been overwritten yet.
        set_config('coursetofile', 1, 'local_oer');
        [$courses, $courseinfo] = $active->invoke($snapshot);
        $courseinfo = $overwritten->invoke($snapshot, $courseinfo, $contenthash, $courses);
        $this->assertCount(2, $courses);
        $this->assertCount(2, $courseinfo);
        global $DB;
        // Test 3: Setting enabled, state overwritten, remove a courseinfo at file level.
        $state = new \stdClass();
        $state->contenthash = $contenthash;
        $state->courseid = $course->id;
        $state->coursecode = 'moodlecourse-' . $course->id;
        $state->state = coursetofile::COURSETOFILE_DISABLED;
        $state->usermodified = 2;
        $state->timecreated = time();
        $state->timemodified = time();
        $state->id = $DB->insert_record('local_oer_coursetofile', $state);
        [$courses, $courseinfo] = $active->invoke($snapshot);
        $this->assertCount(2, $courses);
        $this->assertCount(2, $courseinfo);
        $courseinfo = $overwritten->invoke($snapshot, $courseinfo, $contenthash, $courses);
        $this->assertCount(1, $courseinfo);
        $this->assertEquals('ExternalCourse', reset($courseinfo)['identifier']);

        // Test 4: Setting enabled, state overwritten, disable a courseinfo, add it on file level.
        $state->state = coursetofile::COURSETOFILE_ENABLED;
        $DB->set_field('local_oer_courseinfo', 'ignored', 1, ['coursecode' => 'moodlecourse-' . $course->id]);
        $DB->update_record('local_oer_coursetofile', $state);
        [$courses, $courseinfo] = $active->invoke($snapshot);
        $this->assertCount(1, $courses);
        $this->assertCount(1, $courseinfo);
        $courseinfo = $overwritten->invoke($snapshot, $courseinfo, $contenthash, $courses);
        $this->assertCount(2, $courseinfo);

        // Test 5: Additional courseinfo from other course is added.
        $this->set_additional_courseinfoentry(7);
        unset($state->id);
        $state->courseid = 7;
        $state->coursecode = 'ExternalCourse';
        $DB->insert_record('local_oer_coursetofile', $state);
        [$courses, $courseinfo] = $active->invoke($snapshot);
        $this->assertCount(1, $courses);
        $this->assertCount(1, $courseinfo);
        $courseinfo = $overwritten->invoke($snapshot, $courseinfo, $contenthash, $courses);
        $this->assertCount(3, $courseinfo);
    }

    /**
     * Simple test for a simple function.
     * Check if the return array has all set fields.
     *
     * @covers ::extract_courseinfo_metadata
     * @covers ::add_customfields_to_snapshot
     *
     * @return void
     * @throws \ReflectionException
     * @throws \dml_exception
     */
    public function test_extract_courseinfo_metadata(): void {
        $this->resetAfterTest();

        $entry = $this->set_additional_courseinfoentry(7);
        $snapshot = new snapshot(7);
        $setstate = new \ReflectionMethod($snapshot, 'extract_courseinfo_metadata');
        $setstate->setAccessible(true);
        $metadata = $setstate->invoke($snapshot, $entry);
        $this->assertEquals($entry->coursecode, $metadata['identifier']);
        $this->assertEquals($entry->external_courseid, $metadata['courseid']);
        $this->assertEquals($entry->external_sourceid, $metadata['sourceid']);
        $this->assertEquals($entry->coursename, $metadata['coursename']);
        $this->assertEquals($entry->structure, $metadata['structure']);
        $this->assertEquals($entry->description, $metadata['description']);
        $this->assertEquals($entry->objectives, $metadata['objective']);
        $this->assertEquals($entry->organisation, $metadata['organisation']);
        $this->assertEquals($entry->language, $metadata['courselanguage']);
        $this->assertEquals($entry->lecturer, $metadata['lecturer']);
        $this->assertCount(10, $metadata);
    }

    /**
     * Add another courseinfo entry for a given courseid.
     * This function does not check if the entry is valid, it just adds the same entry for every course given.
     *
     * @param int $courseid Moodle courseid
     * @return \stdClass
     * @throws \dml_exception
     */
    private function set_additional_courseinfoentry(int $courseid): \stdClass {
        global $DB;
        $entry = new \stdClass();
        $entry->courseid = $courseid;
        $entry->coursecode = 'ExternalCourse';
        $entry->deleted = 0;
        $entry->ignored = 0;
        $entry->external_courseid = 12345;
        $entry->external_sourceid = 23456;
        $entry->coursename = 'External course for unit test';
        $entry->coursename_edited = 1;
        $entry->structure = 'Test';
        $entry->structure_edited = 0;
        $entry->description = 'Description';
        $entry->description_edited = 0;
        $entry->objectives = 'Objective';
        $entry->objectives_edited = 0;
        $entry->organisation = 'OER';
        $entry->organisation_edited = 1;
        $entry->language = 'en';
        $entry->language_edited = 0;
        $entry->lecturer = 'Christian Ortner';
        $entry->lecturer_edited = 0;
        $entry->subplugin = 'other';
        $entry->usermodified = 2;
        $time = time();
        $entry->timecreated = $time;
        $entry->timemodified = $time;
        $DB->insert_record('local_oer_courseinfo', $entry);
        return $entry;
    }
}
