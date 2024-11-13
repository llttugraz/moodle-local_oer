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

use local_oer\metadata\coursecustomfield;
use local_oer\metadata\courseinfo_sync;

/**
 * Class coursecustomfield_test
 *
 * @coversDefaultClass \local_oer\metadata\coursecustomfield
 */
final class coursecustomfield_test extends \advanced_testcase {
    /**
     * Data that is generated in setup method.
     *
     * @var null
     */
    private $data = [];

    /**
     * Prepare two courses and a set of customfields with some values for those courses to use in the tests.
     *
     * @return void
     * @throws \coding_exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->data['course1'] = $course;
        $course2 = $this->getDataGenerator()->create_course();
        $this->data['course2'] = $course2;
        $customcat1 = $this->getDataGenerator()->create_custom_field_category(['name' => 'First Category']);
        $this->data['cat1'] = $customcat1;
        $customcat2 = $this->getDataGenerator()->create_custom_field_category(['name' => 'Second Category']);
        $this->data['cat2'] = $customcat2;
        $customcat3 = $this->getDataGenerator()->create_custom_field_category(['name' => 'Third Category']);
        $this->data['cat3'] = $customcat3;
        $field1cat1 = $this->getDataGenerator()->create_custom_field(
                [
                        'name' => 'semester',
                        'shortname' => 'sem',
                        'type' => 'text',
                        'categoryid' => $customcat1->get('id'),
                        'configdata' => [
                                'visibility' => 0,
                                'defaultvalue' => 'nosemester',
                        ],
                ]);
        $this->data['field1cat1'] = $field1cat1;
        $field2cat1 = $this->getDataGenerator()->create_custom_field(
                [
                        'name' => 'additionaltextarea',
                        'shortname' => 'ata',
                        'type' => 'textarea',
                        'categoryid' => $customcat1->get('id'),
                        'configdata' => [
                                'visibility' => 1,
                                'defaultvalue' => 'emptytext',
                        ],
                ]);
        $this->data['field2cat1'] = $field2cat1;
        $field3cat1 = $this->getDataGenerator()->create_custom_field(
                [
                        'name' => 'checkboxfield',
                        'shortname' => 'cf',
                        'type' => 'checkbox',
                        'categoryid' => $customcat1->get('id'),
                        'configdata' => [
                                'visibility' => 2,
                                'defaultvalue' => 0,
                        ],
                ]);
        $this->data['field3cat1'] = $field3cat1;
        $field4cat1 = $this->getDataGenerator()->create_custom_field(
                [
                        'name' => 'dateofsomething',
                        'shortname' => 'dos',
                        'type' => 'date',
                        'categoryid' => $customcat1->get('id'),
                        'configdata' => [
                                'visibility' => 0,
                            // Date does not have a default value.
                        ],
                ]);
        $this->data['field4cat1'] = $field4cat1;
        $field5cat1 = $this->getDataGenerator()->create_custom_field(
                [
                        'name' => 'selectfield',
                        'shortname' => 'sf',
                        'type' => 'select',
                        'categoryid' => $customcat1->get('id'),
                        'configdata' => [
                                'visibility' => 1,
                                'defaultvalue' => 'def',
                                'options' => "abc\r\ndef\r\nghi",
                        ],
                ]);
        $this->data['field5cat1'] = $field5cat1;
        $field1cat2 = $this->getDataGenerator()->create_custom_field(
                [
                        'name' => 'dateofanotherthing',
                        'shortname' => 'doat',
                        'type' => 'date',
                        'categoryid' => $customcat2->get('id'),
                        'configdata' => [
                                'visibility' => 2,
                        ],
                ]);
        $this->data['field1cat2'] = $field1cat2;

        $handler = \core_course\customfield\course_handler::create();
        $data = new \stdClass();
        $data->id = $course->id;
        $data->customfield_sem = 'WS';
        $data->customfield_ata_editor = [
                'text' => '<p dir="ltr" style="text-align:left;">' .
                        'Textarea <a href="localhost">with</a> <img src="@@PLUGINFILE@@">a lots of text...</p>',
                'format' => '1',
                'itemid' => 0,
        ];
        $data->customfield_cf = 1;
        $data->customfield_dos = 1657152000;
        $data->customfield_doat = 1671840000;
        $data->customfield_sf = 3; // Should be ghi.
        $handler->instance_form_save($data);
        $data = new \stdClass();
        $data->id = $course2->id;
        $data->customfield_sem = 'SS';
        $handler->instance_form_save($data);
    }

    /**
     * Test different settings variations.
     * Three settings can have an impact.
     *
     * local_oer | coursecustomfields : enable/disable customfields in metadata
     * local_oer | coursecustomfieldsvisibility : restrict the metadata based on the customfields visibility
     *   - everyone (despite the name is this the most strict setting for oer, only fields that are visible for everyone are added)
     *   - teachers (fields for teachers and everyone are added)
     *   - nobody   (all fields are added, also the hidden ones)
     * local_oer | coursecustomfieldsignored : disable fields that should not be added to metadata
     *
     * Test 1: coursecustomfields disabled
     *   expected: return value is empty array
     * Test 2: coursecustomfields enabled, visibility 'nobody', nothing on ignore
     *   expected: all fields are added as defined in ::setUp
     * Test 3: coursecustomfields enabled, visibility 'nobody', field2cat1 and field4cat1 on ignore
     *   expected: all fields except the ignored
     * Test 4: coursecustomfields enabled, visibility 'teachers', nothing on ignore
     *   expected: fields with visibility nobody are not added anymore
     * Test 5: coursecustomfields enabled, visibility 'teachers', field2cat1 and field4cat1 on ignore
     *   expected: fields with visibility nobody and ignored fields are not added anymore
     * Test 6: coursecustomfields enabled, visibility 'everyone', nothing on ignore
     *   expected: only fields with visibility everyone are added
     * Test 7: coursecustomfields enabled, visibility 'everyone', field2cat1 and field4cat1 on ignore
     *   expected: only fields with visibility everyone are added,
     *             ignored fields have both a visibility lower than everyone, so same result as test 6
     *
     * Every test is run with the second parameter set to true and false.
     * This test will only check the differences as the detailed versions already
     * have been tested (below) for the generating and loading of customfields.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::get_course_customfields_with_applied_config
     */
    public function test_get_course_customfields_with_applied_config(): void {
        $courseid = $this->data['course1']->id;
        $data = $this->data;

        $everyone = \core_course\customfield\course_handler::VISIBLETOALL;
        $teachers = \core_course\customfield\course_handler::VISIBLETOTEACHERS;
        $nobody = \core_course\customfield\course_handler::NOTVISIBLE;

        $field2cat1 = $data['cat1']->get('id') . ':' . $data['field2cat1']->get('id');
        $field4cat1 = $data['cat1']->get('id') . ':' . $data['field4cat1']->get('id');
        $ignore = $field2cat1 . ',' . $field4cat1;

        // Test 1.
        $this->set_settings_for_test($courseid, 0, $nobody, '');

        $system = coursecustomfield::get_course_customfields_with_applied_config($courseid, false);
        $this->assertEquals([], $system);
        $this->assertIsArray($system);
        $oer = coursecustomfield::get_course_customfields_with_applied_config($courseid, true);
        $this->assertEquals([], $oer);
        $this->assertIsArray($oer);

        // Test 2.
        $this->set_settings_for_test($courseid, 1, $nobody, '');
        $system = coursecustomfield::get_course_customfields_with_applied_config($courseid, false);
        $this->assert_fields_course_one($system);
        $oer = coursecustomfield::get_course_customfields_with_applied_config($courseid, true);
        $this->assert_fields_course_one($oer);

        // Test 2 also tests for all values. The next tests will only compare field shortnames and count.
        // Test 3.
        $this->set_settings_for_test($courseid, 1, $nobody, $ignore);
        $system = coursecustomfield::get_course_customfields_with_applied_config($courseid, false);
        $this->assertCount(3, $system[0]['fields']);
        $this->assertEquals('sem', $system[0]['fields'][0]['shortname']);
        $this->assertEquals('cf', $system[0]['fields'][1]['shortname']);
        $this->assertEquals('sf', $system[0]['fields'][2]['shortname']);
        $this->assertEquals('doat', $system[1]['fields'][0]['shortname']);
        $oer = coursecustomfield::get_course_customfields_with_applied_config($courseid, true);
        $this->assertCount(3, $oer[0]['fields']);
        $this->assertEquals('sem', $oer[0]['fields'][0]['shortname']);
        $this->assertEquals('cf', $oer[0]['fields'][1]['shortname']);
        $this->assertEquals('sf', $oer[0]['fields'][2]['shortname']);
        $this->assertEquals('doat', $oer[1]['fields'][0]['shortname']);

        // Test 4.
        $this->set_settings_for_test($courseid, 1, $teachers, '');
        $system = coursecustomfield::get_course_customfields_with_applied_config($courseid, false);
        $this->assertCount(3, $system[0]['fields']);
        $this->assertEquals('ata', $system[0]['fields'][0]['shortname']);
        $this->assertEquals('cf', $system[0]['fields'][1]['shortname']);
        $this->assertEquals('sf', $system[0]['fields'][2]['shortname']);
        $this->assertEquals('doat', $system[1]['fields'][0]['shortname']);
        $oer = coursecustomfield::get_course_customfields_with_applied_config($courseid, true);
        $this->assertCount(3, $oer[0]['fields']);
        $this->assertEquals('ata', $oer[0]['fields'][0]['shortname']);
        $this->assertEquals('cf', $oer[0]['fields'][1]['shortname']);
        $this->assertEquals('sf', $oer[0]['fields'][2]['shortname']);
        $this->assertEquals('doat', $oer[1]['fields'][0]['shortname']);

        // Test 5.
        $this->set_settings_for_test($courseid, 1, $teachers, $ignore);
        $system = coursecustomfield::get_course_customfields_with_applied_config($courseid, false);
        $this->assertCount(2, $system[0]['fields']);
        $this->assertEquals('cf', $system[0]['fields'][0]['shortname']);
        $this->assertEquals('sf', $system[0]['fields'][1]['shortname']);
        $this->assertEquals('doat', $system[1]['fields'][0]['shortname']);
        $oer = coursecustomfield::get_course_customfields_with_applied_config($courseid, true);
        $this->assertCount(2, $oer[0]['fields']);
        $this->assertEquals('cf', $oer[0]['fields'][0]['shortname']);
        $this->assertEquals('sf', $oer[0]['fields'][1]['shortname']);
        $this->assertEquals('doat', $oer[1]['fields'][0]['shortname']);

        // Test 6.
        $this->set_settings_for_test($courseid, 1, $everyone, '');
        $system = coursecustomfield::get_course_customfields_with_applied_config($courseid, false);
        $this->assertCount(1, $system[0]['fields']);
        $this->assertEquals('cf', $system[0]['fields'][0]['shortname']);
        $this->assertEquals('doat', $system[1]['fields'][0]['shortname']);
        $oer = coursecustomfield::get_course_customfields_with_applied_config($courseid, true);
        $this->assertCount(1, $oer[0]['fields']);
        $this->assertEquals('cf', $oer[0]['fields'][0]['shortname']);
        $this->assertEquals('doat', $oer[1]['fields'][0]['shortname']);

        // Test 7.
        $this->set_settings_for_test($courseid, 1, $everyone, $ignore);
        $system = coursecustomfield::get_course_customfields_with_applied_config($courseid, false);
        $this->assertCount(1, $system[0]['fields']);
        $this->assertEquals('cf', $system[0]['fields'][0]['shortname']);
        $this->assertEquals('doat', $system[1]['fields'][0]['shortname']);
        $oer = coursecustomfield::get_course_customfields_with_applied_config($courseid, true);
        $this->assertCount(1, $oer[0]['fields']);
        $this->assertEquals('cf', $oer[0]['fields'][0]['shortname']);
        $this->assertEquals('doat', $oer[1]['fields'][0]['shortname']);
    }

    /**
     * Helper to prevent code duplication.
     *
     * @param int $courseid Moodle courseid
     * @param int $enable Checkbox value, 1 or 0
     * @param int $visibility Visibility as defined for customfields
     * @param string $ignored Setting of ignored customfields Format catid:fieldid, ...
     * @return void
     * @throws \dml_exception
     */
    private function set_settings_for_test(int $courseid, int $enable, int $visibility, string $ignored): void {
        set_config('coursecustomfields', $enable, 'local_oer');
        set_config('coursecustomfieldsvisibility', $visibility, 'local_oer');
        set_config('coursecustomfieldsignored', $ignored, 'local_oer');
        $syncer = new courseinfo_sync();
        $syncer->sync_course($courseid);
    }

    /**
     * Test if all fields of the courses are loaded with the correct values to store in oer plugin.
     *
     * @return void
     * @throws \Exception
     * @covers ::get_course_customfields
     */
    public function test_get_course_customfields(): void {
        $data = $this->data;
        // Test for course 1. Course 1 has set all fields.
        $fields = coursecustomfield::get_course_customfields($data['course1']->id);
        $this->assert_fields_course_one($fields);

        // Test for course 2. Course 2 has only set semester. Other fields should have default value.
        $fields2 = coursecustomfield::get_course_customfields($data['course2']->id);
        $this->assertCount(3, $fields2, 'There are three categories set up for the platform');

        $this->assertEquals((int) $data['cat1']->get('id'), $fields2[0]['id']);
        $this->assertEquals('First Category', $fields2[0]['name']);
        $this->assertCount(5, $fields2[0]['fields']);

        $this->assertEquals((int) $data['field1cat1']->get('id'), $fields2[0]['fields'][0]['id']);
        $this->assertEquals('sem', $fields2[0]['fields'][0]['shortname']);
        $this->assertEquals('semester', $fields2[0]['fields'][0]['fullname']);
        $this->assertEquals('text', $fields2[0]['fields'][0]['type']);
        $this->assertEquals(0, $fields2[0]['fields'][0]['visibility']);
        $this->assertEquals('SS', $fields2[0]['fields'][0]['data']);

        $this->assertEquals((int) $data['field2cat1']->get('id'), $fields2[0]['fields'][1]['id']);
        $this->assertEquals('ata', $fields2[0]['fields'][1]['shortname']);
        $this->assertEquals('additionaltextarea', $fields2[0]['fields'][1]['fullname']);
        $this->assertEquals('textarea', $fields2[0]['fields'][1]['type']);
        $this->assertEquals(1, $fields2[0]['fields'][1]['visibility']);
        $this->assertEquals('emptytext', $fields2[0]['fields'][1]['data']);

        $this->assertEquals((int) $data['field3cat1']->get('id'), $fields2[0]['fields'][2]['id']);
        $this->assertEquals('cf', $fields2[0]['fields'][2]['shortname']);
        $this->assertEquals('checkboxfield', $fields2[0]['fields'][2]['fullname']);
        $this->assertEquals('checkbox', $fields2[0]['fields'][2]['type']);
        $this->assertEquals(2, $fields2[0]['fields'][2]['visibility']);
        $this->assertEquals(0, $fields2[0]['fields'][2]['data']);

        $this->assertEquals((int) $data['field4cat1']->get('id'), $fields2[0]['fields'][3]['id']);
        $this->assertEquals('dos', $fields2[0]['fields'][3]['shortname']);
        $this->assertEquals('dateofsomething', $fields2[0]['fields'][3]['fullname']);
        $this->assertEquals('date', $fields2[0]['fields'][3]['type']);
        $this->assertEquals(0, $fields2[0]['fields'][3]['visibility']);
        $this->assertEquals(0, $fields2[0]['fields'][3]['data']);

        $this->assertEquals((int) $data['field5cat1']->get('id'), $fields2[0]['fields'][4]['id']);
        $this->assertEquals('sf', $fields2[0]['fields'][4]['shortname']);
        $this->assertEquals('selectfield', $fields2[0]['fields'][4]['fullname']);
        $this->assertEquals('select', $fields2[0]['fields'][4]['type']);
        $this->assertEquals(1, $fields2[0]['fields'][4]['visibility']);
        $this->assertEquals('def', $fields2[0]['fields'][4]['data']);

        $this->assertEquals((int) $data['cat2']->get('id'), $fields2[1]['id']);
        $this->assertEquals('Second Category', $fields2[1]['name']);
        $this->assertCount(1, $fields2[1]['fields']);

        $this->assertEquals((int) $data['field1cat2']->get('id'), $fields2[1]['fields'][0]['id']);
        $this->assertEquals('doat', $fields2[1]['fields'][0]['shortname']);
        $this->assertEquals('dateofanotherthing', $fields2[1]['fields'][0]['fullname']);
        $this->assertEquals('date', $fields2[1]['fields'][0]['type']);
        $this->assertEquals(2, $fields2[1]['fields'][0]['visibility']);
        $this->assertEquals(0, $fields2[1]['fields'][0]['data']);

        $this->assertEquals((int) $data['cat3']->get('id'), $fields2[2]['id']);
        $this->assertEquals('Third Category', $fields2[2]['name']);
        $this->assertCount(0, $fields2[2]['fields']);
    }

    /**
     * Test if the customfield db field has all necessary data after sync.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::load_course_customfields_from_oer
     */
    public function test_load_course_customfields_from_oer(): void {
        $data = $this->data;
        $this->set_settings_for_test($data['course1']->id, 1, 0, '');

        $fields = coursecustomfield::load_course_customfields_from_oer($data['course1']->id);
        $this->assert_fields_course_one($fields);
    }

    /**
     * Helper function to prevent code duplication.
     *
     * @param array $fields
     * @return void
     */
    private function assert_fields_course_one(array $fields): void {
        $data = $this->data;
        $this->assertCount(3, $fields, 'There are three categories set up for the platform');
        $this->assertEquals((int) $data['cat1']->get('id'), $fields[0]['id']);
        $this->assertEquals('First Category', $fields[0]['name']);

        $this->assertCount(5, $fields[0]['fields']);
        $this->assertEquals((int) $data['field1cat1']->get('id'), $fields[0]['fields'][0]['id']);
        $this->assertEquals('sem', $fields[0]['fields'][0]['shortname']);
        $this->assertEquals('semester', $fields[0]['fields'][0]['fullname']);
        $this->assertEquals('text', $fields[0]['fields'][0]['type']);
        $this->assertEquals(0, $fields[0]['fields'][0]['visibility']);
        $this->assertEquals('WS', $fields[0]['fields'][0]['data']);

        $this->assertEquals((int) $data['field2cat1']->get('id'), $fields[0]['fields'][1]['id']);
        $this->assertEquals('ata', $fields[0]['fields'][1]['shortname']);
        $this->assertEquals('additionaltextarea', $fields[0]['fields'][1]['fullname']);
        $this->assertEquals('textarea', $fields[0]['fields'][1]['type']);
        $this->assertEquals(1, $fields[0]['fields'][1]['visibility']);
        $this->assertEquals('Textarea localhost  a lots of text...', $fields[0]['fields'][1]['data']);

        $this->assertEquals((int) $data['field3cat1']->get('id'), $fields[0]['fields'][2]['id']);
        $this->assertEquals('cf', $fields[0]['fields'][2]['shortname']);
        $this->assertEquals('checkboxfield', $fields[0]['fields'][2]['fullname']);
        $this->assertEquals('checkbox', $fields[0]['fields'][2]['type']);
        $this->assertEquals(2, $fields[0]['fields'][2]['visibility']);
        $this->assertEquals(1, $fields[0]['fields'][2]['data']);

        $this->assertEquals((int) $data['field4cat1']->get('id'), $fields[0]['fields'][3]['id']);
        $this->assertEquals('dos', $fields[0]['fields'][3]['shortname']);
        $this->assertEquals('dateofsomething', $fields[0]['fields'][3]['fullname']);
        $this->assertEquals('date', $fields[0]['fields'][3]['type']);
        $this->assertEquals(0, $fields[0]['fields'][3]['visibility']);
        $this->assertEquals(1657152000, $fields[0]['fields'][3]['data']);

        $this->assertEquals((int) $data['field5cat1']->get('id'), $fields[0]['fields'][4]['id']);
        $this->assertEquals('sf', $fields[0]['fields'][4]['shortname']);
        $this->assertEquals('selectfield', $fields[0]['fields'][4]['fullname']);
        $this->assertEquals('select', $fields[0]['fields'][4]['type']);
        $this->assertEquals(1, $fields[0]['fields'][4]['visibility']);
        $this->assertEquals('ghi', $fields[0]['fields'][4]['data']);

        $this->assertEquals((int) $data['cat2']->get('id'), $fields[1]['id']);
        $this->assertEquals('Second Category', $fields[1]['name']);

        $this->assertEquals((int) $data['field1cat2']->get('id'), $fields[1]['fields'][0]['id']);
        $this->assertEquals('doat', $fields[1]['fields'][0]['shortname']);
        $this->assertEquals('dateofanotherthing', $fields[1]['fields'][0]['fullname']);
        $this->assertEquals('date', $fields[1]['fields'][0]['type']);
        $this->assertEquals(2, $fields[1]['fields'][0]['visibility']);
        $this->assertEquals(1671840000, $fields[1]['fields'][0]['data']);

        $this->assertEquals((int) $data['cat3']->get('id'), $fields[2]['id']);
        $this->assertEquals('Third Category', $fields[2]['name']);
    }

    /**
     * The released metadata has a flat structure. The fields contain reduced metadata
     * as not every value is necessary for relase.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::get_customfields_for_snapshot
     */
    public function test_get_customfields_for_snapshot(): void {
        $data = $this->data;
        $this->set_settings_for_test($data['course1']->id, 1, 0, '');
        $fields = coursecustomfield::get_customfields_for_snapshot($data['course1']->id);
        $this->assertCount(6, $fields);
        foreach ($fields as $field) {
            $this->assertArrayHasKey('shortname', $field);
            $this->assertArrayHasKey('fullname', $field);
            $this->assertArrayHasKey('data', $field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('category', $field);
        }
        $this->assertEquals('sem', $fields[0]['shortname']);
        $this->assertEquals('semester', $fields[0]['fullname']);
        $this->assertEquals('WS', $fields[0]['data']);
        $this->assertEquals('text', $fields[0]['type']);
        $this->assertEquals('First Category', $fields[0]['category']);

        $this->assertEquals('ata', $fields[1]['shortname']);
        $this->assertEquals('additionaltextarea', $fields[1]['fullname']);
        $this->assertEquals('Textarea localhost  a lots of text...', $fields[1]['data']);
        $this->assertEquals('text', $fields[1]['type']);
        $this->assertEquals('First Category', $fields[1]['category']);

        $this->assertEquals('cf', $fields[2]['shortname']);
        $this->assertEquals('checkboxfield', $fields[2]['fullname']);
        $this->assertEquals(1, $fields[2]['data']);
        $this->assertEquals('bool', $fields[2]['type']);
        $this->assertEquals('First Category', $fields[2]['category']);

        $this->assertEquals('dos', $fields[3]['shortname']);
        $this->assertEquals('dateofsomething', $fields[3]['fullname']);
        $this->assertEquals(1657152000, $fields[3]['data']);
        $this->assertEquals('timestamp', $fields[3]['type']);
        $this->assertEquals('First Category', $fields[3]['category']);

        $this->assertEquals('sf', $fields[4]['shortname']);
        $this->assertEquals('selectfield', $fields[4]['fullname']);
        $this->assertEquals('ghi', $fields[4]['data']);
        $this->assertEquals('text', $fields[4]['type']);
        $this->assertEquals('First Category', $fields[4]['category']);

        $this->assertEquals('doat', $fields[5]['shortname']);
        $this->assertEquals('dateofanotherthing', $fields[5]['fullname']);
        $this->assertEquals(1671840000, $fields[5]['data']);
        $this->assertEquals('timestamp', $fields[5]['type']);
        $this->assertEquals('Second Category', $fields[5]['category']);
    }

    /**
     * Test to get string value of select field data.
     *
     * @return void
     * @covers ::get_text_of_select_field
     */
    public function test_get_text_of_select_field(): void {
        $selectdata = "A\r\nB\r\nC\r\nD\r\nE";
        $this->assertEquals('', coursecustomfield::get_text_of_select_field(0, $selectdata));
        $this->assertEquals('A', coursecustomfield::get_text_of_select_field(1, $selectdata));
        $this->assertEquals('B', coursecustomfield::get_text_of_select_field(2, $selectdata));
        $this->assertEquals('C', coursecustomfield::get_text_of_select_field(3, $selectdata));
        $this->assertEquals('D', coursecustomfield::get_text_of_select_field(4, $selectdata));
        $this->assertEquals('E', coursecustomfield::get_text_of_select_field(5, $selectdata));
    }
}
