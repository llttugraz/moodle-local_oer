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
 * Class snapshot_test
 *
 * @coversDefaultClass \local_oer\snapshot
 */
class snapshot_test extends \advanced_testcase {
    /**
     * Test if file snapshots are correctly taken from a testcourse.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::create_snapshot_of_course_files
     */
    public function test_create_snapshot_of_course_files() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $this->assertTrue($DB->record_exists('local_oer_courseinfo', ['courseid' => $course->id]),
                          'There should be at least one courseinfo entry for testcourse');
        $this->assertEmpty($DB->get_records('local_oer_snapshot'));
        $snapshot = new snapshot($course->id);
        $snapshot->create_snapshot_of_course_files();
        $this->assertEmpty($DB->get_records('local_oer_snapshot'), 'No files are set for release yet.');
        $helper->set_files_to($course->id, 1, true);
        $snapshot->create_snapshot_of_course_files();
        $snapshot->create_snapshot_of_course_files();
        $this->assertEquals(1, $DB->count_records('local_oer_snapshot'),
                            'Although the release is called 2 times, only one file should be released because nothing changed');
        $helper->set_files_to($course->id, 2, true);
        $snapshot->create_snapshot_of_course_files();
        $this->assertEquals(2, $DB->count_records('local_oer_snapshot'), 'Two files have been released.');
    }
}
