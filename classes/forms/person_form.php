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
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\forms;

use local_oer\helper\formhelper;

/**
 * Formular to define all necessary metadata fields.
 */
class person_form extends \moodleform {
    /**
     * Mform definition function, required by moodleform.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('select', 'role', get_string('role', 'local_oer'), formhelper::lom_role_types());
        $mform->setDefault('role', 'Author');

        $mform->addElement('text', 'firstname', get_string('firstname'));
        $mform->addRule('firstname', get_string('required'), 'required', '', 'client');
        $mform->addRule('firstname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'lastname', get_string('lastname'));
        $mform->addRule('lastname', get_string('required'), 'required', '', 'client');
        $mform->addRule('lastname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->disable_form_change_checker();
    }

    /**
     * Moodle mform validation method.
     *
     * @param array $data
     * @param array $files
     * @return array
     * @throws \coding_exception
     */
    public function validation($data, $files) {
        return [];
    }
}
