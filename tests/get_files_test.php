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
 * Class get_files_test
 *
 * Very similar to get_file. So the differences will be tested. But shared function is not tested again.
 *
 * @runTestsInSeparateProcesses
 * @coversDefaultClass \local_oer\services\get_files
 */
final class get_files_test extends \advanced_testcase {
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
     * @covers \local_oer\services\get_files::service_parameters
     */
    public function test_service_parameters(): void {
        $parameters = \local_oer\services\get_files::service_parameters();
        $this->assertEquals($this->parameter, get_class($parameters));
        $this->assertArrayHasKey('courseid', $parameters->keys);
        $this->assertEquals($this->value, get_class($parameters->keys['courseid']));
        $this->assertEquals(PARAM_INT, $parameters->keys['courseid']->type);
    }

    /**
     * Test if service returns has all necessary fields defined.
     *
     * @return void
     * @covers \local_oer\services\get_files::service_returns
     */
    public function test_service_returns(): void {
        $returnvalue = \local_oer\services\get_files::service_returns();
        $this->assertEquals($this->single, get_class($returnvalue));
        $this->assertArrayHasKey('courseid', $returnvalue->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['courseid']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['courseid']->type);
        $this->assertArrayHasKey('context', $returnvalue->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['context']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['context']->type);

        // Update 2024-01-17 Sections have been removed due to the subplugin structure.
        // A more general approach has been implemented which result in the origin key.
        $this->assertEquals($this->multi, get_class($returnvalue->keys['origin']));
        $this->assertEquals($this->single, get_class($returnvalue->keys['origin']->content));
        $this->assertArrayHasKey('origin', $returnvalue->keys['origin']->content->keys);
        $this->assertEquals($this->value, get_class($returnvalue->keys['origin']->content->keys['origin']));
        $this->assertEquals(PARAM_ALPHANUMEXT, $returnvalue->keys['origin']->content->keys['origin']->type);
        $this->assertArrayHasKey('originname', $returnvalue->keys['origin']->content->keys);
        $this->assertEquals($this->value,
                get_class($returnvalue->keys['origin']->content->keys['originname']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['origin']->content->keys['originname']->type);

        // The main difference to get_file is the multiple structure for the files values.
        $this->assertEquals($this->multi, get_class($returnvalue->keys['files']));
        $this->assertEquals($this->single, get_class($returnvalue->keys['files']->content));
        $this->assertEquals($this->value, get_class($returnvalue->keys['files']->content->keys['contenthash']));
    }

    /**
     * Test service.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers \local_oer\services\get_files::service
     */
    public function test_service(): void {
        $this->setAdminUser();
        $helper = new \local_oer\testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());

        $result = \local_oer\services\get_files::service($course->id);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('courseid', $result);
        $this->assertEquals($course->id, $result['courseid']);
        $this->assertArrayHasKey('context', $result);
        $this->assertEquals(\context_course::instance($course->id)->id, $result['context']);
        $this->assertArrayHasKey('origin', $result);
        $this->assertIsArray($result['origin']);
        $this->assertCount(1, $result['origin']);
        $this->assertArrayHasKey('files', $result);
        $this->assertIsArray($result['files']);
        $this->assertCount(5, $result['files']);
    }
}
