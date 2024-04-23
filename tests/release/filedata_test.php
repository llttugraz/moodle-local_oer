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

namespace local_oer;

defined('MOODLE_INTERNAL') || die();

use local_oer\release\filedata;

require_once(__DIR__ . '/../helper/testcourse.php');

/**
 * Test filedata class
 *
 * @coversDefaultClass  \local_oer\release\filedata
 */
final class filedata_test extends \advanced_testcase {
    /**
     * As the other methods of the abstract class are already tested, only the differences are tested here.
     *
     * @covers ::__construct
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filedata_constructor(): void {
        $this->resetAfterTest();

        global $DB, $CFG;
        $this->setAdminUser();
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $snapshot = new snapshot($course->id);
        $helper->set_files_to($course->id, 1, true);
        $snapshot->create_snapshot_of_course_files(1);
        $files = release::get_released_files_for_course($course->id, 'v2.0.0');
        $this->assertEquals(1, count($files), 'One file should be ready for release');
        $data = $DB->get_records('local_oer_snapshot', ['releasenumber' => 1]);
        $elementinfo = reset($data);
        $filedata = new filedata($elementinfo);
        $metadata = $filedata->get_array();
        $this->assertCount(16, $metadata, '12 default fields and 4 additional.');
        $this->assertArrayHasKey('source', $metadata);
        $this->assertArrayHasKey('mimetype', $metadata);
        $this->assertArrayHasKey('filesize', $metadata);
        $this->assertArrayHasKey('filecreationtime', $metadata);
        $decomposed = identifier::decompose($elementinfo->identifier);
        $publicurl = $CFG->wwwroot . '/pluginfile.php/' .
                \context_course::instance($course->id)->id . '/local_oer/public/' .
                $elementinfo->id . '/' . $decomposed->value;
        $typedata = json_decode($elementinfo->typedata);
        $this->assertEquals($publicurl, $metadata['source']);
        $this->assertEquals($typedata->mimetype, $metadata['mimetype']);
        $this->assertEquals($typedata->filesize, $metadata['filesize']);
        $this->assertEquals($elementinfo->timecreated, $metadata['filecreationtime']);
    }
}
