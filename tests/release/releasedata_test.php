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
 * @copyright  2024 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace release;

use local_oer\release;
use local_oer\release\releasedata;
use local_oer\snapshot;
use local_oer\testcourse;

require_once(__DIR__ . '/../helper/testcourse.php');

/**
 * Instantiate an unaltered version of abstract class releasedata.
 */
class testdummy extends releasedata {
    // Does not extend parent.
}

/**
 * Test for the releasedata data structure.
 *
 * @coversDefaultClass \local_oer\release\releasedata
 */
class releasedata_test extends \advanced_testcase {
    /**
     * Test if metadata contains all fields after release.
     *
     * @covers ::__construct
     * @covers ::get_array
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_array() {
        $this->resetAfterTest();

        // TODO: test is dependent from subplugin.
        set_config('enabledmodplugins', 'resource', 'local_oer');

        global $DB;
        $this->setAdminUser();
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $snapshot = new snapshot($course->id);
        $helper->set_files_to($course->id, 1, true);
        $snapshot->create_snapshot_of_course_files(1);
        $files = release::get_released_files_for_course($course->id);
        $this->assertEquals(1, count($files), 'One file should be ready for release');
        $data = $DB->get_records('local_oer_snapshot', ['releasenumber' => 1]);
        $elementinfo = reset($data);
        $testdummy = new testdummy($elementinfo);
        $metadata = $testdummy->get_array();
        $this->assertCount(12, $metadata, 'Only basic information in this test.');

    }
}