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

namespace local_oer\helper;

use local_oer\classification;
use local_oer\forms\fileinfo_form;
use local_oer\logger;

/**
 * Class filestate
 *
 * Calculate the state of a file, to show additional information on the filecard
 * in view.
 * If the file is used in more than one course, it can also be locked in other courses
 * to prevent multiple edits with different metadata.
 */
class filestate {
    /**
     * File has not been edited for OER yet.
     * If it is used in more than one course, just show in every course that it is also
     * used in other courses.
     */
    const STATE_FILE_NOT_EDITED = 1;

    /**
     * File has been edited in one course. The metadata is locked in all other courses.
     * The course where the file is edited is marked.
     */
    const STATE_FILE_EDITED = 2;

    /**
     * File has been released. The metadata is locked in all courses.
     * The course where it has been released is marked.
     */
    const STATE_FILE_RELEASED = 3;

    /**
     * File may be in an ambiguous state. Maybe it has been edited in multiple courses.
     * In this state an administrator needs to examine what's wrong and clean up the mess.
     */
    const STATE_FILE_ERROR = 0;

    /**
     * Calculate the current file state for a file that has been found in a mod_resource or mod_folder activity.
     *
     * @param string $contenthash     Moodle file contenthash
     * @param int    $currentcourseid Course where this function is currently called.
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function calculate_file_state(string $contenthash, int $currentcourseid) {
        $courses = [];
        global $DB;
        // Step 1: Load usage of contenthash.
        $sql    = "SELECT DISTINCT(contextid) FROM {files} " .
                  "WHERE contenthash = :contenthash " .
                  "AND (component = 'mod_resource' OR component = 'mod_folder')";
        $usages = $DB->get_records_sql($sql, ['contenthash' => $contenthash]);
        if (!$usages) {
            // Woah, how did this happen?
            throw new \coding_exception('Something really unexpected happened, ' .
                                        'a file contenthash (' . $contenthash .
                                        ') has been searched that is not used anywhere');
        }

        // Step 2: Extract courseids from contexts.
        // As this are module contexts we need to find the parent course of it.
        foreach ($usages as $contextid => $usage) {
            list(, $course,) = get_context_info_array($contextid);
            $courses[$course->id] = [
                    'id'     => $course->id,
                    'name'   => format_string($course->fullname),
                    'editor' => false,
            ];
        }

        // Step 3: Determine OER file state. Is file being edited or already released?
        // There should only be one entry possible, but it is also tested if there.
        // Is ambiguous information in the table about this file.
        $state    = self::STATE_FILE_NOT_EDITED;
        $editorid = 0;
        $oerfiles = $DB->get_records('local_oer_files', ['contenthash' => $contenthash], 'id ASC', 'courseid');
        if ($oerfiles && count($oerfiles) > 1) {
            $message = 'Ambiguous metadata for file ' . $contenthash .
                       ' found. File has been edited in ' . count($oerfiles) . ' courses';
            logger::add(array_key_first($oerfiles), logger::LOGERROR, $message);
            return [self::STATE_FILE_ERROR, 0, []];
        } else if ($oerfiles && count($oerfiles) == 1) {
            $cid   = array_key_first($oerfiles);
            $state = self::STATE_FILE_EDITED;
            if (isset($courses[$cid])) {
                $editorid = $cid;
            } else {
                // Why is the course not set for this file? Probably it has been deleted in this context.
                // So it will be inherited to the first course in the list.
                $editorid = array_key_first($courses);
                $DB->set_field('local_oer_files', 'courseid', $editorid, ['contenthash' => $contenthash]);
            }
            $courses[$editorid]['editor'] = true;
        }

        // Snapshots will not be inherited to another course.
        if ($DB->record_exists('local_oer_snapshot', ['contenthash' => $contenthash])) {
            $state = self::STATE_FILE_RELEASED;
        }

        $writable = self::metadata_writable($state, $editorid == $currentcourseid);

        return [$state, $editorid, $courses, $writable];
    }

    /**
     * Resolve state and editor state of a file to a boolean value if the metadata is writable in a given course.
     *
     * @param int  $state  State of the file as calculated from this class.
     * @param bool $editor Value if the course is the course that has edited this file.
     * @return bool
     */
    public static function metadata_writable(int $state, bool $editor): bool {
        switch ($state) {
            case self::STATE_FILE_ERROR:
            case self::STATE_FILE_RELEASED:
                return false;
            case self::STATE_FILE_NOT_EDITED:
                return true;
            case self::STATE_FILE_EDITED:
                return $editor;
        }
        return false;
    }

    /**
     * Generate a placeholder text to show when a file is not writable.
     *
     * @param array $file
     * @return mixed
     * @throws \coding_exception
     */
    public static function formatted_notwritable_output_html(array $file) {
        global $OUTPUT, $DB;
        $support = \core_user::get_support_user();
        if ($file['state'] !== self::STATE_FILE_ERROR) {
            $data      = $DB->get_record('local_oer_files',
                                         ['courseid' => $file['editor'], 'contenthash' => $file['file']->get_contenthash()]);
            $context   = formhelper::lom_context_list();
            $resources = formhelper::lom_resource_types();
            // It ain`t much, but it`s honest work.
            $linebreak      = str_replace("\r\n", '<br>', $data->description);
            $firstbreak     = strpos($linebreak, '<br>');
            $firstline      = $firstbreak && $firstbreak < 80 ? $firstbreak : 80;
            $simplemetadata = [
                    [
                            'name'  => get_string('title', 'local_oer'),
                            'value' => $data->title
                    ],
                    [
                            'name'     => get_string('description', 'local_oer'),
                            'heading'  => substr($linebreak, 0, $firstline),
                            'body'     => substr($linebreak, $firstline, strlen($linebreak)),
                            'value'    => $data->description,
                            'collapse' => true,
                    ],
                    [
                            'name'  => get_string('context', 'local_oer'),
                            'value' => $context[$data->context]
                    ],
                    [
                            'name'  => get_string('license', 'local_oer'),
                            'value' => license::get_license_fullname($data->license)
                    ],
                    [
                            'name'  => get_string('language', 'local_oer'),
                            'value' => $data->language
                    ],
                    [
                            'name'  => get_string('resourcetype', 'local_oer'),
                            'value' => $resources[$data->resourcetype]
                    ],
            ];
            $tags           = explode(',', $data->tags);
            $taglist        = [];
            foreach ($tags as $tag) {
                $taglist[] = ['value' => $tag];
            }
            $persons    = empty($data->persons) ? [] : json_decode($data->persons, true)['persons'];
            $personlist = [];
            foreach ($persons as $person) {
                $personlist[] = $person;
            }
            $classification = json_decode($data->classification, true);
            $classlist      = [];
            foreach ($classification as $type => $entries) {
                $frankenstyle = 'oerclassification_' . $type;
                list($url, $classdata) = fileinfo_form::load_classification_plugin_values($type);
                $values = [];
                foreach ($entries as $entry) {
                    $values[] = [
                            'name' => $classdata[$entry],
                            'code' => $entry,
                    ];
                }
                $classlist[] = [
                        'type'   => get_string('selectname', $frankenstyle),
                        'url'    => $url,
                        'values' => $values,
                ];
            }
            $metadata = [
                    'simple'             => $simplemetadata,
                    'tags'               => !empty($taglist),
                    'taglist'            => $taglist,
                    'persons'            => !empty($personlist),
                    'personlist'         => $personlist,
                    'classification'     => !empty($classification),
                    'classificationlist' => $classlist,
            ];
        }

        switch ($file['state']) {
            case self::STATE_FILE_ERROR:
                $alert = 'danger';
                break;
            case self::STATE_FILE_RELEASED:
                $alert = 'success';
                break;
            default:
                $alert = 'info';
        }

        return $OUTPUT->render_from_template('local_oer/notwritable',
                                             [
                                                     'header'       => get_string('metadatanotwritable', 'local_oer'),
                                                     'alert'        => $alert,
                                                     'reason'       => get_string('metadatanotwritable' . $file['state'],
                                                                                  'local_oer'),
                                                     'support'      => get_string('contactsupport', 'local_oer',
                                                                                  ['support' => $support->email]),
                                                     'multiple'     => count($file['courses']) > 1,
                                                     'courses'      => array_values($file['courses']),
                                                     'showmetadata' => $file['state'] != self::STATE_FILE_ERROR,
                                                     'metadata'     => $metadata,
                                             ]);
    }
}
