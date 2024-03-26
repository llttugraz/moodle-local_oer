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

namespace local_oer\modules;

/**
 * Class person
 *
 * A person object consists of a name and a role.
 * The name can be set as first- and lastname or as full name.
 * First- and lastname will be preferred.
 */
class person {
    /**
     * Shortname of a role.
     *
     * Types of roles are set in sub-plugins.
     *
     * @var string|null
     */
    private ?string $role = null;

    /**
     * Firstname of a person.
     *
     * @var string|null
     */
    private ?string $firstname = null;

    /**
     * Lastname of a person.
     *
     * @var string|null
     */
    private ?string $lastname = null;

    /**
     * Full name of a person.
     *
     * Sometimes only a full name is available. It is not easy to separate first- and lastname from a given full name.
     *
     * @var string|null
     */
    private ?string $fullname = null;

    /**
     * Set the shortname of a role.
     *
     * This is the first parameter from the supported_roles() function implemented in sub-plugins.
     *
     * @param string $role
     * @return void
     * @throws \invalid_parameter_exception
     */
    public function set_role(string $role): void {
        validate_param($role, PARAM_ALPHA);
        $this->role = $role;
    }

    /**
     * Firstname of a person.
     *
     * @param string $firstname
     * @return void
     * @throws \invalid_parameter_exception
     */
    public function set_firstname(string $firstname): void {
        validate_param($firstname, PARAM_TEXT);
        $this->firstname = $firstname;
    }

    /**
     * Lastname of a person.
     *
     * @param string $lastname
     * @return void
     * @throws \invalid_parameter_exception
     */
    public function set_lastname(string $lastname): void {
        validate_param($lastname, PARAM_TEXT);
        $this->lastname = $lastname;
    }

    /**
     * Full name of a person.
     *
     * @param string $fullname
     * @return void
     * @throws \invalid_parameter_exception
     */
    public function set_fullname(string $fullname): void {
        validate_param($fullname, PARAM_TEXT);
        $this->fullname = $fullname;
    }

    /**
     * Return stdClass of person prepared for json encoding.
     *
     * @return \stdClass
     * @throws \coding_exception
     */
    public function get_person_array(): \stdClass {
        if (!isset($this->role)) {
            throw new \coding_exception('A person must have a role');
        }
        $state = 0;
        if (isset($this->firstname) && isset($this->lastname)) {
            $state = 1; // Prefer first- and lastname over fullname.
        } else if (isset($this->fullname)) {
            $state = 2;
        }
        switch ($state) {
            case 1:
                return (object) [
                        'role' => $this->role,
                        'firstname' => $this->firstname,
                        'lastname' => $this->lastname,
                ];
            case 2:
                return (object) [
                        'role' => $this->role,
                        'fullname' => $this->fullname,
                ];
            case 0:
            default:
                throw new \coding_exception('No name was set for the person.');
        }
    }
}
