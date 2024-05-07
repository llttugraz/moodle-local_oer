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

require_once(__DIR__ . '/../helper/testcourse.php');

/**
 * Class legacy_test
 *
 * @coversDefaultClass \local_oer\release\legacy
 */
final class legacy_test extends \advanced_testcase {
    /**
     * Test construction of legacy data structure.
     *
     * @covers ::__construct
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_legacy_constructor(): void {
        $this->resetAfterTest();

        global $DB, $CFG;
        $this->setAdminUser();
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $snapshot = new snapshot($course->id);
        $helper->set_files_to($course->id, 1, true);
        $snapshot->create_snapshot_of_course_files(1);
        $files = release::get_released_files_for_course($course->id, 'v1.0.0');
        $this->assertEquals(1, count($files), 'One file should be ready for release');
        $additionaldata = [
                'semester' => 'summer',
                'year' => '2024',
        ];
        $data = $DB->get_records('local_oer_snapshot', ['releasenumber' => 1]);
        $elementinfo = reset($data);
        $elementinfo->additionaldata = json_encode($additionaldata);
        $filedata = new release\legacy($elementinfo);
        $metadata = $filedata->get_array();
        $this->assertCount(18, $metadata, '16 default fields and 2 additional');
        $this->assertArrayHasKey('title', $metadata);
        $this->assertArrayHasKey('contenthash', $metadata);
        $this->assertArrayHasKey('fileurl', $metadata);
        $this->assertArrayHasKey('abstract', $metadata);
        $this->assertArrayHasKey('license', $metadata);
        $this->assertArrayHasKey('context', $metadata);
        $this->assertArrayHasKey('resourcetype', $metadata);
        $this->assertArrayHasKey('language', $metadata);
        $this->assertArrayHasKey('persons', $metadata);
        $this->assertArrayHasKey('tags', $metadata);
        $this->assertArrayHasKey('mimetype', $metadata);
        $this->assertArrayHasKey('filesize', $metadata);
        $this->assertArrayHasKey('filecreationtime', $metadata);
        $this->assertArrayHasKey('timereleased', $metadata);
        $this->assertArrayHasKey('classification', $metadata);
        $this->assertArrayHasKey('courses', $metadata);
        $this->assertArrayHasKey('semester', $metadata);
        $this->assertArrayHasKey('year', $metadata);
        $decomposed = identifier::decompose($elementinfo->identifier);
        $publicurl = $CFG->wwwroot . '/pluginfile.php/' .
                \context_course::instance($course->id)->id . '/local_oer/public/' .
                $elementinfo->id . '/' . $decomposed->value;
        $typedata = json_decode($elementinfo->typedata);
        $this->assertEquals($decomposed->value, $metadata['contenthash']);
        $this->assertEquals($publicurl, $metadata['fileurl']);
        $this->assertEquals($typedata->mimetype, $metadata['mimetype']);
        $this->assertEquals($typedata->filesize, $metadata['filesize']);
        $this->assertEquals($elementinfo->timecreated, $metadata['filecreationtime']);
    }
}
