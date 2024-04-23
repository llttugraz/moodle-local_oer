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

use local_oer\helper\license;
use local_oer\plugininfo\oermod;

/**
 * Class license_test
 *
 * @coversDefaultClass \local_oer\helper\license
 */
final class license_test extends \advanced_testcase {
    /**
     * Test license cc check.
     *
     * 2023-11-02 Moodle updated licenses from cc 3.0 to cc 4.0 so some tests have been updated.
     * https://tracker.moodle.org/browse/MDL-43195'
     *
     * @return void
     * @throws \coding_exception
     * @covers \local_oer\helper\license::test_license_correct_for_upload
     */
    public function test_test_license_correct_for_upload(): void {
        $this->resetAfterTest();
        $this->assertFalse(license::test_license_correct_for_upload('doesnotexist'));
        $this->assertFalse(license::test_license_correct_for_upload('allrightsreserved'));
        $this->assertFalse(license::test_license_correct_for_upload('cc-nc-sa'),
                'This test has been set to assert false on 2023-11-02 due to license change');
        $this->assertTrue(license::test_license_correct_for_upload('cc-nc-sa-4.0'),
                'Introduced on 2023-11-02 because of license change');
        $this->assertTrue(license::test_license_correct_for_upload('public'));
    }

    /**
     * Test get license fullname.
     *
     * @return void
     * @throws \coding_exception
     * @covers \local_oer\helper\license::get_license_fullname
     */
    public function test_get_license_fullname(): void {
        $this->resetAfterTest();
        $this->assertEquals('Creative Commons - NonCommercial-NoDerivatives 4.0 International',
                license::get_license_fullname('cc-nc-nd-4.0'),
                '2023-11-02 Updated because of license change');
        $this->assertEquals(get_string('licensenotfound', 'local_oer'), license::get_license_fullname('nope'));
    }

    /**
     * As get_license_by_shortname is only a wrapper for a core method there is not much to test here.
     *
     * @return void
     * @covers \local_oer\helper\license::get_license_by_shortname
     */
    public function test_get_license_by_shortname(): void {
        $this->resetAfterTest();
        $this->assertIsObject(license::get_license_by_shortname('allrightsreserved'));
        $this->assertNull(license::get_license_by_shortname('doesnotexist'));
    }

    /**
     * Test license select data
     *
     * @return void
     * @throws \coding_exception
     * @covers \local_oer\helper\license::get_licenses_select_data
     */
    public function test_get_license_select_data(): void {
        $this->resetAfterTest();
        global $CFG;
        require_once($CFG->libdir . '/licenselib.php');
        $licences = \license_manager::get_active_licenses_as_array();
        $supported = [];
        foreach ($licences as $key => $licence) {
            $supported[] = $key;
        }
        $list = license::get_licenses_select_data(false, $supported);
        $this->assertCount(9, $list, 'Test with the moodle default licenses');
        $this->assertArrayHasKey('cc-4.0', $list, '2023-11-02 Updated because of license change');
        $this->assertArrayHasKey('public', $list);
        $this->assertArrayHasKey('unknown', $list);
        $list = license::get_licenses_select_data(true, $supported);
        $this->assertCount(10, $list, 'Test with the moodle default licenses');
        $this->assertArrayHasKey('nopref', $list);
        $this->assertArrayHasKey('cc-nc-4.0', $list, '2023-11-02 Updated because of license change');
    }
}
