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

/**
 * Class logger_test
 *
 * @coversDefaultClass \local_oer\logger
 */
final class logger_test extends \advanced_testcase {
    /**
     * Test add log message.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::add
     */
    public function test_add(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $records = $DB->get_records(logger::LOGTABLE);
        $this->assertCount(1, $records, 'After installation of the plugin, the release time window is set.');
        \local_oer\logger::add($course->id, logger::LOGERROR, 'Unit test to test error log', 'local_oer');
        \local_oer\logger::add($course->id, logger::LOGSUCCESS, 'Unit test to test success log', 'core');
        $records = $DB->get_records(logger::LOGTABLE);
        $this->assertCount(3, $records);
    }

    /**
     * Test get log messages.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::get_logs
     */
    public function test_get_logs(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $records = \local_oer\logger::get_logs();
        $this->assertCount(1, $records, 'After installation of the plugin, the release time window is set.');
        \local_oer\logger::add($course->id, logger::LOGERROR, 'Unit test to test error log', 'local_oer');
        \local_oer\logger::add($course->id, logger::LOGERROR, 'Unit test to test error log 1', 'core');
        \local_oer\logger::add($course->id, logger::LOGERROR, 'Unit test to test error log 2', 'local_oer');
        \local_oer\logger::add($course->id, logger::LOGSUCCESS, 'Unit test to test success log', 'core');
        \local_oer\logger::add($course->id, logger::LOGSUCCESS, 'Unit test to test success log 1', 'local_oer');
        $records = \local_oer\logger::get_logs();
        $this->assertCount(6, $records);
    }
}
