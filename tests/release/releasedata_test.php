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

use local_oer\release\releasedata;

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
final class releasedata_test extends \advanced_testcase {
    /**
     * Test if metadata contains all fields after release.
     *
     * @covers ::__construct
     * @covers ::get_array
     * @covers ::prepare_license
     * @covers ::prepare_classification_fields
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_array(): void {
        $this->resetAfterTest();

        global $DB;
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
        $testdummy = new testdummy($elementinfo);
        $metadata = $testdummy->get_array();
        $this->assertCount(12, $metadata, 'Only basic information in this test.');
        $this->assertArrayHasKey('title', $metadata);
        $this->assertArrayHasKey('identifier', $metadata);
        $this->assertArrayHasKey('abstract', $metadata);
        $this->assertArrayHasKey('license', $metadata);
        $this->assertArrayHasKey('context', $metadata);
        $this->assertArrayHasKey('resourcetype', $metadata);
        $this->assertArrayHasKey('language', $metadata);
        $this->assertArrayHasKey('persons', $metadata);
        $this->assertArrayHasKey('tags', $metadata);
        $this->assertArrayHasKey('timereleased', $metadata);
        $this->assertArrayHasKey('classification', $metadata);
        $this->assertArrayHasKey('courses', $metadata);
        $this->assertEquals($elementinfo->title, $metadata['title']);
        $this->assertEquals($elementinfo->identifier, $metadata['identifier']);
        $this->assertEquals($elementinfo->description, $metadata['abstract']);
        $this->assertCount(3, $metadata['license']);
        $this->assertEquals($elementinfo->license, $metadata['license']['shortname']);
        $this->assertEquals('Higher Education', $metadata['context']);
        $this->assertEquals('No selection', $metadata['resourcetype']);
        $this->assertEquals($elementinfo->language, $metadata['language']);
        $persons = json_decode($elementinfo->persons);
        $this->assertCount(2, $metadata['persons']);
        $this->assertEquals($persons->persons, $metadata['persons']);
        $this->assertEquals([], $metadata['tags']);
        $this->assertEquals($elementinfo->timecreated, $metadata['timereleased']);
        $this->assertEquals([], $metadata['classification']);
        $courses = json_decode($elementinfo->coursemetadata);
        $this->assertEquals($courses, $metadata['courses']);
        // Add some additional data to the snapshot.
        $elementinfo->additionaldata = json_encode([
                'testkey' => 'testdata',
                'identifier' => 'should not be overwritten', // This will not be added.
                'additional' => 'additional',
        ]);
        set_config('uselicensereplacement', 1, 'local_oer');
        set_config('licensereplacement', "cc-4.0 =>CC BY 4.0\r\nabc", 'local_oer');
        $testdummy = new testdummy($elementinfo);
        $metadata = $testdummy->get_array();
        $this->assertCount(14, $metadata, 'Two additional fields.');
        $this->assertArrayHasKey('testkey', $metadata);
        $this->assertArrayHasKey('additional', $metadata);
        $this->assertEquals($elementinfo->identifier, $metadata['identifier'], 'Should not be overwritten');
        $this->assertEquals('testdata', $metadata['testkey']);
        $this->assertEquals('additional', $metadata['additional']);
        $this->assertCount(3, $metadata['license']);
        $this->assertEquals('CC BY 4.0', $metadata['license']['shortname']);
    }
}
