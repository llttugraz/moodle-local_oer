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
 * Graz University of Technology specific subplugin for Open Educational Resources Plugin.
 *
 * @package    oermod_resource
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace oermod_resource;


use local_oer\testcourse;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/helper/testcourse.php');
require_once(__DIR__ . '/../../../tests/helper/fromform.php');

/**
 * Class info_test
 *
 * @coversDefaultClass \oermod_resource\module
 */
final class module_test extends \advanced_testcase {
    /**
     * The testcourse created in setup.
     *
     * @var array
     */
    private $course = null;

    /**
     * Set up a testing environment.
     *
     * @return void
     * @throws \dml_exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $this->course = $course;
    }

    /**
     * Test load_data method.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::load_elements
     * @covers ::set_element_to_release
     * @covers ::writable_fields
     * @covers ::supported_licences
     * @covers ::supported_roles
     * @covers ::write_to_source
     */
    public function test_load_elements(): void {
        $this->resetAfterTest();
        $module = new module();
        $elements = $module->load_elements($this->course->id);

        $this->assertEquals(count($elements), 5);
        foreach ($elements as $element) {
            $module->write_to_source($element);
        }

        $writeablefileds = $module->writable_fields();
        $this->assertEquals($writeablefileds, [['license', 'moodle']]);

        $supportedliscences = $module->supported_licences();
        $this->assertEquals(count($supportedliscences), 9);

        $supportedroles = $module->supported_roles();
        $this->assertEquals(count($supportedroles[0]), 4);
        $this->assertEquals(count($supportedroles[1]), 3);

        $this->assertEquals($module->set_element_to_release(iterator_to_array($elements)[0]), true);
    }
}
