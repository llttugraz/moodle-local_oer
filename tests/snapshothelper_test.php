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

use local_oer\helper\snapshothelper;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helper/testcourse.php');

/**
 * Class snapshothelper_test
 *
 * @coversDefaultClass \local_oer\helper\snapshothelper
 */
class snapshothelper_test extends \advanced_testcase {
    /**
     * Test the helper to create snapshots.
     *
     * As this function uses the snapshot class to create snapshots, the detailed snapshot tests could be found there.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers \local_oer\helper\snapshothelper::create_snapshots_of_all_active_courses
     */
    public function test_create_snapshots_of_all_active_courses() {
        $this->resetAfterTest();

        global $DB;
        $this->setAdminUser();

        $testcourse = new testcourse();

        // Every testcourse has already 5 resources with files generated.
        $this->getDataGenerator()->create_course();
        $course1 = $testcourse->generate_testcourse($this->getDataGenerator());
        $this->getDataGenerator()->create_course();
        $course2 = $testcourse->generate_testcourse($this->getDataGenerator());
        $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_course();
        $course3 = $testcourse->generate_testcourse($this->getDataGenerator());
        $this->getDataGenerator()->create_course();
        $course4 = $testcourse->generate_testcourse($this->getDataGenerator());
        $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_course();
        $testcourse->sync_course_info($course1->id);
        $testcourse->sync_course_info($course3->id);
        $testcourse->sync_course_info($course4->id);

        snapshothelper::create_snapshots_of_all_active_courses();
        $this->assertEquals(0, $DB->count_records('local_oer_snapshot'));

        $testcourse->set_files_to($course3->id, 5, false);
        $testcourse->set_files_to($course3->id, 3, true);
        snapshothelper::create_snapshots_of_all_active_courses();
        $this->assertEquals(3, $DB->count_records('local_oer_snapshot'));

        $testcourse->set_files_to($course4->id, 4, false);
        $testcourse->set_files_to($course4->id, 2, true);
        snapshothelper::create_snapshots_of_all_active_courses();
        $this->assertEquals(5, $DB->count_records('local_oer_snapshot'));

        $testcourse->set_files_to($course1->id, 5, true);
        snapshothelper::create_snapshots_of_all_active_courses();
        $this->assertEquals(10, $DB->count_records('local_oer_snapshot'));

        // Removing the release state of files does not remove old releases (intended behaviour).
        $testcourse->set_files_to($course4->id, 5, false);
        snapshothelper::create_snapshots_of_all_active_courses();
        $this->assertEquals(10, $DB->count_records('local_oer_snapshot'));

        $testcourse->set_files_to($course2->id, 5, false);
        $testcourse->set_files_to($course2->id, 3, true);
        snapshothelper::create_snapshots_of_all_active_courses();
        $this->assertEquals(10, $DB->count_records('local_oer_snapshot'), 'Courseinfo missing, will not be added.');
    }

    /**
     * Test get latest snapshot timestamp.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers \local_oer\helper\snapshothelper::get_latest_snapshot_timestamp
     */
    public function test_get_latest_snapshot_timestamp() {
        $this->resetAfterTest();
        global $DB;
        $this->setAdminUser();

        $latest = snapshothelper::get_latest_snapshot_timestamp();
        $this->assertEquals(0, $latest, 'Returns zero if no records are in the table');

        // Create a bunch of entries in snapshot table.
        $testcourse = new testcourse();
        $course1 = $testcourse->generate_testcourse($this->getDataGenerator());
        $course2 = $testcourse->generate_testcourse($this->getDataGenerator());
        $course3 = $testcourse->generate_testcourse($this->getDataGenerator());
        $course4 = $testcourse->generate_testcourse($this->getDataGenerator());
        $testcourse->sync_course_info($course1->id);
        $testcourse->sync_course_info($course2->id);
        $testcourse->sync_course_info($course3->id);
        $testcourse->sync_course_info($course4->id);
        $testcourse->set_files_to($course1->id, 5, true);
        $testcourse->set_files_to($course2->id, 5, true);
        $testcourse->set_files_to($course3->id, 5, true);
        $testcourse->set_files_to($course4->id, 5, true);
        $this->assertEquals(0, $DB->count_records('local_oer_snapshot'));
        snapshothelper::create_snapshots_of_all_active_courses();
        $this->assertEquals(20, $DB->count_records('local_oer_snapshot'));

        // Pick one record and update its timestamps, so it is definitely the latest snapshot.
        // In the real environment this will most probably the record with the highest id.
        $records = $DB->get_records('local_oer_snapshot');
        $records = array_values($records);
        $select = rand(0, count($records) - 1);
        $record = $records[$select];
        $record->timecreated = time() + 1;
        $record->timemodified = time() + 1;
        $DB->update_record('local_oer_snapshot', $record);
        $latest = snapshothelper::get_latest_snapshot_timestamp();
        $this->assertEquals($record->timecreated, $latest);
    }
}
