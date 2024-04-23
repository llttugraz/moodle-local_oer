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
 * @copyright  2017-2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

use local_oer\time\time_settings;

/**
 * Class timewindow_testcase
 *
 * @coversDefaultClass \local_oer\time\time_settings
 */
final class timewindow_test extends \advanced_testcase {
    /**
     * Test daily release
     *
     * @return void
     * @throws \dml_exception
     * @covers ::set_next_upload_window
     */
    public function test_daily_upload(): void {
        $this->resetAfterTest(true);
        $time = '13:00';
        set_config(time_settings::CONF_RELEASETIME, time_settings::DAY, 'local_oer');
        set_config(time_settings::CONF_RELEASEHOUR, $time, 'local_oer');
        set_config(time_settings::CONF_CUSTOMDATES, '', 'local_oer');
        time_settings::set_next_upload_window();
        $updatetime = get_config('local_oer', time_settings::RELEASETIME);
        $compare = new \DateTime("tomorrow $time");
        $this->assertEquals($compare->getTimestamp(), $updatetime);
    }

    /**
     * Test weekly release
     *
     * @return void
     * @throws \dml_exception
     * @covers ::set_next_upload_window
     */
    public function test_weekly_upload(): void {
        $this->resetAfterTest(true);
        $time = '11:00';
        set_config(time_settings::CONF_RELEASETIME, time_settings::WEEK, 'local_oer');
        set_config(time_settings::CONF_RELEASEHOUR, $time, 'local_oer');
        set_config(time_settings::CONF_CUSTOMDATES, '', 'local_oer');
        time_settings::set_next_upload_window();
        $updatetime = get_config('local_oer', time_settings::RELEASETIME);
        $compare = new \DateTime("Monday next week $time");
        $this->assertEquals($compare->getTimestamp(), $updatetime);
    }

    /**
     * Test monthly release
     *
     * @return void
     * @throws \dml_exception
     * @covers ::set_next_upload_window
     */
    public function test_monthly_upload(): void {
        $this->resetAfterTest(true);
        $time = '00:00';
        set_config(time_settings::CONF_RELEASETIME, time_settings::MONTH, 'local_oer');
        set_config(time_settings::CONF_RELEASEHOUR, $time, 'local_oer');
        set_config(time_settings::CONF_CUSTOMDATES, '', 'local_oer');
        time_settings::set_next_upload_window();
        $updatetime = get_config('local_oer', time_settings::RELEASETIME);
        $compare = new \DateTime("first day of next month $time");
        $this->assertEquals($compare->getTimestamp(), $updatetime);
    }

    /**
     * Test custom release
     *
     * @return void
     * @throws \dml_exception
     * @covers ::set_next_upload_window
     */
    public function test_custom_upload(): void {
        $this->resetAfterTest(true);
        $time1 = '23:59';
        $time2 = '00:00';
        $custom1 = '31.12'; // This test always results in current year setting.
        $custom2 = '01.01'; // This test always results in next year setting.
        // JFYI: UnitTests uses Australia/Perth timezone.
        $year1 = date("Y");
        $year2 = date("Y") + 1;
        $compare1 = new \DateTime();
        $compare1->setDate($year1, 12, 31);
        $compare1->setTime(23, 59);
        set_config(time_settings::CONF_RELEASETIME, time_settings::CUSTOM, 'local_oer');
        set_config(time_settings::CONF_RELEASEHOUR, $time1, 'local_oer');
        set_config(time_settings::CONF_CUSTOMDATES, $custom1, 'local_oer');
        time_settings::set_next_upload_window();
        $updatetime = get_config('local_oer', time_settings::RELEASETIME);
        $this->assertEquals($compare1->getTimestamp(), $updatetime);
        set_config(time_settings::CONF_RELEASETIME, time_settings::CUSTOM, 'local_oer');
        set_config(time_settings::CONF_RELEASEHOUR, $time2, 'local_oer');
        set_config(time_settings::CONF_CUSTOMDATES, $custom2, 'local_oer');
        $compare2 = new \DateTime();
        $compare2->setDate($year2, 1, 1);
        $compare2->setTime(00, 00);
        time_settings::set_next_upload_window();
        $updatetime = get_config('local_oer', time_settings::RELEASETIME);
        $this->assertEquals($compare2->getTimestamp(), $updatetime);
    }

    /**
     * Test if the difference is calculated correctly.
     *
     * @return void
     * @throws \coding_exception
     * @covers ::format_difference
     */
    public function test_format_difference(): void {
        $this->resetAfterTest(true);
        $now = new \DateTime('now');
        $week = clone $now;
        $week->add(new \DateInterval('P7D'));

        $result = time_settings::format_difference($week->getTimestamp() - $now->getTimestamp());
        $this->assertEquals('7 days, 0 hours and 0 minutes', $result, 'Eventually the language string has changed.');
    }

    /**
     * Test get timeslot output.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::get_timeslot_output
     */
    public function test_get_timeslot_output(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once(__DIR__ . '/helper/testcourse.php');
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $helper->set_files_to($course->id, 5, true);
        $this->assertIsString(time_settings::get_timeslot_output($course->id));
    }
}
