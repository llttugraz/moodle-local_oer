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

/**
 * Class formhelper
 */
class formhelper {
    /**
     * Extend the select arrays with a null value for the preference form.
     *
     * @param array $values
     * @return array
     * @throws \coding_exception
     */
    public static function add_no_preference_value(array $values): array {
        return array_merge(['nopref' => get_string('nopreference', 'local_oer')], $values);
    }

    /**
     * Context list as defined by LOM standard.
     * TODO: other values than higher education have to be added.
     *
     * @param bool $localization Load the list for frontend with localization, or in english for metadata release.
     * @param bool $addnoprefval
     * @return array
     * @throws \coding_exception
     */
    public static function lom_context_list(bool $localization = true, bool $addnoprefval = false): array {
        $values = [
                "0" => $localization ? get_string('noselection', 'local_oer') : 'No selection',
                "1" => $localization ? get_string('highereducation', 'local_oer') : 'Higher Education',
        ];
        return $addnoprefval ? self::add_no_preference_value($values) : $values;
    }

    /**
     * Resourcetype list as defined by LOM standard.
     *
     * @param bool $localization Load the list for frontend with localization, or in english for metadata release.
     * @param bool $addnoprefval
     * @return array
     * @throws \coding_exception
     */
    public static function lom_resource_types(bool $localization = true, bool $addnoprefval = false): array {
        $values = [
                0 => $localization ? get_string('noselection', 'local_oer') : 'No selection',
                1 => $localization ? get_string('figure', 'local_oer') : 'Figure',
                2 => $localization ? get_string('diagram', 'local_oer') : 'Diagram',
                3 => $localization ? get_string('narrative', 'local_oer') : 'Narrative',
                4 => $localization ? get_string('experiment', 'local_oer') : 'Experiment',
                5 => $localization ? get_string('questionnaire', 'local_oer') : 'Questionnaire',
                6 => $localization ? get_string('graphic', 'local_oer') : 'Graphic',
                7 => $localization ? get_string('contents', 'local_oer') : 'Contents',
                8 => $localization ? get_string('presentationslide', 'local_oer') : 'Presentationslide',
                9 => $localization ? get_string('problem', 'local_oer') : 'Problem',
                10 => $localization ? get_string('exam', 'local_oer') : 'Exam',
                11 => $localization ? get_string('selfassesment', 'local_oer') : 'Selfassesment',
                12 => $localization ? get_string('chart', 'local_oer') : 'Chart',
                13 => $localization ? get_string('exercise', 'local_oer') : 'Exercise',
                14 => $localization ? get_string('lecture', 'local_oer') : 'Lecture',
        ];
        return $addnoprefval ? self::add_no_preference_value($values) : $values;
    }

    /**
     * The role the persons added to the file metadata have.
     *
     * @param array $roles List of roles supported, supported roles are set in subplugin.
     * @param bool $addnoprefval
     * @return array
     * @throws \coding_exception
     */
    public static function lom_role_types(array $roles, bool $addnoprefval = false): array {
        $values = [];
        foreach ($roles as $role) {
            $values[$role[0]] = get_string($role[1], $role[2]);
        }
        return $addnoprefval ? self::add_no_preference_value($values) : $values;
    }

    /**
     * The languages in ISO 639-1 format.
     * Load the languages from moodle, remove all languages that are not in ISO 639-1
     * (It should be all languages longer than 2 chars)
     *
     * @param bool $addnoprefval
     * @return array
     * @throws \coding_exception
     */
    public static function language_select_data(bool $addnoprefval = false): array {
        $controller = new \tool_langimport\controller();
        $languages = [
                0 => get_string('noselection', 'local_oer'),
        ];
        foreach ($controller->availablelangs as $language) {
            if (strlen($language[0]) == 2) {
                // Only use ISO 639-1 compatible codes.
                $languages[$language[0]] = $language[2];
            }
        }
        return $addnoprefval ? self::add_no_preference_value($languages) : $languages;
    }
}
