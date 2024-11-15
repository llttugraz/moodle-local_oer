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

defined('MOODLE_INTERNAL') || die();

use local_oer\helper\filehelper;

require_once(__DIR__ . '/helper/testcourse.php');

/**
 * Class filehelper_test
 *
 * @coversDefaultClass \local_oer\helper\filehelper
 */
final class filehelper_test extends \advanced_testcase {
    /**
     * Test different integer sizes to be converted to human readable filesize.
     *
     * @return void
     * @covers \local_oer\helper\filehelper::get_readable_filesize
     */
    public function test_get_readable_filesize(): void {
        $this->resetAfterTest();

        $this->assertEquals('7.0 Byte', filehelper::get_readable_filesize(7));
        $this->assertEquals('7.0 b', filehelper::get_readable_filesize(7, true));

        $this->assertEquals('1.4 Kilobyte', filehelper::get_readable_filesize(1411));
        $this->assertEquals('1.4 Kb', filehelper::get_readable_filesize(1411, true));

        $this->assertEquals('36.3 Megabyte', filehelper::get_readable_filesize(36312312));
        $this->assertEquals('128.8 Mb', filehelper::get_readable_filesize(128781999, true));

        $this->assertEquals('363.5 Gigabyte', filehelper::get_readable_filesize(363512312123));
        $this->assertEquals('7.6 Gb', filehelper::get_readable_filesize(7600000000, true));

        $this->assertEquals('123.5 Terabyte', filehelper::get_readable_filesize(123456789123456));
        $this->assertEquals('1,255.8 Tb', filehelper::get_readable_filesize(1255756789123456, true));

        // Test if zero or negative numbers does not lead to an error. Even if these use cases do not make sense.
        $this->assertEquals('0.0 Byte', filehelper::get_readable_filesize(0));
        $this->assertEquals('-1.0 Byte', filehelper::get_readable_filesize(-1));
        $this->assertEquals('-123,456,789,123,456.0 Byte', filehelper::get_readable_filesize(-123456789123456));
    }

    /**
     * Test file url creation.
     *
     * As the get_file_url method is only a wrapper for a moodle api. The exact result is not tested.
     *
     * @return void
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     * @covers \local_oer\helper\filehelper::get_file_url
     */
    public function test_get_file_url(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();

        $testcourse = new testcourse();
        [, $file] = $testcourse->generate_file('unittest');
        $url = filehelper::get_file_url($file);
        $this->assertIsObject($url);
        if ($CFG->version < 2024100700) {
            $this->assertEquals('moodle_url', get_class($url));
        } else {
            $this->assertEquals('core\url', get_class($url));
        }

        $urlstring = filehelper::get_file_url($file, true);
        $this->assertIsString($urlstring);
    }
}
