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
 * Testfile for the courseinfo and sync classes
 *
 * @coversDefaultClass \local_oer\zipper
 */
final class zipper_test extends \advanced_testcase {
    /**
     * Test if files are correctly added to one or multiple filepackages.
     *
     * TODO: test for multiple packages.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::separate_files_to_packages
     */
    public function test_separate_files_to_packages(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        // MDL-0 TODO: test is dependent from subplugin.
        set_config('enabledmodplugins', 'resource', 'local_oer');
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $zipper = new zipper();
        $snapshot = new snapshot($course->id);
        [$packages, $info] = $zipper->separate_files_to_packages($course->id, true);
        $this->assertTrue(empty($packages));
        $this->assertEquals(0, $info['general']['packages']);
        $this->assertEquals(0, $info['general']['fullsize']);
        $size = $helper->set_files_to($course->id, 3, true);
        $snapshot->create_snapshot_of_course_files(1);
        [$packages, $info] = $zipper->separate_files_to_packages($course->id, true);
        $this->assertEquals(1, count($packages), 'The amount of packages');
        $this->assertEquals(0, $info['general']['packages'], 'Packages start counting at 0, so 0 should be the correct value');
        $this->assertEquals($size, $info['general']['fullsize'], 'The package size should match the filesizes');

        // MDL-0 TODO: test package separation with lots of big files - look into the old unit test..
    }

    /**
     * Test if a file package is zipped and the zip file is in temp folder.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::compress_file_package
     * @covers ::prepare_files_to_zip
     * @covers ::create_metadata_json_temp
     */
    public function test_compress_file_package(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        // MDL-0 TODO: test is dependent from subplugin.
        set_config('enabledmodplugins', 'resource', 'local_oer');

        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $size = $helper->set_files_to($course->id, 1, true);
        $zipper = new zipper();
        $snapshot = new snapshot($course->id);
        $snapshot->create_snapshot_of_course_files(1);
        [$packages, $info] = $zipper->separate_files_to_packages($course->id, true);
        $this->assertEquals(1, count($packages), 'The amount of packages, should be 1');
        $zipfile = $zipper->compress_file_package($course->id, reset($packages));
        $this->assertNotFalse($zipfile, 'Should be a path to a zipfile');
        $this->assertTrue(file_exists($zipfile), 'There should be a ZIP file in the temp directory');

        // MDL-0 TODO: add some variations?...
    }
}
