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
final class fileinfo_form_test extends \advanced_testcase {
    /**
     * Test setup.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

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
     * @covers ::set_state
     *
     * @return void
     * @throws \ReflectionException
     */
    public function test_set_state(): void {
        $testcourse = new testcourse();
        $course = $testcourse->generate_testcourse($this->getDataGenerator());
        $identifier = $testcourse->get_identifier_of_first_found_file($course);
        $this->assertNotNull($identifier);

        $form = new fileinfo_form(null, ['courseid' => $course->id, 'identifier' => $identifier]);
        $setstate = new \ReflectionMethod($form, 'set_state');
        $data = [];
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
     * It also covers the definition of the form to see PHP and Moodle warnings/errors.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::validation
     * @covers ::definition
     * @covers ::add_shared_fields_to_form
     * @covers ::get_required_fields
     * @covers ::prepare_classification_values_for_form
     */
    public function test_validation(): void {
        // Update from 14.06.2022, now it is possible to select required fields.
        $config = 'description,context';
        set_config('requiredfields', $config, 'local_oer');

        $testcourse = new testcourse();
        $course = $testcourse->generate_testcourse($this->getDataGenerator());
        $identifier = $testcourse->get_identifier_of_first_found_file($course);
        $this->assertNotNull($identifier);

        $fromform = fromform::fileinfoform_submit($course->id, $identifier, 'Unittest',
                'Test set state method', 1,
                'cc', 'en', 1, [], 0, 0);
        $form = new fileinfo_form(null, ['courseid' => $course->id, 'identifier' => $identifier]);
        $errors = $form->validation($fromform, []);
        $this->assertEmpty($errors, 'No errors');

        // Upload and ignore is set, this should not be possible, but a validation has been added just to be sure.
        $fromform = fromform::fileinfoform_submit($course->id, $identifier, 'Unittest',
                'Test set state method', 1,
                'cc', 'en', 1, [], 1, 0);
        $fromform['ignore'] = 1;
        $errors = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['ignore']), 'Upload and ignore cannot be set both.');
        $this->assertEquals(get_string('uploadignoreerror', 'local_oer'), $errors['ignore'],
                'Upload and ignore cannot be set both.');

        // Upload is activated, but wrong license is set.
        $fromform = fromform::fileinfoform_submit($course->id, $identifier, 'Unittest',
                'Test set state method', 1,
                'allrightsreserved', 'en', 1,
                ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'], 1, 0);
        $errors = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['upload']), 'For releasing files an appropriate license is needed');
        $this->assertEquals(get_string('error_upload_license', 'local_oer'), $errors['upload'],
                'For releasing files an appropriate license is needed');
        $this->assertTrue(isset($errors['license']), 'For releasing files an appropriate license is needed');
        $this->assertEquals(get_string('error_license', 'local_oer'), $errors['license'],
                'For releasing files an appropriate license is needed');

        // Upload is activated, but author is not set.
        $fromform = fromform::fileinfoform_submit($course->id, $identifier, 'Unittest',
                'Test set state method', 1,
                'cc', 'en', 1, [], 1, 0);
        $errors = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['addpersons']), 'Release cannot be set when no person is added to metadata');
        $this->assertEquals(get_string('error_upload_author', 'local_oer', ['roles' => 'Author']),
                $errors['addpersons'],
                'Upload cannot be set when no person is added to metadata');

        // Upload is activated, but no context is set.
        $fromform = fromform::fileinfoform_submit($course->id, $identifier, 'Unittest',
                'Test set state method', 0,
                'cc', 'en', 1,
                ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'],
                1, 0);
        $errors = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['context']), 'Release cannot be set when no context is set');
        $this->assertEquals(get_string('error_upload_context', 'local_oer'), $errors['context'],
                'Release cannot be set when no context is set');

        // Title is required for file metadata.
        $fromform = fromform::fileinfoform_submit($course->id, $identifier, '',
                'Test set state method', 0,
                'cc', 'en', 1,
                ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'],
                0, 0);
        $errors = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['title']), 'Title cannot be empty');

        // Title cannot be longer than 255 characters.
        $title = str_pad('A', 300, 'B');
        $fromform = fromform::fileinfoform_submit($course->id, $identifier, $title,
                'Test set state method', 0,
                'cc', 'en', 1,
                ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'],
                0, 0);
        $errors = $form->validation($fromform, []);
        $this->assertTrue(isset($errors['title']), 'Maximum of 255 chars for title');
        $this->assertEquals(get_string('maximumchars', '', 255), $errors['title'],
                'Maximum of 255 chars for title');

        // Set all fields to required. Should trigger several errors.
        set_config('requiredfields', 'description,context,tags,language,resourcetype', 'local_oer');
        $form = new fileinfo_form(null, ['courseid' => $course->id, 'identifier' => $identifier]);
        $fromform = [
                'courseid' => $course->id,
                'identifer' => $identifier,
                'language' => '0',
                'resourcetype' => 0,
                'upload' => 1,
                'license' => 'cc',
                'context' => 0,
                'title' => 'Filename',
                'storedperson' => '{"Author": "Unit tester"}',
                'creator' => 'oermod_resource\module',
        ];
        $errors = $form->validation($fromform, []);
        $this->assertArrayHasKey('addpersons', $errors);
        $this->assertArrayHasKey('context', $errors);
        $this->assertArrayHasKey('tags', $errors);
        $this->assertArrayHasKey('description', $errors);
        $this->assertArrayHasKey('language', $errors);
        $this->assertArrayHasKey('resourcetype', $errors);

        // This should cover the file is not writable part.
        $testcourse->set_files_to($course->id, 5, true);
        new fileinfo_form(null, ['courseid' => $course->id, 'identifier' => $identifier]);
    }

    /**
     * Insert and update metadata in local_oer_files table.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::update_metadata
     * @covers ::add_values_from_form
     * @covers ::set_value
     * @covers ::prepare_classification_values_to_store
     */
    public function test_update_metadata(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        $testcourse = new testcourse();
        $course = $testcourse->generate_testcourse($this->getDataGenerator());
        $identifier = $testcourse->get_identifier_of_first_found_file($course);
        $this->assertNotNull($identifier);

        $record = $DB->get_record('local_oer_elements', ['courseid' => $course->id, 'identifier' => $identifier]);
        $this->assertFalse($record, 'No files record exists yet.');

        $fromform = fromform::fileinfoform_submit($course->id, $identifier, 'Unittest',
                'Test update metadata', 1,
                'cc', 'en', 1, [], 0, 0);
        $form = new fileinfo_form(null, ['courseid' => $course->id, 'identifier' => $identifier]);
        $form->update_metadata($fromform);

        $record = $DB->get_record('local_oer_elements', ['courseid' => $course->id, 'identifier' => $identifier]);
        $this->assertEquals($course->id, $record->courseid);
        $this->assertEquals($identifier, $record->identifier);
        $this->assertEquals('Unittest', $record->title);
        $this->assertEquals('Test update metadata', $record->description);
        $this->assertEquals(1, $record->context);
        $this->assertEquals('cc', $record->license);
        $this->assertEquals('{"persons":[]}', $record->persons);
        $this->assertEquals('', $record->tags);
        $this->assertEquals('en', $record->language);
        $this->assertEquals(1, $record->resourcetype);
        $this->assertEquals(null, $record->classification);
        $this->assertEquals(0, $record->releasestate);
        $this->assertEquals(2, $record->usermodified);
        $this->assertTrue($record->timemodified > 0);
        $this->assertTrue($record->timecreated > 0);

        $tags = [
                'unit',
                'test',
        ];

        $fromform = fromform::fileinfoform_submit($course->id, $identifier, 'changed title',
                'Lorem ipsum', 0,
                'allrightsreserved', 'de', 5,
                ['{"role":"Author","firstname":"Christian","lastname":"Ortner"}'],
                0, 1, $tags);

        $form->update_metadata($fromform);
        $this->assertTrue($DB->count_records('local_oer_elements') == 1, 'The existing record should have been updated');

        $record = $DB->get_record('local_oer_elements', ['courseid' => $course->id, 'identifier' => $identifier]);
        $this->assertEquals($course->id, $record->courseid);
        $this->assertEquals($identifier, $record->identifier);
        $this->assertEquals('changed title', $record->title);
        $this->assertEquals('Lorem ipsum', $record->description);
        $this->assertEquals(0, $record->context);
        $this->assertEquals('allrightsreserved', $record->license);
        $this->assertEquals('{"persons":[{"role":"Author","firstname":"Christian","lastname":"Ortner"}]}', $record->persons);
        $this->assertEquals('unit,test', $record->tags);
        $this->assertEquals('de', $record->language);
        $this->assertEquals(5, $record->resourcetype);
        $this->assertEquals(null, $record->classification);
        $this->assertEquals(2, $record->releasestate);
        $this->assertEquals(2, $record->usermodified);
        $this->assertTrue($record->timemodified > 0);
        $this->assertTrue($record->timecreated > 0);
    }

    /**
     * Test reset to preference method.
     *
     * @covers ::reset_form_data_to_preference_values
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_reset_form_data_to_preference_values(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        global $DB, $USER;

        $testcourse = new testcourse();
        $course = $testcourse->generate_testcourse($this->getDataGenerator());
        $contenthash = $testcourse->get_contenthash_of_first_found_file($course);

        $fromform = fromform::fileinfoform_submit($course->id, $contenthash, 'Unittest',
                'Test set state method', 1,
                'cc', 'en', 1, [], 0, 0);
        $compare = $fromform;

        // No preferences exist yet for this course. So there function will just return.
        fileinfo_form::reset_form_data_to_preference_values($fromform);
        $this->assertEquals($compare['courseid'], $fromform['courseid']);
        $this->assertArrayNotHasKey('upload', $compare);
        $this->assertArrayNotHasKey('upload', $fromform);
        $this->assertArrayNotHasKey('ignore', $compare);
        $this->assertArrayNotHasKey('ignore', $fromform);
        $this->assertEquals($compare['license'], $fromform['license']);
        $this->assertEquals($compare['storedperson'], $fromform['storedperson']);
        $this->assertEquals($compare['storedtags'], $fromform['storedtags']);
        $this->assertEquals($compare['resourcetype'], $fromform['resourcetype']);

        $fromform['classification'] = null;
        $fromform['state'] = 0;
        $fromform['usermodified'] = $USER->id;
        $fromform['timemodified'] = time();
        $fromform['timecreated'] = time();
        $fromform['persons'] = null;
        $fromform['id'] = $DB->insert_record('local_oer_preference', $fromform);

        // Now a preference has been stored. Run the reset again.
        $newform = fromform::fileinfoform_submit($course->id, $contenthash, 'Reset to preference',
                'The fields differ from the db entry', 2,
                'allrightsreserved', 'de', 7, ['Frank Furter'], 1, 0);
        fileinfo_form::reset_form_data_to_preference_values($newform);
        $this->assertEquals($compare['courseid'], $newform['courseid']);
        $this->assertEquals(0, $newform['upload']);
        $this->assertEquals($compare['license'], $newform['license']);
        $this->assertEquals('{"persons":[Frank Furter]}', $newform['storedperson'],
                'As persons is null in preferences, it will not be overwritten.');
        $this->assertEquals($compare['storedtags'], $newform['storedtags']);
        $this->assertEquals($compare['resourcetype'], $newform['resourcetype']);
        $this->assertArrayHasKey('ignore', $newform);
        $this->assertEquals(0, $newform['ignore']);

        $fromform['persons'] = '{"persons":[Bess Twishes]}';
        $fromform['state'] = 1;
        $DB->update_record('local_oer_preference', (object) $fromform);
        fileinfo_form::reset_form_data_to_preference_values($newform);
        $this->assertEquals('{"persons":[Bess Twishes]}', $newform['storedperson']);
        $this->assertArrayHasKey('ignore', $newform);
        $this->assertEquals(1, $newform['ignore']);
    }

    /**
     * Test for private function add_people.
     *
     * @covers ::add_people
     *
     * @return void
     * @throws \ReflectionException
     * @throws \coding_exception
     */
    public function test_add_people(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $testcourse = new testcourse();
        $course = $testcourse->generate_testcourse($this->getDataGenerator());
        $identifier = $testcourse->get_identifier_of_first_found_file($course);
        $this->assertNotNull($identifier);

        $fromform = fromform::fileinfoform_submit($course->id, $identifier, 'Unittest',
                'Test set state method', 1,
                'cc', 'en', 1, [], 0, 0);
        $form = new fileinfo_form(null, ['courseid' => $course->id, 'identifier' => $identifier]);
        $errors = $form->validation($fromform, []);
        $this->assertEmpty($errors, 'No errors');

        $addpeople = new \ReflectionMethod($form, 'add_people');

        $person1 = new \stdClass();
        $person1->firstname = $this->get_firstname();
        $person1->lastname = $this->get_lastname();
        $person1->role = $this->get_roles(true)[0];
        $person2 = new \stdClass();
        $person2->fullname = $this->get_fullname(2, 3);
        $person2->role = $this->get_roles(true)[0];
        $people = [$person1, $person2];

        $addpeople->setAccessible(true);
        $result = $addpeople->invoke($form, $people, $this->get_roles(false));
        $this->assertCount(2, $result);
    }

    /**
     * Get an array of roles for name tests.
     *
     * The sub-plugins will deliver an array in the format:
     * [
     *   [
     *     Rolename,
     *     langstring name,
     *     component
     *   ]
     * ]
     * For the tests only the first element is necessary.
     *
     * @param bool $random
     * @return array
     */
    private function get_roles(bool $random): array {
        $roles = [
                ['Author'], ['Publisher'], ['Creator'], ['Contributor'], ['Presenter'],
        ];
        return $random ? $roles[rand(1, count($roles) - 1)] : $roles;
    }

    /**
     * Get a random firstname from test data generator.
     *
     * @return string
     */
    private function get_firstname(): string {
        return $this->getDataGenerator()->firstnames[rand(0, count($this->getDataGenerator()->firstnames) - 1)];
    }

    /**
     * Get a random lastname from test data generator.
     *
     * @return string
     */
    private function get_lastname(): string {
        return $this->getDataGenerator()->lastnames[rand(0, count($this->getDataGenerator()->lastnames) - 1)];
    }

    /**
     * Generate a fullname with names from testdata generator.
     *
     * @param int $amountfirstnames Amount of firstnames.
     * @param int $amountlastnames Amount of lastnames.
     * @return string
     */
    private function get_fullname(int $amountfirstnames = 1, int $amountlastnames = 1): string {
        $firstnames = [];
        $lastnames = [];
        for ($i = 0; $i < $amountfirstnames; $i++) {
            $firstnames[] = $this->get_firstname();
        }
        for ($i = 0; $i < $amountlastnames; $i++) {
            $lastnames[] = $this->get_lastname();
        }
        $name = array_merge($firstnames, $lastnames);
        return implode(' ', $name);
    }
}
