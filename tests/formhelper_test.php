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

use local_oer\helper\formhelper;

/**
 * Class formhelper_test
 *
 * @coversDefaultClass \local_oer\helper\formhelper
 */
final class formhelper_test extends \advanced_testcase {
    /**
     * Test add no preference value.
     *
     * @return void
     * @throws \coding_exception
     * @covers \local_oer\helper\formhelper::add_no_preference_value
     */
    public function test_add_no_preference_value(): void {
        $this->resetAfterTest();
        $value = [];
        $value = formhelper::add_no_preference_value($value);
        $this->assertCount(1, $value);
        $this->assertArrayHasKey('nopref', $value);
        $this->assertEquals(get_string('nopreference', 'local_oer'), $value['nopref']);
    }

    /**
     * Test context list
     *
     * @return void
     * @throws \coding_exception
     * @covers \local_oer\helper\formhelper::lom_context_list
     */
    public function test_lom_context_list(): void {
        $this->resetAfterTest();
        $lom = formhelper::lom_context_list(false, false);
        $this->assertCount(2, $lom);
        $this->assertArrayHasKey('0', $lom);
        $this->assertEquals('No selection', $lom[0]);
        $this->assertArrayHasKey('1', $lom);
        $this->assertEquals('Higher Education', $lom[1]);
        $lom = formhelper::lom_context_list(false, true);
        $this->assertCount(3, $lom);
        $this->assertArrayHasKey('0', $lom);
        $this->assertEquals('No selection', $lom[0]);
        $this->assertArrayHasKey('1', $lom);
        $this->assertEquals('Higher Education', $lom[1]);
        $this->assertArrayHasKey('nopref', $lom);
        $this->assertEquals(get_string('nopreference', 'local_oer'), $lom['nopref']);
        $lom = formhelper::lom_context_list(true, false);
        $this->assertCount(2, $lom);
        $this->assertArrayHasKey('0', $lom);
        $this->assertEquals(get_string('noselection', 'local_oer'), $lom[0]);
        $this->assertArrayHasKey('1', $lom);
        $this->assertEquals(get_string('highereducation', 'local_oer'), $lom[1]);
    }

    /**
     * Test resource types
     *
     * @return void
     * @throws \coding_exception
     * @covers \local_oer\helper\formhelper::lom_resource_types
     */
    public function test_lom_resource_types(): void {
        $this->resetAfterTest();
        $types = formhelper::lom_resource_types(false, false);
        $this->assertCount(15, $types);
        $this->assertEquals('Narrative', $types[3]);
        $types = formhelper::lom_resource_types(false, true);
        $this->assertCount(16, $types);
        $this->assertArrayHasKey('nopref', $types);
        $this->assertEquals(get_string('nopreference', 'local_oer'), $types['nopref']);
        $this->assertEquals('Exercise', $types[13]);
        $types = formhelper::lom_resource_types(true, false);
        $this->assertCount(15, $types);
        $this->assertEquals(get_string('exam', 'local_oer'), $types[10]);
        $this->assertEquals(get_string('experiment', 'local_oer'), $types[4]);
        $this->assertEquals(get_string('chart', 'local_oer'), $types[12]);
    }

    /**
     * Test role types.
     *
     * @return void
     * @throws \coding_exception
     * @covers \local_oer\helper\formhelper::lom_role_types
     */
    public function test_lom_roles_types(): void {
        $this->resetAfterTest();
        $roles = [
                ['Author', 'author', 'local_oer'],
                ['Publisher', 'publisher', 'local_oer'],
        ];
        $types = formhelper::lom_role_types($roles);
        $this->assertCount(2, $types);
        $this->assertArrayHasKey('Author', $types);
        $this->assertArrayHasKey('Publisher', $types);
        $this->assertEquals(get_string('author', 'local_oer'), $types['Author']);
        $this->assertEquals(get_string('publisher', 'local_oer'), $types['Publisher']);
        $types = formhelper::lom_role_types($roles, true);
        $this->assertCount(3, $types);
        $this->assertArrayHasKey('Author', $types);
        $this->assertArrayHasKey('Publisher', $types);
        $this->assertArrayHasKey('nopref', $types);
        $this->assertEquals(get_string('nopreference', 'local_oer'), $types['nopref']);
        $this->assertEquals(get_string('author', 'local_oer'), $types['Author']);
        $this->assertEquals(get_string('publisher', 'local_oer'), $types['Publisher']);
    }

    /**
     * Test language selection data.
     *
     * @return void
     * @throws \coding_exception
     * @covers \local_oer\helper\formhelper::language_select_data
     */
    public function test_language_select_data(): void {
        $this->resetAfterTest();
        $languages = formhelper::language_select_data(false);
        $count = count($languages);
        $this->assertEquals('no selection', $languages[0]);
        unset($languages[0]);
        foreach ($languages as $code => $language) {
            $this->assertEquals(2, strlen($code), "Language code with more than two character: $code - $language");
        }
        $languages = formhelper::language_select_data(true);
        $this->assertCount($count + 1, $languages);
    }
}
