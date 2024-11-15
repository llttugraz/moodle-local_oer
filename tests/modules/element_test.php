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

use local_oer\modules\element;
use local_oer\modules\information;
use local_oer\modules\person;

/**
 * Test element class
 *
 * @coversDefaultClass \local_oer\modules\element
 */
final class element_test extends \advanced_testcase {
    /**
     * Set up the unit tests.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test element constructor.
     *
     * @covers ::__construct
     *
     * @return void
     */
    public function test_construct(): void {
        new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Creator of the element has to be the module class of sub plugin.');
        new element('local_oer\modules\elements', element::OERTYPE_EXTERNAL);
    }

    /**
     * Test get type method and wrong type method.
     *
     * @covers ::get_type
     * @covers ::wrong_type
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_get_type(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $this->assertEquals(element::OERTYPE_MOODLEFILE, $element->get_type());
        $element = new element('oermod_resource\module', element::OERTYPE_EXTERNAL);
        $this->assertEquals(element::OERTYPE_EXTERNAL, $element->get_type());
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Wrong type defined for element, use either OERTYPE_MOODLEFILE or OERTYPE_EXTERNAL.');
        new element('oermod_resource\module', 5);
    }

    /**
     * Test set and get title and not empty.
     *
     * @covers ::set_title
     * @covers ::get_title
     * @covers ::not_empty
     *
     * @return void
     * @throws \coding_exception#
     */
    public function test_set_get_title(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_title('fancytitle');
        $this->assertEquals('fancytitle', $element->get_title());
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('title has not been set');
        $element->set_title('');
    }

    /**
     * Test set and get identifier.
     *
     * @covers ::set_identifier
     * @covers ::get_identifier
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_set_get_identifier(): void {
        $identifier = 'oer:localhost@moodle:unittest:randomint:23425456456';
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_identifier($identifier);
        $this->assertEquals($identifier, $element->get_identifier());
    }

    /**
     * Test set and get license.
     *
     * @covers ::set_license
     * @covers ::get_license
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_set_get_license(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_license('abc');
        $this->assertEquals('unknown', $element->get_license());
        $element->set_license('cc-4.0');
        $this->assertEquals('cc-4.0', $element->get_license());
    }

    /**
     * Test set and get origin.
     *
     * @covers ::set_origin
     * @covers ::get_origin
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_set_get_origin(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_origin('unittest', 'testidentifier', 'local_oer');
        $origin = $element->get_origin();
        $this->assertArrayHasKey('unittest', $origin);
        $this->assertCount(1, $origin);
        $this->assertCount(2, $origin['unittest']);
        $this->assertEquals('testidentifier', $origin['unittest'][0]);
        $this->assertEquals('local_oer', $origin['unittest'][1]);
    }

    /**
     * Test set and get a source.
     *
     * @covers ::set_source
     * @covers ::get_source
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_set_get_source(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $url = 'https://example.org?test=123';
        $element->set_source($url);
        $this->assertEquals($url, $element->get_source());
    }

    /**
     * Test set and get filesize.
     *
     * @covers ::set_filesize
     * @covers ::get_filesize
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_set_get_filesize(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_filesize(12345);
        $this->assertEquals(12345, $element->get_filesize());
    }

    /**
     * Test set and get mimetype.
     *
     * @covers ::set_mimetype
     * @covers ::get_mimetype
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_set_get_mimetype(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_mimetype('mimetype');
        $this->assertEquals('mimetype', $element->get_mimetype());
    }

    /**
     * Test set and get element state.
     *
     * @covers ::set_elementstate
     * @covers ::get_elementstate
     *
     * @return void
     */
    public function test_set_get_elementstate(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_elementstate(new \stdClass());
        $this->assertIsObject($element->get_elementstate());
    }

    /**
     * Test set and get stored metadata
     *
     * @covers ::set_stored_metadata
     * @covers ::get_stored_metadata
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_set_get_update_stored_metadata(): void {
        $element = $this->set_stored_metadata();
        $result = $element->get_stored_metadata();
        $this->assertIsObject($result);
        $this->assertFalse(isset($result->identifier));
        $this->assertFalse(isset($result->title));
        $this->assertFalse(isset($result->license));
        $this->assertEquals(userdate(1710757823), $result->timemodified);
        $this->assertEquals('-', $result->timereleased);
        $this->assertEquals(0, $result->upload);
        $this->assertEquals(0, $result->ignore);
        $this->assertFalse($result->requirementsmet);
    }

    /**
     * Test set stored metadata field.
     *
     * @covers ::set_stored_metadata_field
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public function test_set_stored_metadata_field(): void {
        $element = $this->set_stored_metadata();
        $count = count((array) $element->get_stored_metadata());
        $element->set_stored_metadata_field('abc', 123, false);
        $metadata = $element->get_stored_metadata();
        $this->assertCount($count + 1, (array) $element->get_stored_metadata(), 'New value added');
        if (strpos(\PHPUnit\Runner\Version::id(), '9.5') === 0) { // For Moodle 4.1 - 4.3.
            $this->assertObjectHasAttribute('abc', $metadata);
        } else { // For Moodle 4.4+.
            $this->assertObjectHasProperty('abc', $metadata);
        }
        $this->assertEquals(123, $metadata->abc);
        $this->assertEquals(0, $metadata->upload);
        $element->set_stored_metadata_field('upload', 1, true);
        $this->assertCount($count + 1, (array) $element->get_stored_metadata(), 'No new value added');
        $metadata = $element->get_stored_metadata();
        $this->assertEquals(1, $metadata->upload);
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Field doesnotexist not yet defined in storedmetadata');
        $element->set_stored_metadata_field('doesnotexist', 1, true);
    }

    /**
     * Test if exception is thrown in set metadata field when metadata is null.
     *
     * @covers ::set_stored_metadata_field
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_set_stored_metadata_field_is_null_exception(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Stored metadata has not been set yet, use set_stored_metadata before updating fields');
        $element->set_stored_metadata_field('abc', 123, false);
    }

    /**
     * Test if exception is thrown when element member is tried to set in stored metadata.
     *
     * @covers ::set_stored_metadata_field
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public function test_set_stored_metadata_field_must_exist_exception(): void {
        $element = $this->set_stored_metadata();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Field identifier not allowed to be added to storedmetadata, ' .
                'use element->set_identifier instead');
        $element->set_stored_metadata_field('identifier', 'notallowedtochange', true);
    }

    /**
     * Helper method to be used in several functions.
     *
     * @return element
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function set_stored_metadata(): element {
        $metadata = new \stdClass();
        $metadata->title = 'abc';
        $metadata->license = 'allrightsreserved';
        $metadata->releasestate = 0;
        $metadata->timemodified = 1710757823;
        $metadata->persons = '';
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_identifier('oer:localhost@moodle:unittest:randomint:23425456456');
        $element->set_stored_metadata($metadata);
        return $element;
    }

    /**
     * Test already stored
     *
     * @covers ::already_stored
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_already_stored(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $this->assertFalse($element->already_stored());
        $element = $this->set_stored_metadata();
        $this->assertTrue($element->already_stored());
    }

    /**
     * Test add person and get people.
     *
     * @covers ::add_person
     * @covers ::get_people
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_set_get_people(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $person = new person();
        $person->set_role('test');
        $person->set_fullname('unit tester');
        $element->add_person($person);
        $person->set_role('otherrole');
        $person->set_firstname('php');
        $person->set_lastname('unit');
        $element->add_person($person);
        $people = $element->get_people();
        $this->assertCount(2, $people);
    }

    /**
     * Test set stored file and get stored files.
     *
     * @covers ::set_storedfile
     * @covers ::get_storedfiles
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_set_get_storedfile(): void {
        $fs = get_file_storage();
        $record = new \stdClass();
        $record->contextid = \context_system::instance()->id;
        $record->component = 'local_oer';
        $record->filearea = 'unittest';
        $record->itemid = 0;
        $record->filepath = '/';
        $record->filename = 'unittest.txt';
        $file = $fs->create_file_from_string($record, 'abcdef');
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_storedfile($file);
        $element->set_storedfile($file);
        $element->set_storedfile($file);
        $files = $element->get_storedfiles();
        $this->assertCount(3, $files);
    }

    /**
     * Test get subplugin.
     *
     * @covers ::get_subplugin
     *
     * @return void
     */
    public function test_get_subplugin(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $this->assertEquals('oermod_resource\module', $element->get_subplugin());
    }

    /**
     * Test add, merge and get information.
     *
     * @covers ::get_information
     * @covers ::add_information
     * @covers ::merge_information
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_add_merge_get_information(): void {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->add_information('type', 'local_oer', 'resource', 'plugintype', 'mod_resource');
        $element->add_information('type', 'local_oer', 'folder', null, '');
        $element->add_information('section', 'moodle', 'abc', 'section', 'abc');
        $this->assertCount(3, $element->get_information());
        $newinfo = new information();
        $newinfo->set_name('blablub');
        $newinfo->set_area('type', 'local_oer');
        $newinfo->set_metadatafield(null);
        $element->merge_information([$newinfo]);
        $this->assertCount(4, $element->get_information());
        $newinfo->set_name('resource');
        $element->merge_information([$newinfo]);
        $this->assertCount(4, $element->get_information());

        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Not all fields for information set.');
        $element->add_information('pluginname', 'local_oer', '', null, '');
    }
}
