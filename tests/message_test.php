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
 * Class message_test
 *
 * @coversDefaultClass \local_oer\message
 */
final class message_test extends \advanced_testcase {
    /**
     * Test send email to user.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::send_requirementschanged
     */
    public function test_send_requirements_changed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        require_once(__DIR__ . '/helper/testcourse.php');
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        $helper->set_files_to($course->id, 5, true);
        $helper->sync_course_info($course->id);
        $this->waitForSecond();
        $files = $DB->get_records('local_oer_elements', ['releasestate' => 1], 'id ASC');
        $elements = [];
        foreach ($files as $file) {
            $elements[$file->courseid] = $file->title;
        }
        $this->preventResetByRollback();
        unset_config('noemailever');
        $sink = $this->redirectEmails();
        \local_oer\message::send_requirementschanged($user, $elements, $course->id);
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
    }
}
