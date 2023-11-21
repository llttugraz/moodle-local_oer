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
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\forms;

use local_oer\filelist;
use local_oer\identifier;
use local_oer\metadata\coursetofile;

/**
 * Class coursetofile_form
 *
 * Formular to load course metadata that can be attached to file metadata.
 */
class coursetofile_form extends \moodleform {
    /**
     * A string to explode the id of checkbox fields.
     * Looks a bit strange to prevent collision with courscode formats.
     */
    const COURSEINFO_SEPARATOR = '_-_-';

    /**
     * Mform definition function, required by moodleform.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        $file = $this->_customdata;
        $element = filelist::get_single_file($file['courseid'], $file['identifier']);
        $courses = coursetofile::get_courses_metadata_for_file($element, $file['courseid']);

        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'contenthash', null);
        $mform->setType('contenthash', PARAM_ALPHANUM);
        $data = [
                'courseid' => $file['courseid'],
                'identifier' => $file['identifier'],
        ];

        $mform->addElement('html', '<div class="alert alert-info">' . get_string('coursetofile_info', 'local_oer') . '</div>');

        if (count($courses) > 1) {
            $mform->addElement('html',
                    '<div class="alert alert-info">' . get_string('multiplecoursestofile', 'local_oer') . '</div>');
        }

        foreach ($courses as $course) {
            $moodleurl = new \moodle_url('/course/view.php', ['id' => $course['id']]);
            $url = '<a target="_blank" href="' . $moodleurl->out() . '">' . get_string('tocourse', 'local_oer') . '</a>';
            $editor = $course['editor'] ? '<span class="badge badge-info">Editor</span>' : '';
            $header = '<h4 class="bg-light">' . $editor . $course['name'] . ' (' . $url . ')</h4>';
            $mform->addElement('html', $header);
            if (empty($course['courseinfo'])) {
                $mform->addElement('html',
                        '<div class="alert alert-warning">' .
                        get_string('nocourseinfo', 'local_oer') .
                        '</div>');
            }
            foreach ($course['courseinfo'] as $courseinfo) {
                $identifier = $courseinfo['parent'] . self::COURSEINFO_SEPARATOR .
                        $courseinfo['coursecode'];
                $data[$identifier] = $courseinfo['state'];
                $courseinfo['metadata']['description'] = str_replace("\r\n", '<br>',
                        $courseinfo['metadata']['description']);
                $courseinfo['metadata']['objectives'] = str_replace("\r\n", '<br>',
                        $courseinfo['metadata']['objectives']);
                $nodata = '-';
                $infodata = [
                        'structure' => empty($courseinfo['metadata']['structure']) ? $nodata :
                                $courseinfo['metadata']['structure'],
                        'description' => empty($courseinfo['metadata']['description']) ? $nodata :
                                $courseinfo['metadata']['description'],
                        'objectives' => empty($courseinfo['metadata']['objectives']) ? $nodata :
                                $courseinfo['metadata']['objectives'],
                        'organisation' => empty($courseinfo['metadata']['organisation']) ? $nodata :
                                $courseinfo['metadata']['organisation'],
                        'language' => empty($courseinfo['metadata']['language']) ? $nodata :
                                $courseinfo['metadata']['language'],
                        'lecturer' => empty($courseinfo['metadata']['lecturer']) ? $nodata :
                                $courseinfo['metadata']['lecturer'],
                ];

                $collapse = $OUTPUT->render_from_template('local_oer/courseinfo_collapse', $infodata);
                $mform->addElement('advcheckbox', $identifier,
                        $courseinfo['metadata']['coursename'], $collapse,
                        ['group' => $courseinfo['parent']],
                        [0, 1]);
            }
            $mform->addElement('html', '<hr>');
        }

        $mform->disable_form_change_checker();
        $this->set_data($data);
    }

    /**
     * Moodle mform validation method.
     *
     * At least one of the editor course choices has to be selected.
     *
     * @param array $data
     * @param array $files
     * @return array
     * @throws \coding_exception
     */
    public function validation($data, $files) {
        $file = $this->_customdata;
        $error = [];
        $found = false;
        foreach ($data as $key => $value) {
            $course = explode(self::COURSEINFO_SEPARATOR, $key);
            if ($course[0] == $file['courseid']) {
                $error[$key] = get_string('oneeditorselectederror', 'local_oer');
                if ($value == "1") {
                    $found = true;
                }
            }
        }
        return $found ? [] : $error;
    }

    /**
     * Store the form data to the local_oer_coursetofile table.
     * Data is only stored if it differs from default value.
     *
     * For other courses the default is disabled. So only  enabled values are stored.
     * For the course where the file is edited also the case for disabling course metadata is possible.
     *
     * @param array $data
     * @return void
     * @throws \dml_exception
     */
    public function store_overwrite_data(array $data) {
        global $DB, $USER;
        $table = 'local_oer_coursetofile';
        $file = $this->_customdata;
        $decomposed = identifier::decompose($file['identifier']);
        foreach ($data as $key => $state) {
            if (strpos($key, self::COURSEINFO_SEPARATOR) === false) {
                continue;
            }
            $course = explode(self::COURSEINFO_SEPARATOR, $key);
            if ($DB->record_exists('local_oer_courseinfo', ['courseid' => $course[0], 'coursecode' => $course[1]])) {
                $params = [
                        'contenthash' => $decomposed->value,
                        'courseid' => $course[0],
                        'coursecode' => $course[1],
                ];
                $ignore = $DB->get_field('local_oer_courseinfo', 'ignored',
                        [
                                'courseid' => $course[0],
                                'coursecode' => $course[1],
                        ]);
                if (($file['courseid'] == $course[0] && $ignore != $state) ||
                        ($file['courseid'] != $course[0] && $state == coursetofile::COURSETOFILE_DISABLED)) {
                    // For editor course only store different values, and for others.
                    // Only store enabled values.
                    $DB->delete_records($table, $params);
                } else if ($owexist = $DB->get_record($table, $params)) {
                    $owexist->state = $state;
                    $owexist->usermodified = $USER->id;
                    $owexist->timemodified = time();
                    $DB->update_record($table, $owexist);
                } else {
                    $ow = new \stdClass();
                    $ow->contenthash = $decomposed->value;
                    $ow->courseid = $course[0];
                    $ow->coursecode = $course[1];
                    $ow->state = $state;
                    $ow->usermodified = $USER->id;
                    $ow->timecreated = time();
                    $ow->timemodified = time();
                    $DB->insert_record($table, $ow);
                }
            }
        }
    }
}
