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
 * Class oer_config_link_test
 *
 * @coversDefaultClass \local_oer\time\oer_config_link
 */
final class oer_config_link_test extends \advanced_testcase {
    /**
     * Test the time form validation element.
     *
     * @return void
     * @throws \coding_exception
     * @covers ::__construct
     * @covers ::output_html
     */
    public function test_output_html(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $url = new \moodle_url('');
        $setting = new \local_oer\time\oer_config_link('unittest', 'Unit test', $url);
        $this->assertIsString($setting->output_html([]));
    }
}
