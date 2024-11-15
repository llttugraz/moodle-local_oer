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

use local_oer\metadata\courseinfo;

/**
 * Class courseinfo_test
 *
 * @coversDefaultClass \local_oer\metadata\courseinfo
 */
final class courseinfo_test extends \advanced_testcase {
    /**
     * Moodle courseid used in all tests.
     *
     * @var null
     */
    private $courseid = null;

    /**
     * Setup for all tests.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->courseid = $course->id;
    }

    /**
     * Test the default metadata. Also tests if properties remain. This would show
     * if the method has been extended but the unit tests need to be updated.
     *
     * @return void
     * @covers ::get_default_metadata_object
     */
    public function test_get_default_metadata_object(): void {
        $metadata = courseinfo::get_default_metadata_object($this->courseid);
        $this->assertIsObject($metadata);
        $this->assertTrue(property_exists($metadata, 'courseid'));
        $this->assertEquals($this->courseid, $metadata->courseid);
        unset($metadata->courseid);
        $this->assertTrue(property_exists($metadata, 'coursecode'));
        unset($metadata->coursecode);
        $this->assertTrue(property_exists($metadata, 'deleted'));
        unset($metadata->deleted);
        $this->assertTrue(property_exists($metadata, 'ignored'));
        unset($metadata->ignored);
        $this->assertTrue(property_exists($metadata, 'external_courseid'));
        unset($metadata->external_courseid);
        $this->assertTrue(property_exists($metadata, 'external_sourceid'));
        unset($metadata->external_sourceid);
        $this->assertTrue(property_exists($metadata, 'coursename'));
        unset($metadata->coursename);
        $this->assertTrue(property_exists($metadata, 'coursename_edited'));
        unset($metadata->coursename_edited);
        $this->assertTrue(property_exists($metadata, 'structure'));
        unset($metadata->structure);
        $this->assertTrue(property_exists($metadata, 'structure_edited'));
        unset($metadata->structure_edited);
        $this->assertTrue(property_exists($metadata, 'description'));
        unset($metadata->description);
        $this->assertTrue(property_exists($metadata, 'description_edited'));
        unset($metadata->description_edited);
        $this->assertTrue(property_exists($metadata, 'objectives'));
        unset($metadata->objectives);
        $this->assertTrue(property_exists($metadata, 'objectives_edited'));
        unset($metadata->objectives_edited);
        $this->assertTrue(property_exists($metadata, 'organisation'));
        unset($metadata->organisation);
        $this->assertTrue(property_exists($metadata, 'organisation_edited'));
        unset($metadata->organisation_edited);
        $this->assertTrue(property_exists($metadata, 'language'));
        unset($metadata->language);
        $this->assertTrue(property_exists($metadata, 'language_edited'));
        unset($metadata->language_edited);
        $this->assertTrue(property_exists($metadata, 'lecturer'));
        unset($metadata->lecturer);
        $this->assertTrue(property_exists($metadata, 'lecturer_edited'));
        unset($metadata->lecturer_edited);
        $this->assertTrue(property_exists($metadata, 'customfields'));
        unset($metadata->customfields);
        $this->assertTrue(property_exists($metadata, 'subplugin'));
        $this->assertEquals('base', $metadata->subplugin);
        unset($metadata->subplugin);
        $this->assertTrue(property_exists($metadata, 'usermodified'));
        unset($metadata->usermodified);
        $this->assertTrue(property_exists($metadata, 'timecreated'));
        unset($metadata->timecreated);
        $this->assertTrue(property_exists($metadata, 'timemodified'));
        unset($metadata->timemodified);
        // The unset procedure is more of a reminder to keep the unit tests up to date if something of the basic structure changes.
        $this->assertEmpty((array) $metadata);
    }

    /**
     * Test the loading of course metadata from db.
     * A Moodle course can have multiple course metadata objects, so test if the method would also deliver more than one.
     * Test if custom fields are converted to array when loading them from db.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::load_metadata_from_database
     */
    public function test_load_metadata_from_database(): void {
        $courseinfo = new courseinfo();
        $this->assertEmpty($courseinfo->load_metadata_from_database($this->courseid));
        global $DB;
        $metadata = courseinfo::get_default_metadata_object($this->courseid);
        $DB->insert_record('local_oer_courseinfo', $metadata);
        $this->assertEquals(1, count($courseinfo->load_metadata_from_database($this->courseid)));
        $metadata = courseinfo::get_default_metadata_object($this->courseid);
        $metadata->coursecode = 'secondcourse';
        $DB->insert_record('local_oer_courseinfo', $metadata);
        $this->assertEquals(2, count($courseinfo->load_metadata_from_database($this->courseid)));
        $json = [
                'a' => 'test',
                'b' => 'aaaa',
        ];
        $metadata->customfields = json_encode($json);
        $id = $DB->get_field('local_oer_courseinfo', 'id',
                ['courseid' => $this->courseid, 'coursecode' => $metadata->coursecode]);
        $metadata->id = $id;
        $DB->update_record('local_oer_courseinfo', $metadata);
        $courses = $courseinfo->load_metadata_from_database($this->courseid);
        $this->assertTrue(is_array(end($courses)->customfields));
    }

    /**
     * Test the metadata generator for courseinfo.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @covers ::generate_metadata
     */
    public function test_generate_metadata(): void {
        $customcat1 = $this->getDataGenerator()->create_custom_field_category(['name' => 'unittest category 1']);
        $customcat2 = $this->getDataGenerator()->create_custom_field_category(['name' => 'category 2 for unittest']);
        $field1 = $this->getDataGenerator()->create_custom_field([
                'name' => 'semester', 'shortname' => 'sem',
                'type' => 'text',
                'categoryid' => $customcat1->get('id'),
        ]);
        $handler = \core_course\customfield\course_handler::create();
        $data = new \stdClass();
        $data->id = $this->courseid;
        $data->customfield_sem = 'WS';
        $handler->instance_form_save($data);

        set_config('coursecustomfields', 0, 'local_oer');
        $editingteacher1 = $this->getDataGenerator()->create_user();
        $editingteacher2 = $this->getDataGenerator()->create_user();
        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($editingteacher1->id, $this->courseid, 'editingteacher');
        $this->getDataGenerator()->enrol_user($editingteacher2->id, $this->courseid, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher1->id, $this->courseid, 'teacher');
        $this->getDataGenerator()->enrol_user($student1->id, $this->courseid, 'student');

        // Test 1: get basic metadata of course without customfields (setting is disabled).
        $courseinfo = new courseinfo();
        $courses = $courseinfo->generate_metadata($this->courseid);
        $this->assertCount(1, $courses);
        $course = reset($courses);
        $mcourse = get_course($this->courseid);
        $this->assertEquals('moodlecourse-' . $this->courseid, $course->coursecode);
        $this->assertEquals($mcourse->fullname, $course->coursename);
        $this->assertEquals('', $course->structure);
        $this->assertEquals(courseinfo::simple_html_to_text_reduction($mcourse->summary), $course->description);
        $lecturers = fullname($editingteacher1) . ', ' . fullname($editingteacher2);
        $this->assertEquals($lecturers, $course->lecturer);
        $this->assertNull($course->customfields);

        // Test 2: add customfields
        // Only a basic test, settings are tested in coursecustomfield tests.
        set_config('coursecustomfields', 1, 'local_oer');
        $courses = $courseinfo->generate_metadata($this->courseid);
        $course = reset($courses);
        $expected = [
                [
                        'id' => (int) $customcat1->get('id'),
                        'name' => $customcat1->get('name'),
                        'fields' => [
                                [
                                        'id' => (int) $field1->get('id'),
                                        'shortname' => $field1->get('shortname'),
                                        'fullname' => $field1->get('name'),
                                        'type' => $field1->get('type'),
                                        'visibility' => $field1->get('configdata')['visibility'],
                                        'data' => "WS",
                                ],
                        ],
                ],
                [
                        'id' => (int) $customcat2->get('id'),
                        'name' => $customcat2->get('name'),
                        'fields' => [],
                ],
        ];
        $this->assertEquals($expected, $course->customfields);
    }

    /**
     * Test the html to text function.
     *
     * @covers ::simple_html_to_text_reduction
     *
     * @return void
     * @throws \Exception
     */
    public function test_simple_html_to_text_reduction(): void {
        $anchor = '<a href="https://irunaunittest.test">this text is lost</a>';
        $expected = "https://irunaunittest.test";
        $result = courseinfo::simple_html_to_text_reduction($anchor);
        $this->assertEquals($expected, $result);
        $text = "<h1>Hello there</h1>" .
                "<h3>Testtext</h3>" .
                "<p></p>" .
                "<p>" .
                "<ol>" .
                "<li>abc</li>" .
                "<li>def</li>" .
                "<li>ghi</li>" .
                "<li>uvw</li>" .
                "<li>xyz</li>" .
                "</ol>" .
                $anchor .
                $anchor .
                "</p>" . // Linebreak \r\n will be removed by trim.
                '<img src="abcdef" alt="abc">';
        $result = courseinfo::simple_html_to_text_reduction($text);
        $expected = "Hello there\r\n" .
                "Testtext\r\n" .
                "\r\n" .
                "* abc\r\n" .
                "* def\r\n" .
                "* ghi\r\n" .
                "* uvw\r\n" .
                "* xyz\r\n" .
                "\r\n" .
                "https://irunaunittest.test https://irunaunittest.test";
        $this->assertEquals($expected, $result);
    }
}
