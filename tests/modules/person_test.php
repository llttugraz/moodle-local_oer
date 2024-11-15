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

use local_oer\modules\person;

/**
 * Test element class
 *
 * @coversDefaultClass \local_oer\modules\person
 */
final class person_test extends \advanced_testcase {
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
     * Test the set role function of person class.
     *
     * @covers ::set_role
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_set_role(): void {
        $person = new person();
        $person->set_fullname('unit tester');
        $person->set_role('testuser');
        $result = $person->get_person_array();
        $this->assertEquals('testuser', $result->role);

        $this->expectException('invalid_parameter_exception');
        $person->set_role('test user');
    }

    /**
     * Test the set firstname function of person class.
     *
     * @covers ::set_firstname
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_set_firstname(): void {
        $person = new person();
        $person->set_role('testuser');
        $person->set_lastname('test');
        $person->set_firstname('unit');
        $result = $person->get_person_array();
        $this->assertEquals('unit', $result->firstname);

        $this->expectException('invalid_parameter_exception');
        $person->set_firstname('<body>unit</body>');
    }

    /**
     * Test the set lastname function of person class.
     *
     * @covers ::set_lastname
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_set_lastname(): void {
        $person = new person();
        $person->set_role('testuser');
        $person->set_firstname('unit');
        $person->set_lastname('test');
        $result = $person->get_person_array();
        $this->assertEquals('test', $result->lastname);

        $this->expectException('invalid_parameter_exception');
        $person->set_lastname('<img src="#">test</img>');
    }

    /**
     * Test the set fullname function of person class.
     *
     * @covers ::set_fullname
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_set_fullname(): void {
        $person = new person();
        $person->set_role('testuser');
        $person->set_fullname('unit tester');
        $result = $person->get_person_array();
        $this->assertEquals('unit tester', $result->fullname);

        $this->expectException('invalid_parameter_exception');
        $person->set_fullname('<a href="#">unit tester</a>');
    }

    /**
     * Test get person array function of person class.
     *
     * @covers ::get_person_array
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_get_person_array(): void {
        $person = new person();
        $person->set_role('testuser');
        $person->set_fullname('unit tester');
        $result = $person->get_person_array();
        $this->assertEquals('testuser', $result->role);
        $this->assertEquals('unit tester', $result->fullname);
        $this->assertFalse(isset($result->firstname));
        $this->assertFalse(isset($result->lastname));
        $this->assertIsString(json_encode($result));

        $person->set_firstname('another');
        $person->set_lastname('name');
        $result = $person->get_person_array();
        $this->assertEquals('testuser', $result->role);
        $this->assertEquals('another', $result->firstname);
        $this->assertEquals('name', $result->lastname);
        $this->assertFalse(isset($result->fullname));
    }

    /**
     * Test role exception in get person array function.
     *
     * @covers ::get_person_array
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_get_person_array_no_role(): void {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('A person must have a role');
        $person = new person();
        $person->get_person_array();
    }

    /**
     * Test name exception in get person array function.
     *
     * @covers ::get_person_array
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_get_person_array_no_name(): void {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('No name was set for the person');
        $person = new person();
        $person->set_role('testrole');
        $person->get_person_array();
    }
}
