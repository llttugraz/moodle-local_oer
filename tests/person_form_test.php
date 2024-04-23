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

use local_oer\forms\person_form;

/**
 * Class person_form_test
 *
 * @coversDefaultClass \local_oer\forms\person_form
 */
final class person_form_test extends \advanced_testcase {
    /**
     * Just to run through the code and test for PHP and Moodle warnings/errors.
     *
     * @return void
     * @covers \local_oer\forms\person_form::validation
     * @covers \local_oer\forms\person_form::definition
     */
    public function test_validation(): void {
        $this->resetAfterTest();
        $mform = new person_form(null, ['creator' => 'preference']);
        $result = $mform->validation([], []);
        $this->assertEmpty($result);
    }
}
