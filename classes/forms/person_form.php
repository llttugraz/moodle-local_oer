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
use local_oer\plugininfo\oermod;

/**
 * Formular to define all necessary metadata fields.
 */
class person_form extends \moodleform {
    /**
     * Mform definition function, required by moodleform.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function definition(): void {
        $mform = $this->_form;
        $args = $this->_customdata;
        $allroles = [];
        $head = [];
        $tabledata = [];
        $plugins = oermod::get_enabled_plugins();
        foreach ($plugins as $plugin => $name) {
            $skip = false;
            switch ($plugin) {
                case 'folder':
                    $sourcename = get_string('pluginname', 'mod_folder');
                    $skip = true; // Prevent misleading labeling.
                    break;
                case 'resource':
                    $sourcename = get_string('pluginname', 'mod_resource');
                    break;
                default:
                    $sourcename = get_string('origin', "oermod_$plugin");
            }
            if (!$skip) {
                $head[] = $sourcename;
            }
            $rolecase = [];
            $supportedroles = oermod::get_supported_roles("oermod_" . $plugin . "\\module");
            foreach ($supportedroles as $role) {
                $allroles[$role[0]] = $role;
                $rolename = get_string($role[1], $role[2]);
                $rolecase[] = count($role) == 4 ? "*$rolename" : $rolename;
            }
            if (!$skip) {
                $tabledata[] = $rolecase;
            }
        }

        $tabledata = array_map(null, ...$tabledata); // Transpose the array.

        if ($args['creator'] == 'preference') {
            global $OUTPUT;
            $mform->addElement('html', $OUTPUT->render_from_template('local_oer/roletable', [
                    'head' => $head,
                    'data' => $tabledata,
            ]));
            $roles = $allroles;
        } else {
            $roles = oermod::get_supported_roles($args['creator']);
        }

        $mform->addElement('select', 'role', get_string('role', 'local_oer'), formhelper::lom_role_types($roles));
        $mform->setDefault('role', 'Author');
        $mform->addHelpButton('role', 'role', 'local_oer');

        $mform->addElement('text', 'firstname', get_string('firstname'));
        $mform->addRule('firstname', get_string('required'), 'required', '', 'client');
        $mform->addRule('firstname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setType('firstname', PARAM_TEXT);

        $mform->addElement('text', 'lastname', get_string('lastname'));
        $mform->addRule('lastname', get_string('required'), 'required', '', 'client');
        $mform->addRule('lastname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setType('lastname', PARAM_TEXT);

        $mform->disable_form_change_checker();
    }

    /**
     * Moodle mform validation method.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        return [];
    }
}
