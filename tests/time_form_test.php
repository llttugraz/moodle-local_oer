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

use local_oer\time\time_form;
use local_oer\time\time_settings;

/**
 * Class time_form_test
 *
 * @coversDefaultClass \local_oer\time\time_form
 */
final class time_form_test extends \advanced_testcase {
    /**
     * Test the time form validation element.
     *
     * @return void
     * @throws \coding_exception
     * @covers ::validation
     * @covers ::definition
     */
    public function test_validation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $mform = new time_form(null, []);

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => '16.09;17.02;31.12;29.03;15.08',
        ];

        $this->assertEmpty($mform->validation($fromform, []));
        $error = get_string('customdates_error', 'local_oer')
                . get_string('customdates_help', 'local_oer');

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => '32.01',
        ];

        $result = $mform->validation($fromform, []);
        $this->assertArrayHasKey('customdates', $result);
        $this->assertEquals($error, $result['customdates']);

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => '15.00',
        ];

        $result = $mform->validation($fromform, []);
        $this->assertArrayHasKey('customdates', $result);
        $this->assertEquals($error, $result['customdates']);

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => '00.01',
        ];

        $result = $mform->validation($fromform, []);
        $this->assertArrayHasKey('customdates', $result);
        $this->assertEquals($error, $result['customdates']);

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => '16.08,17.09',
        ];

        $result = $mform->validation($fromform, []);
        $this->assertArrayHasKey('customdates', $result);
        $this->assertEquals($error, $result['customdates']);

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => '16.08;17.09;',
        ];

        $result = $mform->validation($fromform, []);
        $this->assertArrayHasKey('customdates', $result);
        $this->assertEquals($error, $result['customdates']);

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => '',
        ];

        $result = $mform->validation($fromform, []);
        $this->assertArrayHasKey('customdates', $result);
        $this->assertEquals($error, $result['customdates']);

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => 'aa.bb;cc.dd',
        ];

        $result = $mform->validation($fromform, []);
        $this->assertArrayHasKey('customdates', $result);
        $this->assertEquals($error, $result['customdates']);

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => '28.02',
        ];

        $this->assertEmpty($mform->validation($fromform, []));

        $fromform = [
                time_settings::CONF_RELEASETIME => time_settings::CUSTOM,
                time_settings::CONF_CUSTOMDATES => '29.02',
        ];

        $result = $mform->validation($fromform, []);
        $this->assertArrayHasKey('customdates', $result);
        $this->assertEquals($error, $result['customdates']);
    }
}
