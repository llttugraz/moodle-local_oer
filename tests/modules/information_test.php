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

use local_oer\modules\information;

/**
 * Test information class
 *
 * @coversDefaultClass \local_oer\modules\information
 */
final class information_test extends \advanced_testcase {
    /**
     * Set up test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test the set and get area method.
     *
     * @covers ::set_area
     * @covers ::get_area
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_set_get_area(): void {
        $information = new information();
        $information->set_area('pluginname', 'local_oer');
        $this->assertEquals(get_string('pluginname', 'local_oer'), $information->get_area());
    }

    /**
     * Test set and get name method.
     *
     * @covers ::set_name
     * @covers ::get_name
     *
     * @return void
     */
    public function test_set_get_name(): void {
        $information = new information();
        $information->set_name('abc');
        $this->assertEquals('abc', $information->get_name());
    }

    /**
     * Test set and get url, and has url method.
     *
     * @covers ::set_url
     * @covers ::get_url
     * @covers ::get_hasurl
     *
     * @return void
     * @throws \invalid_parameter_exception
     */
    public function test_set_get_url_and_hasurl(): void {
        $information = new information();
        $this->assertFalse($information->get_hasurl());
        $information->set_url('http://localhost');
        $this->assertEquals('http://localhost', $information->get_url());
        $this->assertTrue($information->get_hasurl());
        $information->set_url(null);
        $this->assertEmpty($information->get_url());
        $this->assertFalse($information->get_hasurl());
        $this->expectException('invalid_parameter_exception');
        $information->set_url('$nourl');
    }

    /**
     * Test get id method.
     *
     * @covers ::set_id
     * @covers ::get_id
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_set_get_id(): void {
        $information = new information();
        $this->assertNull($information->get_id());
        $information->set_area('pluginname', 'local_oer');
        $this->assertNull($information->get_id());
        $information->set_name('abc');
        $this->assertNotNull($information->get_id());
        $hash = sha1(get_string('pluginname', 'local_oer') . 'abc');
        $this->assertEquals($hash, $information->get_id());
    }
}
