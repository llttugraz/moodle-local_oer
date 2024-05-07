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
use local_oer\plugininfo\oerclassification;
use local_oer\plugininfo\oermod;

/**
 * Formular to define all necessary metadata fields.
 */
class preference_form extends \moodleform {
    /**
     * Mform definition function, required by moodleform.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function definition() {
        $mform = $this->_form;
        $course = $this->_customdata;

        global $DB;

        $entry = $DB->get_record('local_oer_preference', ['courseid' => $course['courseid']]);

        $mform->addElement('html', get_string('preferenceinfoformhelp', 'local_oer'));

        $data = [];
        $data['courseid'] = $course['courseid'];
        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);

        // For preferences, all available roles are shown. Only the roles supported will be added to the elements.
        $plugins = oermod::get_enabled_plugins();
        $roles = [];
        foreach ($plugins as $plugin => $name) {
            $subplugin = array_merge($roles, oermod::get_supported_roles("oermod_" . $plugin . "\\module"));
            foreach ($subplugin as $role) {
                $roles[$role[0]] = $role;
            }
        }
        $data['personroletypes'] = json_encode(array_values($roles));
        $data['creator'] = 'preference';

        if ($entry) {
            if (!is_null($entry->context)) {
                $data['context'] = $entry->context;
            }
            if (!is_null($entry->license)) {
                $data['license'] = $entry->license;
            }
            if (!is_null($entry->persons)) {
                $data['storedperson'] = $entry->persons;
            }
            if (!is_null($entry->tags)) {
                $data['storedtags'] = $entry->tags;
            }
            if (!is_null($entry->language)) {
                $data['language'] = $entry->language;
            }
            if (!is_null($entry->resourcetype)) {
                $data['resourcetype'] = $entry->resourcetype;
            }
            if (!is_null($entry->context)) {
                $data['context'] = $entry->context;
            }
            if (!is_null($entry->state)) {
                switch ($entry->state) {
                    case 0:
                    case 1:
                        $data['upload'] = 0; // Upload has been removed from preferences.
                        $data['ignore'] = 0;
                        break;
                    case 2:
                        $data['upload'] = 0;
                        $data['ignore'] = 1;
                }
            }
        }

        fileinfo_form::add_shared_fields_to_form($mform, true, []);
        $classificationdata = $entry && !is_null($entry->classification) ? json_decode($entry->classification) : null;
        fileinfo_form::prepare_classification_values_for_form($mform, $classificationdata, $data);

        $mform->addElement('checkbox', 'ignore', get_string('ignore', 'local_oer'));
        $mform->addHelpButton('ignore', 'ignore', 'local_oer');

        $mform->disable_form_change_checker();
        $this->set_data($data);
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
        $errors = [];
        // No release validation checks needed for preferences.
        return $errors;
    }

    /**
     * Update the formdata in the database.
     *
     * @param array $fromform
     * @return void
     * @throws \dml_exception
     */
    public function update_metadata(array $fromform): void {
        global $DB;
        $fromdb = $DB->get_record('local_oer_preference', ['courseid' => $fromform['courseid']]);
        $timestamp = time();
        if ($fromdb) {
            $record = $this->add_values_from_form($fromdb, $fromform, $timestamp);
            $DB->update_record('local_oer_preference', $record);
        } else {
            $record = new \stdClass();
            $record->courseid = $fromform['courseid'];
            $record = $this->add_values_from_form($record, $fromform, $timestamp);
            $record->timecreated = $timestamp;
            $DB->insert_record('local_oer_preference', $record);
        }
    }

    /**
     * Add the values from the form from frontend.
     *
     * @param \stdClass $record
     * @param array $fromform
     * @param int $timestamp
     * @return \stdClass
     */
    private function add_values_from_form(\stdClass $record, array $fromform, int $timestamp): \stdClass {
        global $USER;
        $record->context = $fromform['context'] == 'nopref' ? null : $fromform['context'];
        $record->license = $fromform['license'] == 'nopref' ? null : $fromform['license'];
        $record->persons = $fromform['storedperson'] == '' ? null : $fromform['storedperson'];
        $record->tags = $fromform['storedtags'] == '' ? null : $fromform['storedtags'];
        $record->language = $fromform['language'] == 'nopref' ? null : $fromform['language'];
        $record->resourcetype = $fromform['resourcetype'] == 'nopref' ? null : $fromform['resourcetype'];
        $record->state = $fromform['ignore'] == 1 ? 2 : null; // Upload cannot be set in preferences.
        $record->usermodified = $USER->id;
        $record->timemodified = $timestamp;
        $record->classification = fileinfo_form::prepare_classification_values_to_store($fromform);
        return $record;
    }
}
