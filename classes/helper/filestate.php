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

use local_oer\forms\fileinfo_form;
use local_oer\identifier;
use local_oer\logger;
use local_oer\modules\element;

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
     * Calculate the state of the element.
     *
     * This will affect which actions a user can take to manipulate the metadata.
     * Other courses are also considered in calculation.
     * See states above to learn more about the different possibilities.
     *
     * @param element $element
     * @param int $currentcourseid
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function calculate_state(element $element, int $currentcourseid): void {
        $courses = [];
        switch ($element->get_type()) {
            case element::OERTYPE_MOODLEFILE:
                // Step 1: Load usage of contenthash.
                // Step 2: Extract courseids from contexts.
                $decomposed = identifier::decompose($element->get_identifier());
                $courses = self::find_courses_that_use_this_element($decomposed->value);
                // Step 3: Determine OER element state. Is element being edited or already released?
                [$state, $courses, $editorid, $writable] = self::determine_element_state($element, $currentcourseid, $courses);
                break;
            case element::OERTYPE_EXTERNAL:
                // MDL-0 TODO: is there a performant way to find all courses for an external object?
                // Maybe this should be implemented in module subplugin as every subplugin has an other way for this information?
                global $DB;
                $course = get_course($currentcourseid);
                $courses[$currentcourseid] = [
                        'id' => $currentcourseid,
                        'name' => $course->fullname,
                        'editor' => false,
                ];
                if ($edited = $DB->get_record('local_oer_elements', ['identifier' => $element->get_identifier()])) {
                    $course = get_course($edited->courseid);
                    $courses[$edited->courseid] = [
                            'id' => $edited->courseid,
                            'name' => $course->fullname,
                            'editor' => true,
                    ];
                }
                [$state, $courses, $editorid, $writable] = self::determine_element_state($element, $currentcourseid, $courses);
                break;
            default:
                throw new \coding_exception('Unknown element type: ' . $element->get_type());
        }
        $elementstate = new \stdClass();
        $elementstate->state = $state;
        $elementstate->editorid = $editorid;
        $elementstate->courses = $courses;
        $elementstate->writable = $writable;
        $element->set_elementstate($elementstate);
    }

    /**
     * Search for the usages of a given contenthash. This function only works for Moodle based subplugins.
     * Due to historical reasons at the moment only mod_resource and mod_folder are supported.
     * TODO: If anytime in the future a subplugin will be created that loads files from other activities this has to be extended.
     *
     * @param string $contenthash Moodle file contenthash
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function find_courses_that_use_this_element(string $contenthash): array {
        global $DB;
        $courses = [];
        $sql = "SELECT DISTINCT contextid FROM {files} " .
                "WHERE contenthash = :contenthash " .
                "AND (component = 'mod_resource' OR component = 'mod_folder')";
        $usages = $DB->get_records_sql($sql, ['contenthash' => $contenthash]);
        if (!$usages) {
            // Woah, how did this happen?
            throw new \coding_exception('Something really unexpected happened, ' .
                    'a file contenthash (' . $contenthash .
                    ') has been searched that is not used anywhere');
        }

        // As this are module contexts we need to find the parent course of it.
        foreach ($usages as $contextid => $usage) {
            [, $course] = get_context_info_array($contextid);
            $courses[$course->id] = [
                    'id' => $course->id,
                    'name' => format_string($course->fullname),
                    'editor' => false,
            ];
        }
        return $courses;
    }

    /**
     * Returns the state for a given element.
     *
     * State consists of:
     * - Has element already been edited or released?
     * - In which courses is this element in use (for external elements this will only work if another course is editing)
     * - Which course is the editor course
     * - Is the element writable in this course
     *
     * @param element $element
     * @param int $currentcourseid
     * @param array $courses
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function determine_element_state(element $element, int $currentcourseid, array $courses = []): array {
        global $DB;
        $state = self::STATE_FILE_NOT_EDITED;
        $editorid = 0;
        $oerelement = $DB->get_record('local_oer_elements', ['identifier' => $element->get_identifier()], 'courseid');
        if ($oerelement) {
            $state = self::STATE_FILE_EDITED;
            if (isset($courses[$oerelement->courseid])) {
                $editorid = $oerelement->courseid;
            } else {
                // Why is the course not set for this file? Probably it has been deleted in this context.
                // So it will be inherited to the first course in the list.
                $editorid = array_key_first($courses);
                $DB->set_field('local_oer_elements', 'courseid', $editorid, ['identifier' => $element->get_identifier()]);
                logger::add($editorid, logger::LOGERROR,
                        'Inherited file ' . $element->get_identifier() . ' from course ' . $oerelement->courseid);
            }
            $courses[$editorid]['editor'] = true;
        }

        // Snapshots will not be inherited to another course.
        if ($DB->record_exists('local_oer_snapshot', ['identifier' => $element->get_identifier()])) {
            $state = self::STATE_FILE_RELEASED;
        }

        $writable = self::metadata_writable($state, $editorid == $currentcourseid);

        return [$state, $courses, $editorid, $writable];
    }

    /**
     * Resolve state and editor state of a file to a boolean value if the metadata is writable in a given course.
     *
     * @param int $state State of the file as calculated from this class.
     * @param bool $editor Value if the course is the course that has edited this file.
     * @return bool
     */
    public static function metadata_writable(int $state, bool $editor): bool {
        switch ($state) {
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
     * @param element $element
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function formatted_notwritable_output_html(element $element): string {
        global $OUTPUT, $DB, $CFG;
        $support = \core_user::get_support_user();
        $data = $DB->get_record('local_oer_elements', ['identifier' => $element->get_identifier()]);
        $context = formhelper::lom_context_list();
        $resources = formhelper::lom_resource_types();
        // It ain`t much, but it`s honest work.
        $linebreak = str_replace("\r\n", '<br>', $data->description);
        $firstbreak = strpos($linebreak, '<br>');
        $firstline = $firstbreak && $firstbreak < 80 ? $firstbreak : 80;
        $heading = substr($linebreak, 0, $firstline);
        $body = substr($linebreak, $firstline, strlen($linebreak));
        $simplemetadata = [
                [
                        'name' => get_string('title', 'local_oer'),
                        'value' => $data->title,
                ],
                [
                        'name' => get_string('description', 'local_oer'),
                        'heading' => $heading,
                        'body' => $body,
                        'value' => $data->description,
                        'emptybody' => empty($body),
                        'collapse' => true, // Used for different state in mustache than other values here.
                ],
                [
                        'name' => get_string('context', 'local_oer'),
                        'value' => $context[$data->context],
                ],
                [
                        'name' => get_string('license'),
                        'value' => license::get_license_fullname($data->license),
                ],
                [
                        'name' => get_string('language', 'local_oer'),
                        'value' => $data->language,
                ],
                [
                        'name' => get_string('resourcetype', 'local_oer'),
                        'value' => $resources[$data->resourcetype],
                ],
        ];
        $tags = empty($data->tags) ? [] : explode(',', $data->tags);
        $taglist = [];
        foreach ($tags as $tag) {
            $taglist[] = ['value' => $tag];
        }
        $persons = empty($data->persons) ? [] : json_decode($data->persons, true)['persons'];
        $personlist = [];
        foreach ($persons as $person) {
            $personlist[] = $person;
        }
        $classification = empty($data->classification) ? [] : json_decode($data->classification, true);
        $classlist = [];
        foreach ($classification as $type => $entries) {
            $frankenstyle = 'oerclassification_' . $type;
            [$url, $classdata] = fileinfo_form::load_classification_plugin_values($type);
            $values = [];
            foreach ($entries as $entry) {
                $values[] = [
                        'name' => $classdata[$entry],
                        'code' => $entry,
                ];
            }
            $classlist[] = [
                    'type' => get_string('selectname', $frankenstyle),
                    'url' => $url,
                    'values' => $values,
            ];
        }
        $metadata = [
                'simple' => $simplemetadata,
                'tags' => !empty($taglist),
                'taglist' => $taglist,
                'persons' => !empty($personlist),
                'personlist' => $personlist,
                'classification' => !empty($classification),
                'classificationlist' => $classlist,
        ];

        $state = $element->get_elementstate()->state;
        switch ($state) {
            case self::STATE_FILE_RELEASED:
                $alert = 'success';
                break;
            default:
                $alert = 'info';
        }

        return $OUTPUT->render_from_template('local_oer/notwritable',
                [
                        'header' => get_string('metadatanotwritable', 'local_oer'),
                        'alert' => $alert,
                        'reason' => get_string('metadatanotwritable' . $state,
                                'local_oer'),
                        'support' => get_string('contactsupport', 'local_oer',
                                ['support' => $support->email]),
                        'multiple' => count($element->get_elementstate()->courses) > 1,
                        'courses' => array_values($element->get_elementstate()->courses),
                        'showmetadata' => true, // MDL-0 TODO: this flag can be removed as STATE_FILE_ERROR does not exist anymore.
                        'metadata' => $metadata,
                        'wwwroot' => $CFG->wwwroot,
                ]);
    }
}
