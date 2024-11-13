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

use oermod_resource\privacy\provider;

/**
 * Class provider_test
 *
 * @coversDefaultClass \oermod_resource\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * Test string of language identifier.
     *
     * @return void
     * @covers ::get_reason
     */
    public function test_get_reason(): void {
        $this->resetAfterTest();
        $this->assertEquals('privacy:metadata', provider::get_reason());
    }
}
