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
class get_file_test extends \advanced_testcase {
    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        $this->resetAfterTest();
        require_once(__DIR__ . '/helper/testcourse.php');
    }

    /**
     * Test parameters.
     *
     * @return void
     * @covers \local_oer\services\get_file::service_parameters
     */
    public function test_service_parameters() {
        $parameters = \local_oer\services\get_file::service_parameters();
        $this->assertEquals('external_function_parameters', get_class($parameters));
        $this->assertArrayHasKey('courseid', $parameters->keys);
        $this->assertEquals('external_value', get_class($parameters->keys['courseid']));
        $this->assertEquals(PARAM_INT, $parameters->keys['courseid']->type);
        $this->assertArrayHasKey('contenthash', $parameters->keys);
        $this->assertEquals('external_value', get_class($parameters->keys['contenthash']));
        $this->assertEquals(PARAM_ALPHANUM, $parameters->keys['contenthash']->type);
    }

    /**
     * Test if service returns has all necessary fields defined.
     *
     * @return void
     * @covers \local_oer\services\get_file::service_returns
     * @covers \local_oer\services\get_file::external_file_return_value
     */
    public function test_service_returns() {
        $returnvalue = \local_oer\services\get_file::service_returns();
        $this->assertEquals('external_single_structure', get_class($returnvalue));
        $this->assertArrayHasKey('courseid', $returnvalue->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['courseid']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['courseid']->type);
        $this->assertArrayHasKey('context', $returnvalue->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['context']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['context']->type);
        $this->assertArrayHasKey('file', $returnvalue->keys);
        $this->assertEquals('external_single_structure', get_class($returnvalue->keys['file']));
        $this->assertArrayHasKey('id', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['id']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['id']->type);
        $this->assertArrayHasKey('contenthash', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['contenthash']));
        $this->assertEquals(PARAM_ALPHANUM, $returnvalue->keys['file']->keys['contenthash']->type);
        $this->assertArrayHasKey('title', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['title']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['title']->type);
        $this->assertArrayHasKey('mimetype', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['mimetype']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['mimetype']->type);
        $this->assertArrayHasKey('icon', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['icon']));
        $this->assertEquals(PARAM_RAW, $returnvalue->keys['file']->keys['icon']->type);
        $this->assertArrayHasKey('icontype', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['icontype']));
        $this->assertEquals(PARAM_ALPHA, $returnvalue->keys['file']->keys['icontype']->type);
        $this->assertArrayHasKey('iconisimage', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['iconisimage']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['iconisimage']->type);
        $this->assertArrayHasKey('timemodified', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['timemodified']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['timemodified']->type);
        $this->assertArrayHasKey('timeuploaded', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['timeuploaded']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['timeuploaded']->type);
        $this->assertArrayHasKey('timeuploadedts', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['timeuploadedts']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['timeuploadedts']->type);
        $this->assertArrayHasKey('upload', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['upload']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['upload']->type);
        $this->assertArrayHasKey('ignore', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['ignore']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['ignore']->type);
        $this->assertArrayHasKey('deleted', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['deleted']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['deleted']->type);

        $this->assertArrayHasKey('modules', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_multiple_structure', get_class($returnvalue->keys['file']->keys['modules']));
        $this->assertEquals('external_single_structure', get_class($returnvalue->keys['file']->keys['modules']->content));
        $this->assertArrayHasKey('moduleurl', $returnvalue->keys['file']->keys['modules']->content->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['modules']->content->keys['moduleurl']));
        $this->assertEquals(PARAM_URL, $returnvalue->keys['file']->keys['modules']->content->keys['moduleurl']->type);
        $this->assertArrayHasKey('modulename', $returnvalue->keys['file']->keys['modules']->content->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['modules']->content->keys['modulename']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['modules']->content->keys['modulename']->type);

        $this->assertEquals('external_multiple_structure', get_class($returnvalue->keys['file']->keys['sections']));
        $this->assertEquals('external_single_structure', get_class($returnvalue->keys['file']->keys['sections']->content));
        $this->assertArrayHasKey('sectionnum', $returnvalue->keys['file']->keys['sections']->content->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['sections']->content->keys['sectionnum']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['sections']->content->keys['sectionnum']->type);
        $this->assertArrayHasKey('sectionname', $returnvalue->keys['file']->keys['sections']->content->keys);
        $this->assertEquals('external_value',
                get_class($returnvalue->keys['file']->keys['sections']->content->keys['sectionname']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['sections']->content->keys['sectionname']->type);

        $this->assertArrayHasKey('requirementsmet', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['requirementsmet']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['requirementsmet']->type);
        $this->assertArrayHasKey('state', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['state']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['state']->type);
        $this->assertArrayHasKey('multiple', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['multiple']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['multiple']->type);
        $this->assertArrayHasKey('editor', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['editor']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['editor']->type);

        $this->assertEquals('external_multiple_structure', get_class($returnvalue->keys['file']->keys['courses']));
        $this->assertEquals('external_single_structure', get_class($returnvalue->keys['file']->keys['courses']->content));
        $this->assertArrayHasKey('id', $returnvalue->keys['file']->keys['courses']->content->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['courses']->content->keys['id']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['file']->keys['courses']->content->keys['id']->type);
        $this->assertArrayHasKey('name', $returnvalue->keys['file']->keys['courses']->content->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['courses']->content->keys['name']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['file']->keys['courses']->content->keys['name']->type);
        $this->assertArrayHasKey('editor', $returnvalue->keys['file']->keys['courses']->content->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['courses']->content->keys['editor']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['courses']->content->keys['editor']->type);

        $this->assertArrayHasKey('writable', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['writable']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['writable']->type);
        $this->assertArrayHasKey('coursetofile', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['coursetofile']));
        $this->assertEquals(PARAM_BOOL, $returnvalue->keys['file']->keys['coursetofile']->type);
        $this->assertArrayHasKey('wwwroot', $returnvalue->keys['file']->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['file']->keys['wwwroot']));
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
    public function test_service() {
        $this->setAdminUser();
        $helper = new \local_oer\testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $contenthash = $helper->get_contenthash_of_first_found_file($course);

        $result = \local_oer\services\get_file::service($course->id, $contenthash);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('courseid', $result);
        $this->assertEquals($course->id, $result['courseid']);
        $this->assertArrayHasKey('context', $result);
        $this->assertEquals(\context_course::instance($course->id)->id, $result['context']);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('contenthash', $result['file']);
        $this->assertEquals($contenthash, $result['file']['contenthash']);
        // Does it make sense to test all values here?
        // The service will ensure all fields that are set in service_returns are set.
        // An error will be thrown if a field is missing. Additional fields will be removed by external_api.
        // If the used function deliver the correct values is tested in the unit tests for those classes.

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('Contenthash cannot be empty for single file aquirement.');
        \local_oer\services\get_file::service($course->id, '');
    }
}
