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

/**
 * Class get_file_test
 *
 * @runTestsInSeparateProcesses
 * @coversDefaultClass \local_oer\services\get_file
 */
final class get_file_test extends \advanced_testcase {
    /**
     * Type of external_value. In Moodle 4.2 the namespace changes.
     *
     * @var string
     */
    private $value = 'external_value';
    /**
     * Type of external_single_structure. In Moodle 4.2 the namespace changes.
     *
     * @var string
     */
    private $single = 'external_single_structure';
    /**
     * Type of external_multiple_structure. In Moodle 4.2 the namespace changes.
     *
     * @var string
     */
    private $multi = 'external_multiple_structure';

    /**
     * Type of external_function_parameters. In Moodle 4.2 the namespace changes.
     *
     * @var string
     */
    private $parameter = 'external_function_parameters';

    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        require_once(__DIR__ . '/helper/testcourse.php');
        global $CFG;
        // In Moodle 4.2 the namespace core_external was added to external_api.
        if ($CFG->version >= 2023042401) {
            $this->value = 'core_external\\' . $this->value;
            $this->single = 'core_external\\' . $this->single;
            $this->multi = 'core_external\\' . $this->multi;
            $this->parameter = 'core_external\\' . $this->parameter;
        }
    }

    /**
     * Test parameters.
     *
     * @return void
     * @covers \local_oer\services\get_file::service_parameters
     */
    public function test_service_parameters(): void {
        $parameters = \local_oer\services\get_file::service_parameters();
        $this->assertEquals($this->parameter, get_class($parameters));
        $this->assertArrayHasKey('courseid', $parameters->keys);
        $this->assertEquals($this->value, get_class($parameters->keys['courseid']));
        $this->assertEquals(PARAM_INT, $parameters->keys['courseid']->type);
        $this->assertArrayHasKey('identifier', $parameters->keys);
        $this->assertEquals($this->value, get_class($parameters->keys['identifier']));
        $this->assertEquals(PARAM_TEXT, $parameters->keys['identifier']->type);
    }

    /**
     * Test if service returns has all necessary fields defined.
     *
     * @return void
     * @covers \local_oer\services\get_file::service_returns
     * @covers \local_oer\services\get_file::external_file_return_value
     */
    public function test_service_returns(): void {
        $returnvalue = \local_oer\services\get_file::service_returns();
        $this->assertEquals($this->single, get_class($returnvalue));
        $this->assertArrayHasKey('courseid', $returnvalue->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['courseid']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['courseid']->type);
        $this->assertArrayHasKey('context', $returnvalue->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['context']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['context']->type);
        $this->assertArrayHasKey('file', $returnvalue->keys);
        $this->assertEquals($this->single, get_class($returnvalue->keys['file']));
        $this->assertArrayHasKey('id', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['id']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['id']->type);
        $this->assertArrayHasKey('identifier', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['identifier']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['identifier']->type);
        $this->assertArrayHasKey('title', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['title']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['title']->type);
        $this->assertArrayHasKey('mimetype', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['mimetype']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['mimetype']->type);
        $this->assertArrayHasKey('icon', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['icon']));
        $this->assertEquals(PARAM_RAW, $returnvalue->keys['file']->keys['icon']->type);
        $this->assertArrayHasKey('timemodified', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['timemodified']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['timemodified']->type);
        $this->assertArrayHasKey('timeuploaded', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['timeuploaded']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['timeuploaded']->type);
        $this->assertArrayHasKey('timeuploadedts', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['timeuploadedts']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['timeuploadedts']->type);
        $this->assertArrayHasKey('upload', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['upload']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['upload']->type);
        $this->assertArrayHasKey('ignore', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['ignore']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['ignore']->type);
        $this->assertArrayHasKey('deleted', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['deleted']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['deleted']->type);

        // Update 2024-01-17 Modules and sections have been removed due to the subplugin structure.
        // A more general approach has been implemented which result in the information key and subkeys.
        $this->assertArrayHasKey('information', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->multi, get_class($returnvalue->keys['file']->keys['information']));
        $this->assertEquals($this->single, get_class($returnvalue->keys['file']->keys['information']->content));
        $this->assertArrayHasKey('area', $returnvalue->keys['file']->keys['information']->content->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['information']->content->keys['area']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['information']->content->keys['area']->type);
        $this->assertArrayHasKey('fields', $returnvalue->keys['file']->keys['information']->content->keys);
        $this->assertEquals($this->multi, get_class($returnvalue->keys['file']->keys['information']->content->keys['fields']));
        $this->assertEquals($this->single,
                get_class($returnvalue->keys['file']->keys['information']->content->keys['fields']->content));
        $this->assertArrayHasKey('infoname',
                $returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys);
        $this->assertEquals($this->value,
                get_class($returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys['infoname']));
        $this->assertEquals(PARAM_TEXT,
                $returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys['infoname']->type);
        $this->assertArrayHasKey('infourl',
                $returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys);
        $this->assertEquals($this->value,
                get_class($returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys['infourl']));
        $this->assertEquals(PARAM_URL,
                $returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys['infourl']->type);
        $this->assertArrayHasKey('infohasurl',
                $returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys);
        $this->assertEquals($this->value,
                get_class($returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys['infohasurl']));
        $this->assertEquals(PARAM_BOOL,
                $returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys['infohasurl']->type);
        $this->assertArrayHasKey('last',
                $returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys);
        $this->assertEquals($this->value,
                get_class($returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys['last']));
        $this->assertEquals(PARAM_BOOL,
                $returnvalue->keys['file']->keys['information']->content->keys['fields']->content->keys['last']->type);

        $this->assertArrayHasKey('requirementsmet', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['requirementsmet']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['requirementsmet']->type);
        $this->assertArrayHasKey('state', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['state']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['state']->type);
        $this->assertArrayHasKey('multiple', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['multiple']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['multiple']->type);
        $this->assertArrayHasKey('editor', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['editor']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['editor']->type);

        $this->assertEquals($this->multi, get_class($returnvalue->keys['file']->keys['courses']));
        $this->assertEquals($this->single, get_class($returnvalue->keys['file']->keys['courses']->content));
        $this->assertArrayHasKey('id', $returnvalue->keys['file']->keys['courses']->content->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['courses']->content->keys['id']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['courses']->content->keys['id']->type);
        $this->assertArrayHasKey('name', $returnvalue->keys['file']->keys['courses']->content->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['courses']->content->keys['name']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['courses']->content->keys['name']->type);
        $this->assertArrayHasKey('editor', $returnvalue->keys['file']->keys['courses']->content->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['courses']->content->keys['editor']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['courses']->content->keys['editor']->type);

        $this->assertArrayHasKey('writable', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['writable']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['writable']->type);
        $this->assertArrayHasKey('coursetofile', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['coursetofile']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['coursetofile']->type);
        $this->assertArrayHasKey('wwwroot', $returnvalue->keys['file']->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['file']->keys['wwwroot']));
        $this->assertEquals(PARAM_URL, $returnvalue->keys['file']->keys['wwwroot']->type);
    }

    /**
     * Test service.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers \local_oer\services\get_file::service
     */
    public function test_service(): void {
        $this->setAdminUser();
        $helper = new \local_oer\testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $identifier = $helper->get_identifier_of_first_found_file($course);

        $result = \local_oer\services\get_file::service($course->id, $identifier);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('courseid', $result);
        $this->assertEquals($course->id, $result['courseid']);
        $this->assertArrayHasKey('context', $result);
        $this->assertEquals(\context_course::instance($course->id)->id, $result['context']);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('identifier', $result['file']);
        $this->assertEquals($identifier, $result['file']['identifier']);
        // Does it make sense to test all values here?
        // The service will ensure all fields that are set in service_returns are set.
        // An error will be thrown if a field is missing. Additional fields will be removed by external_api.
        // If the used function deliver the correct values is tested in the unit tests for those classes.

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('Contenthash cannot be empty for single file aquirement.');
        \local_oer\services\get_file::service($course->id, '');
    }
}
