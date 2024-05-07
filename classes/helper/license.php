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
 * Helper class to prepare courses for mustache template
 *
 * @package    local_oer
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/licenselib.php');

/**
 * Class license
 */
class license {
    /**
     * Test if the license is a creative commons license or public domain.
     *
     * TODO: eventually change the name of the function, as test_ is the naming convention for unit tests.
     *
     * @param string|null $licenseshort Shortname of license
     * @return bool
     * @throws \coding_exception
     */
    public static function test_license_correct_for_upload(?string $licenseshort): bool {
        $licenses = \license_manager::get_active_licenses();
        if (!isset($licenses[$licenseshort])) {
            // License has been deactivated or does not exist.
            return false;
        }
        return preg_match('/^(cc|CC|public)/', $licenseshort) == 1;
    }

    /**
     * Get the fullname of the license.
     *
     * @param string $licenseshort
     * @return string
     * @throws \coding_exception
     */
    public static function get_license_fullname(string $licenseshort): string {
        $licenses = \license_manager::get_active_licenses();
        foreach ($licenses as $key => $license) {
            if ($key == $licenseshort) {
                return $license->fullname;
            }
        }
        return get_string('licensenotfound', 'local_oer');
    }

    /**
     * Load a license with moodle license_manager.
     * This one line wrapper is used because of the require_once needed to load the manager.
     *
     * @param string $licenseshort Shortname of license
     * @return object|null
     */
    public static function get_license_by_shortname(string $licenseshort): ?object {
        return \license_manager::get_license_by_shortname($licenseshort);
    }

    /**
     * Prepare an associative array for frontend form select fields.
     *
     * @param bool $addnoprefval Bool value if a nopref value should be added to select.
     * @param array $supportedlicences A list of licences an element can have. If addnoprefval is true, this list is ignored.
     * @return array
     * @throws \coding_exception
     */
    public static function get_licenses_select_data(bool $addnoprefval, array $supportedlicences) {
        $licenses = \license_manager::get_active_licenses();
        $licenseselect = [];
        foreach ($licenses as $key => $license) {
            if ($addnoprefval == false && !in_array($key, $supportedlicences)) {
                continue;
            }
            $licenseselect[$key] = $license->fullname;
        }
        return $addnoprefval ? formhelper::add_no_preference_value($licenseselect) : $licenseselect;
    }
}
