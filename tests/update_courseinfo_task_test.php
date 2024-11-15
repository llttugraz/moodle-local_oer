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

use local_oer\task\update_courseinfo_task;

/**
 * Class update_courseinfo_task_test
 *
 * @coversDefaultClass \local_oer\task\update_courseinfo_task
 */
final class update_courseinfo_task_test extends \advanced_testcase {
    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        require_once(__DIR__ . '/helper/testcourse.php');
    }

    /**
     * Test get name.
     *
     * @return void
     * @throws \coding_exception
     * @covers ::get_name
     */
    public function test_get_name(): void {
        $task = new \local_oer\task\update_courseinfo_task();
        $this->assertEquals(get_string('updatecourseinfo', 'local_oer'), $task->get_name());
    }

    /**
     * Test basic task procedure.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::execute
     */
    public function test_execute(): void {
        global $DB;
        $this->setAdminUser();
        $helper = new \local_oer\testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $course1 = $helper->generate_testcourse($this->getDataGenerator());

        $this->assertEquals(0, $DB->count_records('local_oer_courseinfo'));
        $task = new update_courseinfo_task();
        $task->execute();
        $this->assertEquals(0, $DB->count_records('local_oer_courseinfo'));
        $helper->set_files_to($course->id, 5, false);
        $task->execute();
        $this->assertEquals(1, $DB->count_records('local_oer_courseinfo'));
        $helper->set_files_to($course1->id, 3, true);
        $task->execute();
        $this->assertEquals(2, $DB->count_records('local_oer_courseinfo'));
    }
}
