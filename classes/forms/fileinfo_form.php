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

use local_oer\filelist;
use local_oer\helper\filestate;
use local_oer\helper\formhelper;
use local_oer\helper\license;
use local_oer\logger;
use local_oer\modules\module;
use local_oer\plugininfo\oerclassification;
use local_oer\plugininfo\oermod;

/**
 * Formular to define all necessary metadata fields.
 */
class fileinfo_form extends \moodleform {
    /**
     * Mform definition function, required by moodleform.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function definition() {
        $mform = $this->_form;
        $course = $this->_customdata;

        global $DB, $OUTPUT;
        $reqfields = static::get_required_fields();

        $element = filelist::get_single_file($course['courseid'], $course['identifier']);
        $metadata = $element->get_stored_metadata();
        $alreadystored = $element->already_stored();
        $roles = oermod::get_supported_roles($element->get_subplugin());

        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'identifier', null);
        $mform->setType('identifier', PARAM_TEXT);

        if (!$element->get_elementstate()->writable) {
            $mform->addElement('hidden', 'nosave', true);
            $mform->setType('identifier', PARAM_BOOL);
            $notwritable = filestate::formatted_notwritable_output_html($element);
            $mform->addElement('html', $notwritable);
            return;
        }

        $preference = $DB->get_record('local_oer_preference', ['courseid' => $course['courseid']]);

        $mform->addElement('text', 'title', get_string('title', 'local_oer'), 'wrap="virtual"');
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required', '', 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('title', 'title', 'local_oer');

        $mform->addElement('textarea', 'description', get_string('filedescription', 'local_oer'),
                'wrap="virtual" rows="3" cols="60"');
        $mform->addHelpButton('description', 'filedescription', 'local_oer');
        if (in_array('description', $reqfields)) {
            $mform->addRule('description', get_string('required'), 'required', '', 'client');
        }

        $data = [];
        $data['courseid'] = $course['courseid'];
        $data['identifier'] = $course['identifier'];
        $data['title'] = $element->get_title();
        $data['description'] = $metadata->description ?? '';
        // For some fields, there are three possibilities.
        // Either default defined for form is used.
        // Or it has stored already then the fromdb value is used.
        // Or it has not been stored, and a preference value is present for that field.
        if ($alreadystored) {
            $data['context'] = $metadata->context;
        } else if ($preference && !is_null($preference->context)) {
            $data['context'] = $preference->context;
        }
        if ($alreadystored) {
            $data['license'] = $element->get_license();
        } else if ($preference && !is_null($preference->license)) {
            $data['license'] = $preference->license;
        } else {
            $data['license'] = $element->get_license();
        }

        // In preferences, all roles from all activated sub-plugins can be prepared.
        // Only add persons with roles that are usable for the current used sub-plugin.
        $persons = [];
        if ($preference && !is_null($preference->persons)) {
            $prefpersons = json_decode($preference->persons);
            $persons = array_merge($persons, $this->add_people($prefpersons->persons, $roles));
        }
        // Add people from the element if the element has not been stored already.
        if (!$alreadystored) {
            $persons = array_merge($persons, $this->add_people($element->get_people(), $roles));
        }

        $this->try_to_remove_duplicates($persons);

        $preferencepersons = new \stdClass();
        $preferencepersons->persons = $persons;
        $data['storedperson'] = $metadata->persons ?? '';
        $data['storedperson'] = !$alreadystored && $preference && !is_null($preference->persons)
                ? json_encode($preferencepersons) : $data['storedperson'];
        $data['storedtags'] = $metadata->tags ?? '';
        $data['storedtags'] = !$alreadystored && $preference && !is_null($preference->tags)
                ? $preference->tags : $data['storedtags'];
        if ($alreadystored && !empty($metadata->language)) {
            $data['language'] = $metadata->language;
        } else if ($preference && !is_null($preference->language)) {
            $data['language'] = $preference->language;
        }
        if ($alreadystored) {
            $data['resourcetype'] = $metadata->resourcetype;
        } else if ($preference && !is_null($preference->resourcetype)) {
            $data['resourcetype'] = $preference->resourcetype;
        }

        if ($alreadystored) {
            $this->set_state($data, $metadata->releasestate);
        } else if ($preference && !is_null($preference->releasestate)) {
            $this->set_state($data, $preference->releasestate);
        }

        $supportedlicences = oermod::get_supported_licences($element);
        self::add_shared_fields_to_form($mform, false, $supportedlicences);
        $classificationdata = $alreadystored ? $metadata->classification : null;
        $classificationdata = !$alreadystored && $preference && !is_null($preference->classification)
                ? $preference->classification : $classificationdata;
        $classificationdata = !is_null($classificationdata) ? json_decode($classificationdata) : null;
        self::prepare_classification_values_for_form($mform, $classificationdata, $data);

        $mform->addElement('checkbox', 'upload', get_string('upload', 'local_oer'));
        $mform->addHelpButton('upload', 'upload', 'local_oer');
        $mform->disabledIf('upload', 'ignore', 'checked');
        $mform->addElement('checkbox', 'ignore', get_string('ignore', 'local_oer'));
        $mform->addHelpButton('ignore', 'ignore', 'local_oer');
        $mform->disabledIf('ignore', 'upload', 'checked');

        $prefhtml = $OUTPUT->render_from_template('local_oer/preferenceform',
                [
                        'enabled' => $preference !== false,
                        'saved' => $alreadystored,
                        'identifier' => $course['identifier'],
                        'courseid' => $course['courseid'],
                ]);
        $mform->addElement('html', $prefhtml);

        if ($writablefields = oermod::get_writable_fields($element)) {
            $info = $OUTPUT->render_from_template('local_oer/info', [
                    'text' => $writablefields,
                    'type' => 'warning',
            ]);
            $mform->addElement('html', $info);
        }

        $data['creator'] = $element->get_subplugin();
        $data['personroletypes'] = json_encode($roles);

        $mform->disable_form_change_checker();
        $this->set_data($data);
    }

    /**
     * Add people to the form from a given source.
     *
     * @param array $people
     * @param array $roles
     * @return array
     */
    private function add_people(array $people, array $roles): array {
        $persons = [];
        foreach ($people as $person) {
            $found = false;
            foreach ($roles as $role) {
                if ($person->role == $role[0]) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                $persons[] = $person;
            }
        }
        return $persons;
    }

    /**
     * Basic test to find duplicates.
     *
     * Names can be set as first- and lastname, or as full name.
     * This leads to the assumption that full names also are in the order firstname lastname.
     *
     * @param array $people
     * @return void
     */
    private function try_to_remove_duplicates(array &$people) {
        foreach ($people as $key => $person) {
            foreach ($people as $innerkey => $compare) {
                if ($innerkey == $key) {
                    continue;
                }
                $compare1 = $person->role . ($person->fullname ?? $person->firstname . $person->lastname);
                $compare2 = $compare->role . ($compare->fullname ?? $compare->firstname . $compare->lastname);
                if ($compare1 == $compare2) {
                    // Found a duplicate, remove the full name version if any.
                    if (isset($person->fullname)) {
                        unset($people[$key]);
                    } else if (isset($compare->fullname)) {
                        unset($people[$innerkey]);
                    } else {
                        unset($people[$innerkey]);
                    }
                }
            }
        }
    }

    /**
     * Get list of required fields from settings.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_required_fields() {
        $requiredfields = get_config('local_oer', 'requiredfields');
        return explode(',', $requiredfields);
    }

    /**
     * As the preference form and the fileinfo form are nearly the same, a shared function has been introduced
     * to add all similar fields to both of the formulars.
     *
     * @param \MoodleQuickForm $mform
     * @param bool $addnopref True when an additional nopref field should be added to select fields.
     * @param array $supportedlicences A list of licenses supported for this element.
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function add_shared_fields_to_form(\MoodleQuickForm $mform, bool $addnopref, array $supportedlicences) {
        $reqfields = static::get_required_fields();
        $mform->addElement('select', 'context', get_string('context', 'local_oer'),
                formhelper::lom_context_list(true, $addnopref));
        $mform->setDefault('context', $addnopref ? 'nopref' : 1);
        $mform->addHelpButton('context', 'context', 'local_oer');
        if (in_array('context', $reqfields)) {
            $mform->addRule('context', get_string('required'), 'required', '', 'client');
        }

        $mform->addElement('select', 'license', get_string('license'),
                license::get_licenses_select_data($addnopref, $supportedlicences));
        $mform->setDefault('license', $addnopref ? 'nopref' : 'unknown');
        $mform->addHelpButton('license', 'license', 'local_oer');
        $mform->addRule('license', get_string('required'), 'required', '', 'client');

        $mform->addElement('html', '<hr>');
        $mform->addElement('hidden', 'storedperson', '');
        $mform->setType('storedperson', PARAM_TEXT);
        $mform->addElement('static', 'storedperson_tagarea', '', '<div id="local_oer_storedperson_tagarea"></div>');
        // Update 2024-02-09 Roles are now handled by subplugins, and these fields extend the dynamic behavior of it.
        // The values for these fields are set in the individual setup of the form for elements and preferences.
        $mform->addElement('hidden', 'creator', null);
        $mform->setType('creator', PARAM_TEXT);
        $mform->addElement('hidden', 'personroletypes', null);
        $mform->setType('personroletypes', PARAM_TEXT);

        global $OUTPUT;
        $prefhtml = $OUTPUT->render_from_template('local_oer/personbutton', []);
        $mform->addElement('static', 'addpersons', get_string('person', 'local_oer'), $prefhtml);
        $mform->addHelpButton('addpersons', 'person', 'local_oer');
        $mform->addElement('html', '<hr>');
        $mform->addRule('addpersons', get_string('required'), 'required', '');

        $mform->addElement('hidden', 'storedtags', '');
        $mform->setType('storedtags', PARAM_TEXT);
        $mform->addElement('static', 'storedtags_tagarea', '', '<div id="local_oer_storedtags_tagarea"></div>');
        $mform->addElement('text', 'tags', get_string('tags', 'local_oer'));
        $mform->addRule('tags', get_string('pressenter', 'local_oer'), 'maxlength', 0, 'client');
        $mform->setType('tags', PARAM_TEXT);
        $mform->addHelpButton('tags', 'tags', 'local_oer');
        $mform->addElement('html', '<hr>');
        if (in_array('tags', $reqfields)) {
            $mform->addRule('tags', get_string('required'), 'required', '', 'client');
        }

        $mform->addElement('select', 'language', get_string('language', 'local_oer'),
                formhelper::language_select_data($addnopref));
        $mform->setDefault('language', $addnopref ? 'nopref' : 'de');
        $mform->addHelpButton('language', 'language', 'local_oer');
        if (in_array('language', $reqfields)) {
            $mform->addRule('language', get_string('required'), 'required', '', 'client');
        }

        $mform->addElement('select', 'resourcetype', get_string('resourcetype', 'local_oer'),
                formhelper::lom_resource_types(true, $addnopref));
        $mform->setDefault('resourcetype', $addnopref ? 'nopref' : 0);
        $mform->addHelpButton('resourcetype', 'resourcetype', 'local_oer');
        if (in_array('resourcetype', $reqfields)) {
            $mform->addRule('resourcetype', get_string('required'), 'required', '', 'client');
        }
    }

    /**
     * Set the checkboxes according to the stored state 0, 1 or 2
     * 0 ... no checkbox selected
     * 1 ... upload selected
     * 2 ... ignore selected
     *
     * @param array $data Formular data to set
     * @param int $state State of upload/ignore 0,1 or 2
     * @return void
     */
    private function set_state(array &$data, int $state) {
        switch ($state) {
            case 0:
                $data['upload'] = 0;
                $data['ignore'] = 0;
                break;
            case 1:
                $data['upload'] = 1;
                $data['ignore'] = 0;
                break;
            case 2:
                $data['upload'] = 0;
                $data['ignore'] = 1;
        }
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
        $reqfields = static::get_required_fields();
        if (isset($data['upload']) && isset($data['ignore']) && $data['upload'] == 1 && $data['ignore'] == 1) {
            $errors['ignore'] = get_string('uploadignoreerror', 'local_oer');
        }

        if (isset($data['upload']) && $data['upload'] == 1) {
            if (!license::test_license_correct_for_upload($data['license'])) {
                $errors['upload'] = get_string('error_upload_license', 'local_oer');
                $errors['license'] = get_string('error_license', 'local_oer');
            }

            $persons = json_decode($data['storedperson']);
            $roles = oermod::get_supported_roles($data['creator']);
            $requiredroles = [];
            $supportedroles = [];
            foreach ($roles as $role) {
                $supportedroles[$role[0]] = get_string($role[1], $role[2]);
                if (isset($role[3]) && $role[3] == module::ROLE_REQUIRED) {
                    $requiredroles[$role[0]] = get_string($role[1], $role[2]);
                }
            }
            $rolesstring = empty($requiredroles)
                    ? ['roles' => implode(' or ', $supportedroles)]
                    : ['roles' => implode(' or ', $requiredroles)];
            if (empty($persons->persons)) {
                $errors['addpersons'] = get_string('error_upload_author', 'local_oer', $rolesstring);
            } else if (!empty($requiredroles)) {
                $found = false;
                foreach ($persons->persons as $person) {
                    if (isset($requiredroles[$person->role])) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $errors['addpersons'] = get_string('error_upload_author', 'local_oer', $rolesstring);
                }
            }
            if (in_array('context', $reqfields) && $data['context'] < 1) {
                $errors['context'] = get_string('error_upload_context', 'local_oer');
            }
            if (in_array('tags', $reqfields) && empty($data['storedtags'])) {
                $errors['tags'] = get_string('error_upload_tags', 'local_oer');
            }
            if (in_array('description', $reqfields) && empty($data['description'])) {
                $errors['description'] = get_string('error_upload_description', 'local_oer');
            }
            if (in_array('language', $reqfields) && $data['language'] === "0") {
                $errors['language'] = get_string('error_upload_language', 'local_oer');
            }
            if (in_array('resourcetype', $reqfields) && $data['resourcetype'] < 1) {
                $errors['resourcetype'] = get_string('error_upload_resourcetype', 'local_oer');
            }
            $classifications = \local_oer\plugininfo\oerclassification::get_enabled_plugins();
            foreach ($classifications as $key => $classplugin) {
                // @codeCoverageIgnoreStart
                // This code is not reachable without subplugins installed.
                if (in_array('oerclassification_' . $key, $reqfields) &&
                        (empty($data['oerclassification_' . $key]) ||
                                $data['oerclassification_' . $key] == '_qf__force_multiselect_submission')) {
                    $errors['oerclassification_' . $key] = get_string('error_upload_classification', 'local_oer');
                }
                // @codeCoverageIgnoreEnd
            }

        }
        if (empty($data['title'])) {
            $errors['title'] = get_string('required');
        }
        if (strlen($data['title']) > 255) {
            $errors['title'] = get_string('maximumchars', '', 255);
        }
        return $errors;
    }

    /**
     * Update the formdata in the database.
     *
     * @param array $fromform
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function update_metadata(array $fromform) {
        global $DB;
        $fromdb = $DB->get_record('local_oer_elements', [
                'courseid' => $fromform['courseid'], // Only update if the course is the editor.
                'identifier' => $fromform['identifier'],
        ]);

        $timestamp = time();
        if ($fromdb) {
            $record = $this->add_values_from_form($fromdb, $fromform, $timestamp);
            $DB->update_record('local_oer_elements', $record);
        } else {
            $record = new \stdClass();
            $record->courseid = $fromform['courseid'];
            $record->identifier = $fromform['identifier'];
            $record = $this->add_values_from_form($record, $fromform, $timestamp);
            $record->timecreated = $timestamp;
            // Update 15.11.2022: File in multiple courses https://github.com/llttugraz/moodle-local_oer/issues/14 .
            // Check if the identifier is not already stored with another courseid.
            if ($DB->get_record('local_oer_elements', ['identifier' => $record->identifier])) {
                logger::add($record->courseid, logger::LOGERROR,
                        'Tried to create duplicate file entry for file ' . $record->identifier . '.' .
                        'This code should not be reachable');
                return;
            }
            $DB->insert_record('local_oer_elements', $record);
        }
        // Update metadata in external sources if necessary.
        $elements = filelist::get_course_files($fromform['courseid']);
        $element = $elements->find_element('identifier', $fromform['identifier']);
        oermod::write_external_metadata($element);
    }

    /**
     * Add the values from the form from frontend.
     *
     * @param \stdClass $record
     * @param array $fromform
     * @param int $timestamp
     * @return mixed
     */
    private function add_values_from_form(\stdClass $record, array $fromform, int $timestamp) {
        global $USER;
        $record->title = trim($fromform['title']);
        $record->description = trim($fromform['description']);
        $this->set_value($record, $fromform, 'context', 0);
        $this->set_value($record, $fromform, 'license', 'unknown');
        $this->set_value($record, $fromform, 'storedperson', '', 'persons');
        $this->set_value($record, $fromform, 'storedtags', '', 'tags');
        $this->set_value($record, $fromform, 'language', 'de');
        $this->set_value($record, $fromform, 'resourcetype', 0);
        $state = isset($fromform['upload']) && $fromform['upload'] == 1 ? 1 : 0;
        $state = isset($fromform['ignore']) && $fromform['ignore'] == 1 ? 2 : $state;
        $record->releasestate = $state;
        $record->preference = $record->preference ?? 1;
        $record->usermodified = $USER->id;
        $record->timemodified = $timestamp;
        $record->classification = self::prepare_classification_values_to_store($fromform);
        return $record;
    }

    /**
     * Set the field of a file record.
     * If the field is set in form - use the form value.
     * If the field is not set in form, use the already set value.
     * If no value is set, use default value.
     *
     * @param \stdClass $record
     * @param array $fromform
     * @param string $field
     * @param string $default
     * @param string|null $fieldrecord
     * @return void
     */
    private function set_value(\stdClass &$record, array $fromform, string $field, string $default, ?string $fieldrecord = null) {
        $fieldrecord = $fieldrecord ?? $field;
        if (isset($fromform[$field])) {
            $record->$fieldrecord = $fromform[$field];
        } else {
            $record->$fieldrecord = $record->$fieldrecord ?? $default;
        }
    }

    /**
     * The classification values are stored as json string.
     * Multiple subplugins can add to this field.
     *
     * @param array $fromform
     * @return false|string
     */
    public static function prepare_classification_values_to_store(array $fromform) {
        $classification = oerclassification::get_enabled_plugins();
        $result = [];
        foreach ($classification as $plugin => $fullname) {
            // @codeCoverageIgnoreStart
            // This code is not reachable without subplugins installed.
            $frankenstyle = 'oerclassification_' . $plugin;
            // The autocomplete field submits a string when no selection has been made. Make sure this string is not stored.
            // MDL-0 TODO: Is this intended behaviour or am i missing here something?
            if (isset($fromform[$frankenstyle]) && $fromform[$frankenstyle] != '_qf__force_multiselect_submission') {
                $result[$plugin] = $fromform[$frankenstyle];
            } else {
                unset($result[$plugin]);
            }
            // @codeCoverageIgnoreEnd
        }
        return empty($result) ? null : json_encode($result);
    }

    /**
     * Shared function to prepare classification form fields.
     *
     * @param \MoodleQuickForm $mform
     * @param \stdClass|null $classificationdata
     * @param array $data
     * @return void
     * @throws \coding_exception
     */
    public static function prepare_classification_values_for_form(\MoodleQuickForm $mform, ?\stdClass $classificationdata,
            array &$data): void {
        $classification = oerclassification::get_enabled_plugins();
        $reqfields = static::get_required_fields();
        foreach ($classification as $key => $pluginname) {
            // @codeCoverageIgnoreStart
            // This code is not reachable without subplugins installed.
            $frankenstyle = 'oerclassification_' . $key;
            // Load classification data and add them to form 0..n classification plugins possible.
            [$url, $selectdata] = self::load_classification_plugin_values($key);
            $options = [
                    'multiple' => true,
            ];
            $mform->addElement('html', '<hr>');
            $select = $mform->createElement('autocomplete', $frankenstyle,
                    get_string('selectname', $frankenstyle),
                    $selectdata, $options);
            $mform->addElement($select);
            $mform->addHelpButton($frankenstyle, 'selectname', $frankenstyle);
            if (in_array($frankenstyle, $reqfields)) {
                $mform->addRule($frankenstyle, get_string('required'), 'required', '', 'client');
            }
            if (!is_null($classificationdata) && isset($classificationdata->$key)) {
                $data[$frankenstyle] = $classificationdata->$key;
            }
            $mform->addElement('html', '<hr>');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Load selectdata and url from external classification plugin.
     *
     * This method is ignored for code coverage because it is not reachable whithout subplugins.
     *
     * @param string $name
     * @return array
     */
    public static function load_classification_plugin_values(string $name): array {
        // @codeCoverageIgnoreStart
        $frankenstyle = 'oerclassification_' . $name;
        $plugin = '\\' . $frankenstyle . '\plugin';
        $url = $plugin::url_to_external_resource();
        $selectdata = $plugin::get_select_data();
        return [$url, $selectdata];
        // @codeCoverageIgnoreEnd
    }

    /**
     * Set back the data of a file to the values defined in preferences. Null values are skipped.
     * Only values maintained by preferences are overwritten. Other values stay the same.
     * Except upload, upload is set to 0 to prevent conflicts.
     *
     * @param array $fromform
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function reset_form_data_to_preference_values(array &$fromform) {
        global $DB;
        $preference = $DB->get_record('local_oer_preference', ['courseid' => $fromform['courseid']]);
        if (!$preference) {
            return;
        }
        $fromform['upload'] = 0; // Upload state is reset, it has to be set new because of possible different required values.
        $fromform['context'] = $preference->context ?? $fromform['context'];
        $fromform['license'] = $preference->license ?? $fromform['license'];
        $fromform['storedperson'] = $preference->persons ?? $fromform['storedperson'];
        $fromform['storedtags'] = $preference->tags ?? $fromform['storedtags'];
        $fromform['language'] = $preference->language ?? $fromform['language'];
        $fromform['resourcetype'] = $preference->resourcetype ?? $fromform['resourcetype'];
        $fromform['ignore'] = $preference->state ?? $fromform['ignore'];
        if (!is_null($preference->classification)) {
            // @codeCoverageIgnoreStart
            // This code is not reachable without subplugins installed.
            $classification = json_decode($preference->classification);
            $classificationplugins = oerclassification::get_enabled_plugins();
            foreach ($classificationplugins as $plugin => $fullname) {
                $frankenstyle = 'oerclassification_' . $plugin;
                if (isset($fromform[$frankenstyle])) {
                    $fromform[$frankenstyle] = $classification->$plugin;
                }
            }
            // @codeCoverageIgnoreEnd
        }
    }
}
