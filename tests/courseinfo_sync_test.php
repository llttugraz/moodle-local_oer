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
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

use local_oer\metadata\courseinfo_sync;

/**
 * Class courseinfo_sync_test
 *
 * @coversDefaultClass \local_oer\metadata\courseinfo_sync
 */
final class courseinfo_sync_test extends \advanced_testcase {
    /**
     * A stdClass that can be inserted in courseinfo table for testing purposes.
     *
     * @param int $courseid Moodle courseid
     * @return \stdClass
     */
    private function get_courseinfo_entry(int $courseid): \stdClass {
        global $USER;
        $entry = new \stdClass();
        $entry->courseid = $courseid;
        $entry->coursecode = 'fakecoursecode';
        $entry->deleted = 0;
        $entry->ignored = 0;
        $entry->external_courseid = 123456;
        $entry->external_sourceid = 234567;
        $entry->coursename = 'Unit test fake mapping';
        $entry->coursename_edited = 0;
        $entry->structure = 'Lecture';
        $entry->structure_edited = 0;
        $entry->description = 'Created to be deleted';
        $entry->description_edited = 0;
        $entry->objectives = 'Test the class';
        $entry->objectives_edited = 0;
        $entry->organisation = 'Graz University of Technology';
        $entry->orgnaisation_edited = 0;
        $entry->language = 'en';
        $entry->language_edited = 0;
        $entry->lecturer = 'Christian Ortner';
        $entry->lecturer_edited = 0;
        $entry->customfields = null;
        $entry->subplugin = 'externalsync';
        $entry->usermodified = $USER->id;
        $entry->timecreated = time();
        $entry->timemodified = time();
        return $entry;
    }

    /**
     * Test the create, update and delete of courseinfo entries.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers \local_oer\metadata\courseinfo_sync::sync_course
     * @covers \local_oer\metadata\courseinfo_sync::compare_course
     */
    public function test_sync_course(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        global $DB;

        $course = $this->getDataGenerator()->create_course(
                [
                        'fullname' => 'Sync test course 1',
                        'shortname' => 'synccourse',
                        'summary' => 'A course created for a php unit test',
                ]
        );

        $this->assertEquals(0, $DB->count_records('local_oer_courseinfo'),
                'Nothing happened yet, table is empty.');

        // Create entry.
        $sync = new courseinfo_sync();
        $sync->sync_course($course->id);
        $this->assertEquals(1, $DB->count_records('local_oer_courseinfo'),
                'Course has been synced, so it is present in the table.');
        $record = $DB->get_record('local_oer_courseinfo', ['coursecode' => 'moodlecourse-' . $course->id]);
        $this->assertEquals('Sync test course 1', $record->coursename);
        $this->assertEquals('A course created for a php unit test', $record->description);
        $this->assertEquals('base', $record->subplugin);

        // Update entry.
        $course->fullname = 'Changed to test the sync.';
        $course->summary = 'The content of the course has been updated, so has the summary.';
        update_course($course);
        $sync->sync_course($course->id);
        $record = $DB->get_record('local_oer_courseinfo', ['coursecode' => 'moodlecourse-' . $course->id]);
        $this->assertEquals('Changed to test the sync.', $record->coursename);
        $this->assertEquals('The content of the course has been updated, so has the summary.', $record->description);
        $this->assertEquals('base', $record->subplugin);
        $this->assertEquals(0, $record->deleted);

        // Delete entries.
        // It is possible that there are multiple courseinfo entries for one moodle course.
        // But unfortunately a subplugin is required to add such data. So for the unit test lets just fake an entry.
        $entry = $this->get_courseinfo_entry($course->id);
        $DB->insert_record('local_oer_courseinfo', $entry);

        // Create another course that should remain in table.
        $course2 = $this->getDataGenerator()->create_course();
        $sync->sync_course($course2->id);
        $this->assertEquals(3, $DB->count_records('local_oer_courseinfo'), 'Three entries should be in the table');
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['coursecode' => 'fakecoursecode']));

        $sync->sync_course($course->id);
        $this->assertEquals(2, $DB->count_records('local_oer_courseinfo'), 'Two entries remain');
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['coursecode' => 'moodlecourse-' . $course->id]));
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['coursecode' => 'moodlecourse-' . $course2->id]));
        $this->assertFalse($DB->record_exists('local_oer_courseinfo', ['coursecode' => 'fakecoursecode']));

        // Delete part 2: Now mark some fields as edited in the additional courseinfo entry.
        // Instead of being deleted it will be marked as deleted.
        $entry->lecturer_edited = 1;
        $DB->insert_record('local_oer_courseinfo', $entry);
        $this->assertEquals(3, $DB->count_records('local_oer_courseinfo'), 'Three entries should be in the table');
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['coursecode' => 'fakecoursecode']));

        $sync->sync_course($course->id);
        $this->assertEquals(3, $DB->count_records('local_oer_courseinfo'), 'Two entries remain');
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['coursecode' => 'moodlecourse-' . $course->id]));
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['coursecode' => 'moodlecourse-' . $course2->id]));
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['coursecode' => 'fakecoursecode', 'deleted' => 1]));

        $sync->sync_course($course->id);
        $this->assertEquals(3, $DB->count_records('local_oer_courseinfo'), 'Two entries remain');
    }

    /**
     * Test customfield comparison function.
     *
     * @return void
     * @covers \local_oer\metadata\courseinfo_sync::compare_customfields
     */
    public function test_compare_customfields(): void {
        $this->resetAfterTest();
        $sync = new courseinfo_sync();
        [$fields, $update] = $sync->compare_customfields(null, null);
        $this->assertNull($fields);
        $this->assertFalse($update);
        [$fields, $update] = $sync->compare_customfields(['notempty' => 'someentry'], null);
        $this->assertNull($fields);
        $this->assertTrue($update);
        $old = [
                'entry' => 'notempty',
        ];
        [$fields, $update] = $sync->compare_customfields($old, $old);
        $this->assertIsString($fields);
        $this->assertEquals(json_encode($old), $fields);
        $this->assertFalse($update);
        $new = [
                'entry' => 'othervalue',
                'secondentry' => 'also exists',
        ];
        [$fields, $update] = $sync->compare_customfields($old, $new);
        $this->assertIsString($fields);
        $this->assertEquals(json_encode($new), $fields);
        $this->assertTrue($update);
    }
}
