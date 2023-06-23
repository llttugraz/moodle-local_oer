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

use local_oer\forms\fileinfo_form;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helper/testcourse.php');
require_once(__DIR__ . '/helper/fromform.php');

/**
 * Class fileinfo_form_test
 *
 * @coversDefaultClass \local_oer\forms\fileinfo_form
 */
class fileinfo_form_test extends \advanced_testcase {
    /**
     * Test the private function set_state.
     *
     * State is a combined value for upload and ignore.
     * State 0: Upload 0 - Ignore 0
     * State 1: Upload 1 - Ignore 0
     * State 2: Upload 0 - Ignore 1
     *
     * Upload and ignore cannot be set together. The mform has dependencies so that these two checkboxes disable each other when
     * selected.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \dml_exception
     * @covers ::set_state
     */
    public function test_set_state() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $testcourse  = new testcourse();
        $course      = $testcourse->generate_testcourse($this->getDataGenerator());
        $contenthash = $testcourse->get_contenthash_of_first_found_file($course);
        $this->assertNotNull($contenthash);

        $form     = new fileinfo_form(null, ['courseid' => $course->id, 'contenthash' => $contenthash]);
        $setstate = new \ReflectionMethod($form, 'set_state');
        $data     = [];
        $setstate->setAccessible(true);
        $setstate->invokeArgs($form, [&$data, 0]);
        $this->assertTrue(isset($data['upload']));
        $this->assertEquals(0, $data['upload']);
        $this->assertTrue(isset($data['ignore']));
        $this->assertEquals(0, $data['ignore']);
        $setstate->invokeArgs($form, [&$data, 1]);
        $this->assertEquals(1, $data['upload']);
        $this->assertEquals(0, $data['ignore']);
        $setstate->invokeArgs($form, [&$data, 2]);
        $this->assertEquals(0, $data['upload']);
        $this->assertEquals(1, $data['ignore']);
    }

    /**
     * Test the validation of formdata in backend.
     * There are some requirements for releasing a file.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @covers ::validation
     */
    public function test_validation() {
        $this->resetAfterTest();
        $this->setAdminUser();
        // Update from 14.06.2022, now it is possible to select required fields.
        $config = 'description,context';
        set_config('requiredfields', $config, 'local_oer');

        $testcourse  = new testcourse();
        $course      = $testcourse->generate_testcourse($this->getDataGenerator());
        $contenthash = $testcourse->get_contenthash_of_first_found_file($course);
        $this->assertNotNull($contenthash);

        $fromform = fromform::fileinfoform_submit($course->id, $contenthash, 'Unittest',
                                                  'Test set state method', 1,
                                                  'cc', 'en', 1, [], 0, 0);
        $form     = new fileinfo_form(null, ['courseid' => $course->id, 'contenthash' => $contenthash]);
        $errors   = $form->validation($fromform, []);
        $this->assertEmpty($errors, 'No errors');

        // Upload and ignore is set, this should not be possible, but a validation has been added just to be sure.
        $fromform           = fromform::fileinfoform_submit($course->id, $contenthash, 'Unittest',
                                                            'Test set state method', 1,
                                                            'cc', 'en', 1, [], 1, 0);
        $fromform['ignore'] = 1;
        $errors             = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['ignore']), 'Upload and ignore cannot be set both.');
        $this->assertEquals(get_string('uploadignoreerror', 'local_oer'), $errors['ignore'],
                            'Upload and ignore cannot be set both.');

        // Upload is activated, but wrong license is set.
        $fromform = fromform::fileinfoform_submit($course->id, $contenthash, 'Unittest',
                                                  'Test set state method', 1,
                                                  'allrightsreserved', 'en', 1,
                                                  ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'], 1, 0);
        $errors   = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['upload']), 'For releasing files an appropriate license is needed');
        $this->assertEquals(get_string('error_upload_license', 'local_oer'), $errors['upload'],
                            'For releasing files an appropriate license is needed');
        $this->assertTrue(isset($errors['license']), 'For releasing files an appropriate license is needed');
        $this->assertEquals(get_string('error_license', 'local_oer'), $errors['license'],
                            'For releasing files an appropriate license is needed');

        // Upload is activated, but author is not set.
        $fromform = fromform::fileinfoform_submit($course->id, $contenthash, 'Unittest',
                                                  'Test set state method', 1,
                                                  'cc', 'en', 1, [], 1, 0);
        $errors   = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['addpersons']), 'Release cannot be set when no person is added to metadata');
        $this->assertEquals(get_string('error_upload_author', 'local_oer'), $errors['addpersons'],
                            'Upload cannot be set when no person is added to metadata');

        // Upload is activated, but no context is set.
        $fromform = fromform::fileinfoform_submit($course->id, $contenthash, 'Unittest',
                                                  'Test set state method', 0,
                                                  'cc', 'en', 1,
                                                  ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'],
                                                  1, 0);
        $errors   = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['context']), 'Release cannot be set when no context is set');
        $this->assertEquals(get_string('error_upload_context', 'local_oer'), $errors['context'],
                            'Release cannot be set when no context is set');

        // Title is required for file metadata.
        $fromform = fromform::fileinfoform_submit($course->id, $contenthash, '',
                                                  'Test set state method', 0,
                                                  'cc', 'en', 1,
                                                  ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'],
                                                  0, 0);
        $errors   = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['title']), 'Title cannot be empty');

        // Title cannot be longer than 255 characters.
        $title    = str_pad('A', 300, 'B');
        $fromform = fromform::fileinfoform_submit($course->id, $contenthash, $title,
                                                  'Test set state method', 0,
                                                  'cc', 'en', 1,
                                                  ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'],
                                                  0, 0);
        $errors   = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['title']), 'Maximum of 255 chars for title');
        $this->assertEquals(get_string('maximumchars', '', 255), $errors['title'],
                            'Maximum of 255 chars for title');
    }

    /**
     * Insert and update metadata in local_oer_files table.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::update_metadata
     */
    public function test_update_metadata() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        $testcourse  = new testcourse();
        $course      = $testcourse->generate_testcourse($this->getDataGenerator());
        $contenthash = $testcourse->get_contenthash_of_first_found_file($course);
        $this->assertNotNull($contenthash);

        $record = $DB->get_record('local_oer_files', ['courseid' => $course->id, 'contenthash' => $contenthash]);
        $this->assertFalse($record, 'No files record exists yet.');

        $fromform = fromform::fileinfoform_submit($course->id, $contenthash, 'Unittest',
                                                  'Test update metadata', 1,
                                                  'cc', 'en', 1, [], 0, 0);
        $form     = new fileinfo_form(null, ['courseid' => $course->id, 'contenthash' => $contenthash]);
        $form->update_metadata($fromform);

        $record = $DB->get_record('local_oer_files', ['courseid' => $course->id, 'contenthash' => $contenthash]);
        $this->assertEquals($course->id, $record->courseid);
        $this->assertEquals($contenthash, $record->contenthash);
        $this->assertEquals('Unittest', $record->title);
        $this->assertEquals('Test update metadata', $record->description);
        $this->assertEquals(1, $record->context);
        $this->assertEquals('cc', $record->license);
        $this->assertEquals('{"persons":[]}', $record->persons);
        $this->assertEquals('', $record->tags);
        $this->assertEquals('en', $record->language);
        $this->assertEquals(1, $record->resourcetype);
        $this->assertEquals(null, $record->classification);
        $this->assertEquals(0, $record->state);
        $this->assertEquals(2, $record->usermodified);
        $this->assertTrue($record->timemodified > 0);
        $this->assertTrue($record->timecreated > 0);

        $tags = [
                'unit',
                'test'
        ];

        $fromform = fromform::fileinfoform_submit($course->id, $contenthash, 'changed title',
                                                  'Lorem ipsum', 0,
                                                  'allrightsreserved', 'de', 5,
                                                  ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'],
                                                  0, 1, $tags);

        $form->update_metadata($fromform);
        $this->assertTrue($DB->count_records('local_oer_files') == 1, 'The existing record should have been updated');

        $record = $DB->get_record('local_oer_files', ['courseid' => $course->id, 'contenthash' => $contenthash]);
        $this->assertEquals($course->id, $record->courseid);
        $this->assertEquals($contenthash, $record->contenthash);
        $this->assertEquals('changed title', $record->title);
        $this->assertEquals('Lorem ipsum', $record->description);
        $this->assertEquals(0, $record->context);
        $this->assertEquals('allrightsreserved', $record->license);
        $this->assertEquals('{"persons":[{"role":"Author","firstname":"Christian","lastname":"Ortner"}]}', $record->persons);
        $this->assertEquals('unit,test', $record->tags);
        $this->assertEquals('de', $record->language);
        $this->assertEquals(5, $record->resourcetype);
        $this->assertEquals(null, $record->classification);
        $this->assertEquals(2, $record->state);
        $this->assertEquals(2, $record->usermodified);
        $this->assertTrue($record->timemodified > 0);
        $this->assertTrue($record->timecreated > 0);
    }
}
