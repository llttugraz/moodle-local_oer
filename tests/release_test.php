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
 */
class release_test extends \advanced_testcase {
    /**
     * Test if the snapshot and release classes are working together.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
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

    // TODO - there are a lot of private functions - test them with reflection classes.
}
