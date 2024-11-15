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

use local_oer\forms\coursetofile_form;

/**
 * Class coursetofile_form_test
 *
 * @coversDefaultClass \local_oer\forms\coursetofile_form
 */
final class coursetofile_form_test extends \advanced_testcase {
    /**
     * Form for the test.
     *
     * @var coursetofile_form|null
     */
    private $mform = null;

    /**
     * Different values the setUp is creating.
     *
     * @var array
     */
    private $data = [];

    /**
     * Setup courses and formular for tests.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $helper = new testcourse();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        [$draftid, $file] = $helper->generate_file('multipleused');

        $customdata = [
                'courseid' => $course1->id,
                'identifier' => $helper->generate_identifier($file->get_contenthash()),
        ];

        $helper->generate_resource($course2, $this->getDataGenerator());
        $record = new \stdClass();
        $record->course = $course1;
        $record->files = $draftid;
        $this->getDataGenerator()->create_module('resource', $record);
        $record->course = $course2;
        $this->getDataGenerator()->create_module('resource', $record);

        // To cover code that is reached before courseinfo is synced.
        new coursetofile_form(null, $customdata);

        $helper->sync_course_info($course1->id);
        $helper->sync_course_info($course2->id);

        $this->mform = new coursetofile_form(null, $customdata);
        $this->data = [
                'course1' => $course1,
                'course2' => $course2,
                'file' => $file,
                'key1' => $course1->id . coursetofile_form::COURSEINFO_SEPARATOR . 'moodlecourse-' . $course1->id,
                'key2' => $course2->id . coursetofile_form::COURSEINFO_SEPARATOR . 'moodlecourse-' . $course2->id,
        ];
    }

    /**
     * Just to run through the code and test for PHP and Moodle warnings/errors.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     * @covers \local_oer\forms\coursetofile_form::validation
     * @covers \local_oer\forms\coursetofile_form::definition
     */
    public function test_validation(): void {
        $result = $this->mform->validation([], []);
        $this->assertEmpty($result);

        $fromform = [
                'courseid' => $this->data['course1']->id,
                'contenthash' => $this->data['file']->get_contenthash(),
                $this->data['key1'] => '1',
                $this->data['key2'] => '1',
        ];
        $result = $this->mform->validation($fromform, []);
        $this->assertEmpty($result);
        $fromform = [
                'courseid' => $this->data['course1']->id,
                'contenthash' => $this->data['file']->get_contenthash(),
                $this->data['key1'] => '0',
                $this->data['key2'] => '0',
        ];
        $result = $this->mform->validation($fromform, []);
        $this->assertArrayHasKey($this->data['key1'], $result);
        $this->assertEquals(get_string('oneeditorselectederror', 'local_oer'), $result[$this->data['key1']]);
    }

    /**
     * Test coursetofile overwrite function.
     *
     * @return void
     * @throws \dml_exception
     * @covers \local_oer\forms\coursetofile_form::store_overwrite_data
     * @covers \local_oer\forms\coursetofile_form::definition
     */
    public function test_store_overwrite_data(): void {
        global $DB;

        $result = $DB->get_records('local_oer_coursetofile');
        $this->assertEmpty($result);

        $fromform = [
                'courseid' => $this->data['course1']->id,
                'contenthash' => $this->data['file']->get_contenthash(),
                $this->data['key1'] => '1',
                $this->data['key2'] => '0',
        ];

        $this->mform->store_overwrite_data($fromform);

        $result = $DB->get_records('local_oer_coursetofile');
        $this->assertEmpty($result);

        $fromform = [
                'courseid' => $this->data['course1']->id,
                'contenthash' => $this->data['file']->get_contenthash(),
                $this->data['key1'] => '1',
                $this->data['key2'] => '1',
        ];

        $this->mform->store_overwrite_data($fromform);

        $result = $DB->get_records('local_oer_coursetofile');
        $this->assertCount(1, $result);

        $this->mform->store_overwrite_data($fromform);

        $result = $DB->get_records('local_oer_coursetofile');
        $this->assertCount(1, $result);

        $fromform = [
                'courseid' => $this->data['course1']->id,
                'contenthash' => $this->data['file']->get_contenthash(),
                $this->data['key1'] => '1',
                $this->data['key2'] => '0',
        ];

        $this->mform->store_overwrite_data($fromform);

        $result = $DB->get_records('local_oer_coursetofile');
        $this->assertEmpty($result);
    }
}
