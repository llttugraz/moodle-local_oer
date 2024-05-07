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
 * @copyright  2017-2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

use local_oer\helper\filestate;
use local_oer\modules\element;
use local_oer\modules\elements;
use local_oer\plugininfo\oermod;

/**
 * Class filelist
 *
 * Get a list of all elements of a course.
 * TODO: As the plugin has been extended the name filelist is not accurate anymore.
 */
class filelist {
    /**
     * Load all files from moodle course modules and return all files and modules.
     *
     * @param int $courseid Moodle courseid
     * @return elements
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_course_files(int $courseid): elements {
        global $DB;
        // Step 1: gather all elements from subplugins.
        $plugins = oermod::get_enabled_plugins();
        $elements = new elements();
        foreach (array_keys($plugins) as $pluginname) {
            $elements->merge_elements(oermod::load_elements($pluginname, $courseid));
        }

        // Step 2: add stored metadata based on editor course and state for usage in multiple courses.
        // The information shown is the same in all courses where the file is used.
        $records = $DB->get_records('local_oer_elements');
        $storedelements = [];
        foreach ($records as $record) {
            $storedelements[$record->identifier] = $record;
        }

        $visited = [];
        foreach ($elements as $key => $element) {
            if (isset($visited[$element->get_identifier()])) {
                // This element is multiple times in this course, only show it once.
                $primary = $elements->get_element_by_key($visited[$element->get_identifier()]);
                foreach ($element->get_origin() as $origin => $languagestring) {
                    $primary->set_origin($origin, $languagestring[0], $languagestring[1]);
                }
                $primary->merge_information($element->get_information());
                if ($primary->get_type() == element::OERTYPE_MOODLEFILE && !empty($element->get_storedfiles())) {
                    $primary->set_storedfile($element->get_storedfiles()[0]);
                }
                $elements->remove_element($elements->key());
                continue;
            }
            filestate::calculate_state($element, $courseid);
            if (isset($storedelements[$element->get_identifier()])) {
                $element->set_stored_metadata($storedelements[$element->get_identifier()]);
            }
            $visited[$element->get_identifier()] = $key;
        }

        return $elements;
    }

    /**
     * Load a single file and the module it is added.
     *
     * @param int $courseid Moodle courseid
     * @param string $identifier Element identifier
     * @return element|null
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_single_file(int $courseid, string $identifier): ?element {
        $elements = self::get_course_files($courseid);
        return $elements->find_element('identifier', $identifier) ?? null;
    }

    /**
     * Loads a list of all files and their metadata for the frontend.
     *
     * @param int $courseid Moodle courseid
     * @param string $identifier Element identifier (optional if only one file should be loaded)
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_simple_filelist(int $courseid, string $identifier = ''): array {
        global $DB, $CFG;
        $overwritemetadata = get_config('local_oer', 'coursetofile');
        [$icons, $renderer] = icon::prepare_file_icon_renderer($courseid);
        $elements = self::get_course_files($courseid);
        $list = [];
        $originfilter = [];

        foreach ($elements as $element) {
            if (!empty($identifier) && $element->get_identifier() != $identifier) {
                continue;
            }

            $info = [];
            $inforesult = [];

            foreach ($element->get_information() as $information) {
                $info[$information->get_area()][] = [
                        'infoname' => $information->get_name(),
                        'infourl' => $information->get_url() ?? '',
                        'infohasurl' => $information->get_hasurl(),
                        'last' => false,
                ];
            }
            foreach ($info as $key => $information) {
                // No dynamic keys for webservice.
                $inforesult[] = [
                        'area' => $key,
                        'fields' => $information,
                ];
            }
            foreach ($inforesult as $key => $info) {
                $last = array_key_last($info['fields']);
                $inforesult[$key]['fields'][$last]['last'] = true;
            }

            $icon = icon::select_file_icon($element->get_mimetype(), $renderer, $icons);
            $preference = $DB->get_record('local_oer_preference', ['courseid' => $courseid]);
            $ignore = $preference && $preference->state == 2 ? 1 : 0;
            $decomposed = identifier::decompose($element->get_identifier());
            $metadata = $element->get_stored_metadata();
            $originlist = [];
            foreach ($element->get_origin() as $origin => $languagestring) {
                $name = get_string($languagestring[0], $languagestring[1]);
                $originlist[] = [
                        'origin' => $origin,
                        'originname' => $name,
                ];
                $originfilter[$origin] = $name;
            }
            $entry = [
                    'id' => $metadata->id ?? 0,
                    'contenthash' => $decomposed->valuetype == 'contenthash' ? $decomposed->value : '',
                    'identifier' => htmlentities($element->get_identifier()),
                    'idhash' => hash('SHA1', $element->get_identifier()),
                    'title' => $element->get_title(),
                    'mimetype' => $element->get_mimetype(),
                    'icon' => $icon,
                    'timemodified' => $metadata->timemodified ?? '-',
                    'timeuploaded' => $metadata->timereleased ?? '-',
                    'timeuploadedts' => $metadata->timereleasedts ?? 0,
                    'upload' => $metadata->upload ?? 0,
                    'ignore' => $metadata->ignore ?? $ignore,
                    'deleted' => 0,
                    'information' => $inforesult,
                    'requirementsmet' => $metadata->requirementsmet ?? false,
                    'state' => $element->get_elementstate()->state,
                    'multiple' => count($element->get_elementstate()->courses) > 1,
                    'editor' => $element->get_elementstate()->editorid,
                    'courses' => $element->get_elementstate()->courses,
                    'writable' => $element->get_elementstate()->writable,
                    'coursetofile' => $overwritemetadata == 1 && $element->get_elementstate()->editor == $courseid,
                    'wwwroot' => $CFG->wwwroot, // Add wwwroot, global.config.wwwroot in mustache does not add subfolders.
                    'origins' => $originlist,
            ];

            if (!empty($identifier) && $element->get_identifier() == $identifier) {
                return $entry;
            }
            $list[] = $entry;
        }
        // MDL-0 TODO: orphaned metadata is missing and has to be added..
        $originresult = [];
        foreach ($originfilter as $key => $entry) {
            $originresult[] = [
                    'origin' => $key,
                    'originname' => $entry,
            ];
        }
        return [$list, $originresult];
    }

    /**
     * Load a single file and the metadata of it for frontend.
     * This is a wrapper that calls get_simple_filelist with the optional contenthash parameter.
     *
     * @param int $courseid Moodle courseid
     * @param string $identifier Element identifier
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_simple_file(int $courseid, string $identifier): array {
        return self::get_simple_filelist($courseid, $identifier);
    }
}
