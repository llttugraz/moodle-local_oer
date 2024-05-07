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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helper/testcourse.php');

use local_oer\forms\courseinfo_form;
use local_oer\metadata\courseinfo;

/**
 * Class courseinfo_form_test
 *
 * @coversDefaultClass \local_oer\forms\courseinfo_form
 */
final class courseinfo_form_test extends \advanced_testcase {
    /**
     * Test validation of formular.
     * Also runs through the formular definition, but there is not tested anything special.
     * At least it throws php or moodle errors when something has changed (php version or moodle api definitions etc.)
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @covers \local_oer\forms\courseinfo_form::validation
     * @covers \local_oer\forms\courseinfo_form::definition
     * @covers \local_oer\forms\courseinfo_form::form_default_element
     * @covers \local_oer\forms\courseinfo_form::parse_identifier
     */
    public function test_validation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;

        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);

        // Some additional setUp things, that has nothing to do with the validation part.
        // But is for the coverage of the definition part.
        set_config('metadataaggregator', 'external', 'local_oer');

        $info = courseinfo::get_default_metadata_object($course->id);
        $info->coursecode = 'externalcourse';
        $info->subplugin = 'external';
        $DB->insert_record('local_oer_courseinfo', $info);
        $info->coursecode = 'externalcourse1';
        $info->subplugin = 'external';
        $info->deleted = 1;
        $DB->insert_record('local_oer_courseinfo', $info);
        $info->coursecode = 'externalcourse2';
        $info->subplugin = 'external';
        $info->deleted = 0;
        $info->ignored = 1;
        $DB->insert_record('local_oer_courseinfo', $info);

        set_config('coursecustomfields', 1, 'local_oer');
        $customcat = $this->getDataGenerator()->create_custom_field_category(['name' => 'OER unit test']);
        $field1 = $this->getDataGenerator()->create_custom_field([
                'categoryid' => $customcat->get('id'),
                'name' => 'field1',
                'shortname' => 'field1',
                'type' => 'text',
        ]);
        $field2 = $this->getDataGenerator()->create_custom_field([
                'categoryid' => $customcat->get('id'),
                'name' => 'field2',
                'shortname' => 'field2',
                'type' => 'date',
        ]);
        $field3 = $this->getDataGenerator()->create_custom_field([
                'categoryid' => $customcat->get('id'),
                'name' => 'field3',
                'shortname' => 'field3',
                'type' => 'select',
        ]);
        $field4 = $this->getDataGenerator()->create_custom_field([
                'categoryid' => $customcat->get('id'),
                'name' => 'field4',
                'shortname' => 'field4',
                'type' => 'checkbox',
        ]);
        $customfields = [
                [
                        'id' => $customcat->get('id'),
                        'name' => 'OER unit test',
                        'fields' => [
                                [
                                        'id' => $field1->get('id'),
                                        'shortname' => 'field1',
                                        'fullname' => 'field1',
                                        'type' => 'text',
                                        'visibility' => '2',
                                        'data' => 'a',
                                ],
                                [
                                        'id' => $field2->get('id'),
                                        'shortname' => 'field2',
                                        'fullname' => 'field2',
                                        'type' => 'date',
                                        'visibility' => '2',
                                        'data' => time(),
                                ],
                                [
                                        'id' => $field3->get('id'),
                                        'shortname' => 'field3',
                                        'fullname' => 'field3',
                                        'type' => 'select',
                                        'visibility' => '2',
                                        'data' => 'b',
                                ],
                                [
                                        'id' => $field4->get('id'),
                                        'shortname' => 'field4',
                                        'fullname' => 'field4',
                                        'type' => 'checkbox',
                                        'visibility' => '2',
                                        'data' => '1',
                                ],
                        ],
                ],
        ];
        $DB->set_field('local_oer_courseinfo', 'customfields', json_encode($customfields),
                ['coursecode' => 'moodlecourse-' . $course->id]);

        $customdata = [
                'courseid' => $course->id,
        ];
        $mform = new courseinfo_form(null, $customdata);

        $formdata = [
                'courseid' => $course->id,
        ];

        $result = $mform->validation($formdata, []);
        $this->assertEmpty($result);

        $formdata['coursename_moodlecourse-' . $course->id] = str_repeat('a', 255);
        $formdata['coursename_edited_moodlecourse-' . $course->id] = 1;

        $result = $mform->validation($formdata, []);
        $this->assertEmpty($result, 'Coursename length is 255 characters, so validation should work.');

        $formdata['coursename_moodlecourse-' . $course->id] = str_repeat('a', 256);
        $formdata['coursename_edited_moodlecourse-' . $course->id] = 1;

        $result = $mform->validation($formdata, []);
        $this->assertArrayHasKey('coursename_moodlecourse-' . $course->id . 'group', $result);
        $this->assertEquals(get_string('maximumchars', '', 255), $result['coursename_moodlecourse-' . $course->id . 'group']);

        $formdata['coursename_moodlecourse-' . $course->id] = '';
        $formdata['coursename_edited_moodlecourse-' . $course->id] = 1;

        $result = $mform->validation($formdata, []);
        $this->assertArrayHasKey('coursename_moodlecourse-' . $course->id . 'group', $result);
        $this->assertEquals(get_string('required'), $result['coursename_moodlecourse-' . $course->id . 'group']);

        $formdata['structure_moodlecourse-' . $course->id] = str_repeat('a', 256);;
        $formdata['structure_edited_moodlecourse-' . $course->id] = 1;
        $formdata['organisation_moodlecourse-' . $course->id] = str_repeat('a', 256);;
        $formdata['organisation_edited_moodlecourse-' . $course->id] = 1;
        $formdata['lecturer_moodlecourse-' . $course->id] = str_repeat('a', 256);;
        $formdata['lecturer_edited_moodlecourse-' . $course->id] = 1;
        $formdata['language_moodlecourse-' . $course->id] = str_repeat('a', 5);;
        $formdata['language_edited_moodlecourse-' . $course->id] = 1;

        $formdata['ignored_moodlecourse-' . $course->id] = 0;

        $result = $mform->validation($formdata, []);
        $this->assertArrayHasKey('coursename_moodlecourse-' . $course->id . 'group', $result);
        $this->assertEquals(get_string('required'), $result['coursename_moodlecourse-' . $course->id . 'group']);
        $this->assertArrayHasKey('structure_moodlecourse-' . $course->id . 'group', $result);
        $this->assertEquals(get_string('maximumchars', '', 255), $result['structure_moodlecourse-' . $course->id . 'group']);
        $this->assertArrayHasKey('organisation_moodlecourse-' . $course->id . 'group', $result);
        $this->assertEquals(get_string('maximumchars', '', 255), $result['organisation_moodlecourse-' . $course->id . 'group']);
        $this->assertArrayHasKey('lecturer_moodlecourse-' . $course->id . 'group', $result);
        $this->assertEquals(get_string('maximumchars', '', 255), $result['lecturer_moodlecourse-' . $course->id . 'group']);
        $this->assertArrayHasKey('language_moodlecourse-' . $course->id . 'group', $result);
        $this->assertEquals(get_string('maximumchars', '', 2), $result['language_moodlecourse-' . $course->id . 'group']);

        // At least one courseinfo needs to be available.
        $formdata['ignored_moodlecourse-' . $course->id] = 1;
        $result = $mform->validation($formdata, []);
        $this->assertArrayHasKey('ignoredgroup_moodlecourse-' . $course->id, $result);
    }

    /**
     * Test update metadata function
     *
     * @return void
     * @throws \dml_exception
     * @covers \local_oer\forms\courseinfo_form::update_metadata
     * @covers \local_oer\forms\courseinfo_form::overwrite_disabled
     */
    public function test_update_metadata(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;

        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);

        $fromform = [
                'courseid' => $course->id,
                'lecturer_moodlecourse-' . $course->id => 'Some Lecturer',
                'lecturer_edited_moodlecourse-' . $course->id => 1,
        ];

        $lecturer = $DB->get_field('local_oer_courseinfo', 'lecturer', ['coursecode' => 'moodlecourse-' . $course->id]);
        $lectureredit = $DB->get_field('local_oer_courseinfo', 'lecturer_edited', ['coursecode' => 'moodlecourse-' . $course->id]);
        $this->assertEmpty($lecturer);
        $this->assertEquals(0, $lectureredit);

        $mform = new courseinfo_form(null, ['courseid' => $course->id]);
        $mform->update_metadata($fromform);

        $lecturer = $DB->get_field('local_oer_courseinfo', 'lecturer', ['coursecode' => 'moodlecourse-' . $course->id]);
        $lectureredit = $DB->get_field('local_oer_courseinfo', 'lecturer_edited', ['coursecode' => 'moodlecourse-' . $course->id]);
        $this->assertEquals('Some Lecturer', $lecturer);
        $this->assertEquals(1, $lectureredit);

        // Set the value back to its original state.
        $fromform = [
                'courseid' => $course->id,
        ];
        $mform->update_metadata($fromform);

        $lecturer = $DB->get_field('local_oer_courseinfo', 'lecturer', ['coursecode' => 'moodlecourse-' . $course->id]);
        $lectureredit = $DB->get_field('local_oer_courseinfo', 'lecturer_edited', ['coursecode' => 'moodlecourse-' . $course->id]);
        $this->assertEmpty($lecturer);
        $this->assertEquals(0, $lectureredit);
    }
}
