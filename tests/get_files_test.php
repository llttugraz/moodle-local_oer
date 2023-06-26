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
 * @coversDefaultClass \local_oer\services\get_files
 */
class get_files_test extends \advanced_testcase {
    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        $this->resetAfterTest();
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');
        require_once(__DIR__ . '/helper/testcourse.php');
    }

    /**
     * Test parameters.
     *
     * @return void
     * @covers ::service_parameters
     */
    public function test_service_parameters() {
        $parameters = \local_oer\services\get_files::service_parameters();
        $this->assertEquals('external_function_parameters', get_class($parameters));
        $this->assertArrayHasKey('courseid', $parameters->keys);
        $this->assertEquals('external_value', get_class($parameters->keys['courseid']));
        $this->assertEquals(PARAM_INT, $parameters->keys['courseid']->type);
    }

    /**
     * Test if service returns has all necessary fields defined.
     *
     * @return void
     * @covers ::service_returns
     */
    public function test_service_returns() {
        $returnvalue = \local_oer\services\get_files::service_returns();
        $this->assertEquals('external_single_structure', get_class($returnvalue));
        $this->assertArrayHasKey('courseid', $returnvalue->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['courseid']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['courseid']->type);
        $this->assertArrayHasKey('context', $returnvalue->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['context']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['context']->type);

        $this->assertEquals('external_multiple_structure', get_class($returnvalue->keys['sections']));
        $this->assertEquals('external_single_structure', get_class($returnvalue->keys['sections']->content));
        $this->assertArrayHasKey('sectionnum', $returnvalue->keys['sections']->content->keys);
        $this->assertEquals('external_value', get_class($returnvalue->keys['sections']->content->keys['sectionnum']));
        $this->assertEquals(PARAM_INT, $returnvalue->keys['sections']->content->keys['sectionnum']->type);
        $this->assertArrayHasKey('sectionname', $returnvalue->keys['sections']->content->keys);
        $this->assertEquals('external_value',
                get_class($returnvalue->keys['sections']->content->keys['sectionname']));
        $this->assertEquals(PARAM_TEXT, $returnvalue->keys['sections']->content->keys['sectionname']->type);

        // The main difference to get_file is the multiple structure for the files values.
        $this->assertEquals('external_multiple_structure', get_class($returnvalue->keys['files']));
        $this->assertEquals('external_single_structure', get_class($returnvalue->keys['files']->content));
        $this->assertEquals('external_value', get_class($returnvalue->keys['files']->content->keys['contenthash']));
    }

    /**
     * Test service.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::service
     */
    public function test_service() {
        $this->setAdminUser();
        $helper = new \local_oer\testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $contenthash = $helper->get_contenthash_of_first_found_file($course);

        $result = \local_oer\services\get_files::service($course->id, $contenthash);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('courseid', $result);
        $this->assertEquals($course->id, $result['courseid']);
        $this->assertArrayHasKey('context', $result);
        $this->assertEquals(\context_course::instance($course->id)->id, $result['context']);
        $this->assertArrayHasKey('sections', $result);
        $this->assertIsArray($result['sections']);
        $this->assertCount(1, $result['sections']);
        $this->assertArrayHasKey('files', $result);
        $this->assertIsArray($result['files']);
        $this->assertCount(5, $result['files']);
    }
}
