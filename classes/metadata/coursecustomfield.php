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

namespace local_oer\metadata;

/**
 * Class coursecustomfield
 *
 * Prepare and load course custom fields.
 */
class coursecustomfield {
    /**
     * This function loads all coursecustomfields with respect to the settings of the oer plugin.
     *
     * - no customfields are returned if coursecustomfields is turned off for OER
     * - visibility is checked and only fields are returned that match maximum visibility setting
     * - ignored fields are filtered away
     *
     * @param int $courseid Moodle courseid
     * @param bool $stored instead of loading the fields from Moodle directly, load the already stored OER version
     * @return array
     * @throws \dml_exception
     */
    public static function get_course_customfields_with_applied_config(int $courseid, bool $stored = false): array {
        if (!get_config('local_oer', 'coursecustomfields')) {
            return [];
        }
        $customfields = $stored ? self::load_course_customfields_from_oer($courseid) : self::get_course_customfields($courseid);
        $result = [];
        $visibility = get_config('local_oer', 'coursecustomfieldsvisibility');
        $ignored = get_config('local_oer', 'coursecustomfieldsignored');
        foreach ($customfields as $category) {
            $fields = [];
            foreach ($category['fields'] as $field) {
                $ignore = $category['id'] . ':' . $field['id'];
                if ($field['visibility'] >= $visibility && strpos($ignored, $ignore) === false) {
                    $fields[] = $field;
                }
            }
            $result[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'fields' => $fields,
            ];
        }

        return $result;
    }

    /**
     * Prepare and return the list of customfields from Moodle course to work with in oer plugin.
     * When using a courseid that does not exist, the customfields will fall back to the default values of the fields.
     *
     * @param int $courseid Moodle course id
     * @return array
     * @throws \Exception
     */
    public static function get_course_customfields(int $courseid): array {
        $customfields = [];
        $handler = \core_course\customfield\course_handler::create();
        $categories = $handler->get_categories_with_fields();
        foreach ($categories as $category) {
            $catid = (int) $category->get('id');
            $catname = $category->get('name');
            $fields = [];
            foreach ($category->get_fields() as $field) {
                $fieldid = (int) $field->get('id');
                $fielddata = $handler->get_instance_data($courseid, true)[$fieldid];
                $type = $field->get('type');
                $settings = $field->get('configdata');
                switch ($type) {
                    case 'select':
                        $data = self::get_text_of_select_field($fielddata->get_value(), $settings['options']);
                        break;
                    case 'textarea':
                        $data = is_null($fielddata->get_value()) ? '' :
                                courseinfo::simple_html_to_text_reduction($fielddata->get_value());
                        break;
                    default:
                        // Make some basic cleaning to other fields.
                        $data = trim(strip_tags($fielddata->get_value()));
                }
                $fields[] = [
                        'id' => $fieldid,
                        'shortname' => $field->get('shortname'),
                        'fullname' => $field->get('name'),
                        'type' => $field->get('type'),
                        'visibility' => $settings['visibility'],
                        'data' => $data,
                ];
            }
            $customfields[] = [
                    'id' => $catid,
                    'name' => $catname,
                    'fields' => $fields,
            ];
        }
        return $customfields;
    }

    /**
     * Load the stored values for customfields from the oer plugin.
     * Only the base course metadata can have course customfield data, as it is the type
     * that directly comes from Moodle. Other course metadata plugins load external data and
     * so it is not necessary to add those fields.
     *
     * @param int $courseid Moodle course id
     * @return array
     * @throws \dml_exception
     */
    public static function load_course_customfields_from_oer(int $courseid): array {
        global $DB;
        $customfields = $DB->get_field('local_oer_courseinfo', 'customfields',
                ['courseid' => $courseid, 'subplugin' => courseinfo::BASETYPE]);
        return !empty($customfields) ? json_decode($customfields, true) : [];
    }

    /**
     * Remove unnecessary information from the fields. Reduce to one layer.
     *
     * Entry per field: shortname, fullname, type, data, categoryname
     *
     * @param int $courseid
     * @return array
     * @throws \dml_exception
     */
    public static function get_customfields_for_snapshot(int $courseid): array {
        $customfields = self::get_course_customfields_with_applied_config($courseid, true);
        $snapshot = [];
        $typeconversion = [
                'checkbox' => 'bool',
                'date' => 'timestamp',
                'textarea' => 'text',
                'select' => 'text',
        ];
        foreach ($customfields as $category) {
            foreach ($category['fields'] as $field) {
                $snapshot[] = [
                        'shortname' => $field['shortname'],
                        'fullname' => $field['fullname'],
                        'type' => $typeconversion[$field['type']] ?? $field['type'],
                        'data' => $field['data'],
                        'category' => $category['name'],
                ];
            }
        }
        return $snapshot;
    }

    /**
     * Return the text of a select field value.
     *
     * @param int $value The stored int value of the options
     * @param string $selectoptions The string of the select options
     * @return string
     */
    public static function get_text_of_select_field(int $value, string $selectoptions): string {
        // Customfields add an empty string as first position.
        $options = array_merge([''], explode("\r\n", $selectoptions));
        return $options[$value] ?? '';
    }

    /**
     * Compare if the stored customfields are different from the fields which would be added.
     *
     * @param int $courseid Moodle courseid
     * @return bool
     * @throws \dml_exception
     */
    public static function compare_difference(int $courseid): bool {
        $stored = self::get_course_customfields_with_applied_config($courseid, true);
        $moodle = self::get_course_customfields_with_applied_config($courseid, false);
        return hash('sha256', json_encode($stored)) != hash('sha256', json_encode($moodle));
    }
}
