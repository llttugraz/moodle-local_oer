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
 * @copyright  2017-2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Hook for link in coursenode
 *
 * @param navigation_node $parentnode
 * @param stdClass $course
 * @param context_course $context
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_oer_extend_navigation_course(navigation_node $parentnode, stdClass $course,
        context_course $context) {
    $capabilities = [
            'local/oer:viewitems',
    ];
    if ($course->id == 1 || !has_any_capability($capabilities, $context)) {
        // Ignore frontpage course.
        return;
    }
    $url = new moodle_url('/local/oer/views/main.php', ['id' => $course->id]);
    $node = navigation_node::create(
            get_string('oer_link', 'local_oer'),
            $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/upload', ''));
    $parentnode->add_node($node);
}

/**
 * Add node to flat navigation
 *
 * @param global_navigation $navigation
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_oer_extend_navigation(global_navigation $navigation) {
    global $PAGE;
    $context = context_course::instance($PAGE->course->id);
    if (has_capability('local/oer:viewitems', $context)) {
        $coursenode = $navigation->find($PAGE->course->id, navigation_node::TYPE_COURSE);
        $beforenode = $coursenode->find('localboostnavigationcoursesections', global_navigation::TYPE_UNKNOWN);
        $beforekey = $beforenode ? $beforenode->key : null;
        $coursenode->add_node(
                navigation_node::create(
                        get_string('oer_link', 'local_oer'),
                        new moodle_url("/local/oer/views/main.php", ["id" => $PAGE->course->id]),
                        navigation_node::TYPE_CUSTOM,
                        '',
                        'oer',
                        new pix_icon('i/upload', '')
                ),
                $beforekey);
    }
}

/**
 * Load and store forms used in frontend.
 *
 * TODO: as this function grows with cases, a refactor would be necessary.
 *      I think the form->render and save cases could be more generalized and
 *      separated into different methods/functions.
 *
 * @param array $args Arguments from JS call
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws required_capability_exception
 */
function local_oer_output_fragment_formdata(array $args): string {
    if (!isset($args['courseid'])
            || !isset($args['formtype'])) {
        return 'wrong arguments given';
    }

    $courseid = clean_param($args['courseid'], PARAM_INT);
    $formtype = clean_param($args['formtype'], PARAM_ALPHA);
    $context = context_course::instance($courseid);
    require_capability('local/oer:edititems', $context);
    $fromform = [];

    switch ($formtype) {
        case 'CourseinfoForm':
            $form = new \local_oer\forms\courseinfo_form(null, ['courseid' => $courseid]);
            return $form->render();
        case 'CourseinfoFormSave':
            if (!isset($args['params'])) {
                return 'form data missing.';
            }
            $mform = new \local_oer\forms\courseinfo_form(null, ['courseid' => $courseid]);
            $formdata = json_decode($args['params']);
            parse_str($formdata->settings, $fromform);
            $mform->set_data($fromform);
            $errors = $mform->validation($fromform, []);
            if (!empty($errors)) {
                $mform->is_validated();
                return $mform->render();
            }
            $mform->update_metadata($fromform);
            return '{"saved":"true"}';
        case 'FileinfoForm':
            if (!isset($args['params'])) {
                return 'additional parameters missing.';
            }
            $params = json_decode($args['params']);
            $identifier = clean_param($params->identifier, PARAM_TEXT);
            \local_oer\identifier::strict_validate($identifier);
            $form = new \local_oer\forms\fileinfo_form(null, ['courseid' => $courseid, 'identifier' => $identifier]);
            return $form->render();
        case 'FileinfoFormSave':
            if (!isset($args['params'])) {
                return 'form data missing.';
            }
            $params = json_decode($args['params']);
            parse_str($params->settings, $fromform);
            $identifier = clean_param($params->identifier, PARAM_TEXT);
            \local_oer\identifier::strict_validate($identifier);
            $mform = new \local_oer\forms\fileinfo_form(null, ['courseid' => $courseid, 'identifier' => $identifier]);
            if (isset($params->preference) && $params->preference == 'reset') {
                \local_oer\forms\fileinfo_form::reset_form_data_to_preference_values($fromform);
            }
            $mform->set_data($fromform);
            $errors = $mform->validation($fromform, []);
            if (!empty($errors) || (isset($params->preference) && $params->preference == 'reset')) {
                $mform->is_validated();
                return $mform->render();
            }
            $mform->update_metadata($fromform);
            return '{"saved":"true","timemodified":"' . userdate(time()) . '"}';
        case 'PreferenceForm':
            $form = new \local_oer\forms\preference_form(null, ['courseid' => $courseid]);
            return $form->render();
        case 'PreferenceFormSave':
            if (!isset($args['params'])) {
                return 'form data missing.';
            }
            $params = json_decode($args['params']);
            $mform = new \local_oer\forms\preference_form(null, ['courseid' => $courseid]);
            parse_str($params->settings, $fromform);
            $mform->set_data($fromform);
            $errors = $mform->validation($fromform, []);
            if (!empty($errors)) {
                $mform->is_validated();
                return $mform->render();
            }
            $mform->update_metadata($fromform);
            return '{"saved":"true"}';
        case 'CourseToFileForm':
            $params = json_decode($args['params']);
            $identifier = clean_param($params->identifier, PARAM_TEXT);
            \local_oer\identifier::strict_validate($identifier);
            $form = new \local_oer\forms\coursetofile_form(null, ['identifier' => $identifier, 'courseid' => $courseid]);
            return $form->render();
        case 'CourseToFileFormSave':
            if (!isset($args['params'])) {
                return 'form data missing.';
            }
            $params = json_decode($args['params']);
            $identifier = clean_param($params->identifier, PARAM_TEXT);
            \local_oer\identifier::strict_validate($identifier);
            $mform = new \local_oer\forms\coursetofile_form(null, [
                    'identifier' => $identifier,
                    'courseid' => $courseid,
            ]);
            parse_str($params->settings, $fromform);
            $mform->set_data($fromform);
            $errors = $mform->validation($fromform, []);
            if (!empty($errors)) {
                $mform->is_validated();
                return $mform->render();
            }
            $mform->store_overwrite_data($fromform);
            return '{"saved":"true"}';
        default:
            return 'Unknown form type submitted.';
    }
}

/**
 * Return the person form for the frontend to render.
 *
 * @param array $args
 * @return string
 */
function local_oer_output_fragment_personform(array $args): string {
    $form = new \local_oer\forms\person_form(null, $args);
    return $form->render();
}

/**
 * Serve public available oer files
 *
 * @param stdClass|null $course the course object
 * @param stdClass|null $cm the course module object
 * @param stdClass|null $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return void just send the file and do not return anything
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_oer_pluginfile(?\stdClass $course, ?\stdClass $cm, ?\stdClass $context,
        string $filearea, array $args, bool $forcedownload, array $options = []): void {
    if (get_config('local_oer', 'pullservice') != 1) {
        throw new \moodle_exception('Webservice to show public accessible OER Files is not activated on this system.');
    }
    if ($filearea != 'public') {
        throw new \moodle_exception('File not found.');
    }
    $fileid = clean_param($args[0], PARAM_INT);

    global $DB;
    if ($DB->record_exists('local_oer_snapshot', ['id' => $fileid])) {
        $fileinfo = $DB->get_record('local_oer_snapshot', ['id' => $fileid]);
        $element = \local_oer\filelist::get_single_file($fileinfo->courseid, $fileinfo->identifier);
        if ($element && !empty($element->get_storedfiles())) {
            $files = $element->get_storedfiles();
            send_stored_file(reset($files));
        }
    }
    throw new \moodle_exception('File not found.');
}

/**
 * Callback to handle entries for deleted users.
 *
 * @param stdClass $user
 * @throws dml_exception
 */
function local_oer_pre_user_delete(stdClass $user) {
    global $DB;
    $DB->delete_records('local_oer_userlist', ['userid' => $user->id]);

    $admindata = get_admin();
    $adminid = $admindata->id;
    $tablenames = \local_oer\privacy\provider::get_tablenames_except_userlist();
    foreach ($tablenames as $tablename) {
        $records = $DB->get_records($tablename, ['usermodified' => $user->id]);
        foreach ($records as $record) {
            if ($record->usermodified != $adminid) {
                $record->usermodified = $adminid;
                $DB->update_record($tablename, $record);
            }
        }
    }
}


