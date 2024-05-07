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

use local_oer\release\externaldata;

require_once(__DIR__ . '/../helper/testcourse.php');

/**
 * Test externaldata class
 *
 * @coversDefaultClass \local_oer\release\externaldata
 */
final class externaldata_test extends \advanced_testcase {
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
    public function test_externaldata_constructor(): void {
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
        $externaldata = new externaldata($elementinfo);
        $metadata = $externaldata->get_array();
        $this->assertCount(16, $metadata, '12 default fields and 4 additional.');
        $typedata = json_decode($elementinfo->typedata);
        // The fields are from a file because the testcourse only provides files at this point.
        $this->assertEquals($typedata->mimetype, $metadata['mimetype']);
        $this->assertEquals($typedata->filesize, $metadata['filesize']);
        $this->assertEquals($typedata->filecreationtime, $metadata['filecreationtime']);
        $this->assertEquals($typedata->source, $metadata['source']);

        $elementinfo = reset($data);
        $elementinfo->typedata = null;
        $externaldata = new externaldata($elementinfo);
        $metadata = $externaldata->get_array();
        $this->assertCount(12, $metadata, '12 default fields as no extra fields are set.');
        $this->assertArrayHasKey('identifier', $metadata);
        $this->assertArrayHasKey('title', $metadata);
        $this->assertArrayHasKey('license', $metadata);
        $this->assertArrayNotHasKey('mimetype', $metadata);
        $this->assertArrayNotHasKey('filesize', $metadata);
        $this->assertArrayNotHasKey('filecreationtime', $metadata);
        $this->assertArrayNotHasKey('source', $metadata);
    }
}
