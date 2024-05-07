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
use local_oer\metadata\coursecustomfield;
use local_oer\metadata\courseinfo;
use local_oer\metadata\courseinfo_sync;

/**
 * Formular to define all necessary metadata fields.
 */
class courseinfo_form extends \moodleform {
    /**
     * Mform definition function, required by moodleform.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;
        global $DB, $OUTPUT;

        $info = new courseinfo();
        $courses = $info->load_metadata_from_database($customdata['courseid']);
        $data = [];
        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);
        $data['courseid'] = $customdata['courseid'];
        $moodleonly = get_config('local_oer', 'metadataaggregator') == 'no_value';

        $text = $OUTPUT->render_from_template('local_oer/forminfo', [
                'text' => get_string('courseinfoformhelp', 'local_oer'),
        ]);
        $mform->addElement('html', $text);
        if (!$moodleonly) {
            $text = $OUTPUT->render_from_template('local_oer/forminfo', [
                    'text' => get_string('courseinfoformexternhelp', 'local_oer'),
            ]);
            $mform->addElement('html', $text);
        }
        $text = $OUTPUT->render_from_template('local_oer/forminfo', [
                'text' => get_string('courseinfoformadditionalhelp', 'local_oer'),
        ]);
        $mform->addElement('html', $text);

        foreach ($courses as $course) {
            $additionalinfo = '';
            if ($course->deleted == 1) {
                $DB->set_field('local_oer_courseinfo', 'ignored', 1,
                        ['courseid' => $customdata['courseid'], 'coursecode' => $course->coursecode]);
                $course->ignored = 1;
                $additionalinfo = ' (' . get_string('deleted', 'local_oer') . ')';
            } else if ($course->ignored == 1) {
                $additionalinfo = ' (' . get_string('ignorecourse', 'local_oer') . ')';
            }

            $mform->addElement('header', 'coursecode_' . $course->coursecode);
            $mform->setExpanded('coursecode_' . $course->coursecode, false);
            $data['coursecode_' . $course->coursecode] = get_string('course')
                    . ': ' . $course->coursecode . $additionalinfo;

            if ($course->deleted == 1) {
                $mform->addElement('HTML', get_string('deleted_help', 'local_oer'));
            }

            $group = [];
            $checkbox = 'ignored_' . $course->coursecode;
            $groupname = 'ignoredgroup_' . $course->coursecode;
            if ($course->deleted != 1) {
                $group[] =& $mform->createElement('advcheckbox', $checkbox, '');
            }
            $group[] =& $mform->createElement('html', get_string('ignoredcourse', 'local_oer'));
            $mform->addGroup($group, $groupname, get_string('ignorecourse', 'local_oer'), ' ', false);
            $mform->addHelpButton($groupname, 'ignorecourse', 'local_oer');
            $data[$checkbox] = $course->ignored;

            $textareaformat = 'wrap="virtual" rows="3" cols="45"';
            $mform = $this->form_default_element($mform, $course, $data, 'coursename', 'text', 255, 0);
            $mform = $this->form_default_element($mform, $course, $data, 'structure', 'text', 255);
            $mform = $this->form_default_element($mform, $course, $data, 'description', 'textarea', 0, 0, false,
                    $textareaformat);
            $mform = $this->form_default_element($mform, $course, $data, 'objectives', 'textarea', 0, 0, false,
                    $textareaformat);
            $mform = $this->form_default_element($mform, $course, $data, 'organisation', 'text', 255);
            $languages = formhelper::language_select_data();
            $mform = $this->form_default_element($mform, $course, $data, 'language', 'select', 2, 0, false, $languages);
            $mform = $this->form_default_element($mform, $course, $data, 'lecturer', 'textarea', 0, 0, false,
                    $textareaformat);

            if ($course->subplugin == courseinfo::BASETYPE && get_config('local_oer', 'coursecustomfields')) {
                $customfields = coursecustomfield::get_course_customfields_with_applied_config($course->courseid, true);
                foreach ($customfields as $category) {
                    $categoryname = str_replace(' ', '', strtolower($category['name'])) . $category['id'] . '_' .
                            $course->coursecode;
                    $cattext = empty($category['fields']) ? get_string('nofieldsincat', 'local_oer') : '';
                    $mform->addElement('html', '<hr>');
                    $mform->addElement('static', $categoryname, '<strong>' . $category['name'] . '</strong>', $cattext);
                    $mform->addHelpButton($categoryname, 'customfieldcategory', 'local_oer');
                    foreach ($category['fields'] as $field) {
                        $shortname = $field['shortname'];
                        switch ($field['type']) {
                            case 'date':
                                $mform->addElement('static', $categoryname . $shortname, $field['fullname'],
                                        userdate($field['data']));
                                break;
                            case 'select':
                                $value = $field['data'];
                                $mform->addElement('static', $categoryname . $shortname, $field['fullname'], $value);
                                break;
                            case 'checkbox':
                                $value = $field['data'] ? get_string('active') : get_string('inactive');
                                $mform->addElement('static', $categoryname . $shortname, $field['fullname'], $value);
                                break;
                            default:
                                $mform->addElement('static', $categoryname . $shortname, $field['fullname'], $field['data']);
                        }
                    }
                }
            }
        }

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
        $collectedignored = [];
        foreach ($data as $key => $value) {
            $group = $key . 'group';
            $identifier = $this->parse_identifier($key);
            switch ($identifier) {
                case 'coursename':
                    if (empty($value)) {
                        $errors[$group] = get_string('required');
                    } else if (strlen($value) > 255) {
                        $errors[$group] = get_string('maximumchars', '', 255);
                    }
                    break;
                case 'structure':
                case 'organisation':
                case 'lecturer':
                    if (strlen($value) > 255) {
                        $errors[$group] = get_string('maximumchars', '', 255);
                    }
                    break;
                case 'language':
                    if (strlen($value) > 2) {
                        $errors[$group] = get_string('maximumchars', '', 2);
                    }
                    break;
                case 'ignored':
                    $collectedignored[$key] = $value;
                    break;
            }
        }
        $onecourseremains = false;
        foreach ($collectedignored as $key => $value) {
            if ($value == 0) {
                $onecourseremains = true;
            }
        }
        if (!$onecourseremains) {
            foreach ($collectedignored as $key => $value) {
                $newkey = str_replace('ignored', 'ignoredgroup', $key);
                $errors[$newkey] = get_string('onecourseinfoneeded', 'local_oer');
            }
        }

        return $errors;
    }

    /**
     * Most of the elements work the same way in this formular.
     * So this is a helper method to set the elements.
     * A checkbox to enable/disable the field.
     * The field itself.
     *
     * @param \MoodleQuickForm $mform
     * @param \stdClass $course
     * @param array $data The data to set for the form elements
     * @param string $identifier Name of form element
     * @param string $type Type of form element
     * @param int $maxlength Maximum input length for text fields
     * @param int $minlength Minimum input length for text fields
     * @param bool $required Is this form field required?
     * @param mixed $additionaldata Datatype depends on used form field.
     * @param string $shownname For dynamic fields language strings cannot be prepared
     * @param string $helpstring Same as the name for dynamic fields, provide a help string
     * @param bool $ignoreable Can this field be ignored?
     * @return \MoodleQuickForm
     * @throws \coding_exception
     */
    private function form_default_element(\MoodleQuickForm $mform, \stdClass $course, array &$data,
            string $identifier, string $type, int $maxlength = 0,
            int $minlength = 0, bool $required = false,
            $additionaldata = false, string $shownname = '',
            string $helpstring = '', $ignoreable = false): \MoodleQuickForm {
        $name = $identifier . '_' . $course->coursecode;
        $shownname = $shownname == '' ? get_string($identifier, 'local_oer') : $shownname;
        $checkbox = $identifier . '_edited_' . $course->coursecode;
        $ignorebox = $identifier . '_ignore_' . $course->coursecode;
        $group = $name . 'group';
        $helpstring = $helpstring == '' ? $identifier : $helpstring;
        $availablefromgroup = [];
        $availablefromgroup[] =& $mform->createElement($type, $name, $shownname, $additionaldata);
        $availablefromgroup[] =& $mform->createElement('checkbox', $checkbox, '', get_string('overwrite', 'local_oer'));
        $mform->addGroup($availablefromgroup, $name . 'group', $shownname, ' ', false);
        $mform->setType($name, PARAM_RAW); // MDL-0 TODO: how to define a more precise type for the fields?
        $mform->disabledIf($group, $checkbox);
        $mform->hideif($group, 'ignored_' . $course->coursecode, 'checked');
        if ($ignoreable) {
            $mform->addElement('checkbox', $ignorebox, '', get_string('ignore', 'local_oer'));
            $mform->disabledIf($group, $ignorebox, 'checked');
            $mform->hideif($ignorebox, 'ignored_' . $course->coursecode, 'checked');
            $mform->addElement('html', '<hr>');
        }
        if ($required) {
            $mform->addRule($group, get_string('required'), 'required', '', 'client');
        }
        if ($minlength > 0) {
            $mform->addRule($group, get_string('minimumchars', 'local_oer', $minlength), 'minlength', $minlength, 'client');
        }
        if ($maxlength > 0) {
            $mform->addRule($group, get_string('maximumchars', '', $maxlength), 'maxlength', $maxlength, 'client');
        }
        $mform->addHelpButton($group, $helpstring, 'local_oer');
        $data[$name] = $course->$identifier;
        $checkboxidentifier = $identifier . '_edited';
        $data[$checkbox] = $course->$checkboxidentifier;
        return $mform;
    }

    /**
     * Update the formdata in the database.
     *
     * @param array $fromform
     * @return void
     * @throws \dml_exception
     */
    public function update_metadata(array $fromform) {
        global $DB, $USER;
        $info = new courseinfo();
        $courses = $info->load_metadata_from_database($fromform['courseid']);
        $updated = false;
        foreach ($courses as $course) {
            $update = false;
            if ($course->subplugin == courseinfo::BASETYPE) {
                $update = coursecustomfield::compare_difference($course->courseid);
            }
            $update = $this->overwrite_disabled($course, 'coursename', $fromform, $update);
            $update = $this->overwrite_disabled($course, 'structure', $fromform, $update);
            $update = $this->overwrite_disabled($course, 'description', $fromform, $update);
            $update = $this->overwrite_disabled($course, 'objectives', $fromform, $update);
            $update = $this->overwrite_disabled($course, 'organisation', $fromform, $update);
            $update = $this->overwrite_disabled($course, 'language', $fromform, $update);
            $update = $this->overwrite_disabled($course, 'lecturer', $fromform, $update);
            foreach ($fromform as $key => $value) {
                if (preg_match('/' . $course->coursecode . '$/', $key)) {
                    $identifier = $this->parse_identifier($key);
                    switch ($identifier) {
                        case 'ignored':
                            if ($course->ignored != $value) {
                                $course->ignored = $value;
                                $update = true;
                            }
                        case 'coursename_edited':
                        case 'structure_edited':
                        case 'description_edited':
                        case 'objectives_edited':
                        case 'organisation_edited':
                        case 'language_edited':
                        case 'lecturer_edited':
                            if ($course->$identifier != $value) {
                                $update = true;
                                $course->$identifier = $value;
                            }
                            $name = str_replace('_edited', '', $identifier);
                            if ($value == 1 && $course->$name
                                    != $fromform[$name . '_' . $course->coursecode]) {
                                $course->$name = $fromform[$name . '_' . $course->coursecode];
                                $update = true;
                            }
                    }
                }
            }
            if ($update) {
                $updated = true;
                $course->usermodified = $USER->id;
                $course->timemodified = time();
                $DB->update_record('local_oer_courseinfo', $course);
            }
        }
        // If at least one course has been updated, initiate a synchronisation for the metadata.
        // This will reset fields that have been set from edited to non-edited.
        if ($updated) {
            $sync = new courseinfo_sync();
            $sync->sync_course($fromform['courseid']);
        }
    }

    /**
     * When a group overwrite checkbox is disabled. The group element will not be submitted from
     * frontend to backend. This function determines if the edited value has to be disabled.
     *
     * @param \stdClass $course
     * @param string $value
     * @param array $fromfrom
     * @param bool $update
     * @return bool
     */
    private function overwrite_disabled(\stdClass &$course, string $value, array $fromfrom, bool $update): bool {
        $field = $value . '_' . $course->coursecode;
        $edited = $value . '_edited';
        if (!isset($fromform[$field]) && $course->$edited == 1) {
            $course->$edited = 0;
            return true;
        }
        return $update;
    }

    /**
     * Get the identifier of a form field.
     * Every element has a name and an additional checkbox with name_edited.
     *
     * @param string $key
     * @return mixed|string
     */
    private function parse_identifier(string $key) {
        $parts = explode('_', $key);
        if (count($parts) > 1 && $parts[1] == 'edited') {
            return $parts[0] . '_' . $parts[1];
        } else {
            return $parts[0];
        }
    }
}
