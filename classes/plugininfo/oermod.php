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
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\plugininfo;

use local_oer\logger;
use local_oer\modules\element;
use local_oer\modules\module;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/oer/classes/plugininfo/plugininfo.php');

/**
 * Class oermodules
 */
class oermod extends plugininfo {
    /**
     * @var string
     */
    protected static $subplugin = 'oermod';

    /**
     * @var string
     */
    protected static $enabledplugins = 'enabledmodplugins';

    /**
     * Load all elements from a given subplugin.
     *
     * @param string $plugin shortname of plugin
     * @param int $courseid Moodle courseid
     * @return \local_oer\modules\elements
     * @throws \coding_exception
     */
    public static function load_elements(string $plugin, int $courseid): \local_oer\modules\elements {
        global $CFG;
        $modulepath = $CFG->dirroot . '/local/oer/modules/' . $plugin . '/classes/module.php';
        if (!file_exists($modulepath)) {
            throw new \coding_exception('Convention: Subplugin has to implement module class.');
        }
        require_once($modulepath);
        $classname = "oermod_$plugin\module";
        $module = new $classname();
        if (!in_array('local_oer\modules\module', class_implements($module))) {
            throw new \coding_exception('Convention: Subplugin class module has to implement local_oer\modules\module interface.');
        }
        return $module->load_elements($courseid);
    }

    /**
     * Write the allowed data back to the external source of the element.
     *
     * External can be another Moodle plugin or a non-Moodle tool/platform.
     *
     * @param element $element
     * @return void
     * @throws \coding_exception
     */
    public static function write_external_metadata(element $element): void {
        $module = self::get_module($element);
        $module->write_to_source($element);
    }

    /**
     * When a snapshot is created, the element is released.
     * Some sub-plugins may need to set the elements to be publicly accessible,
     * or have to do other steps.
     *
     * @param int $courseid Moodle course id
     * @param element $element
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function set_element_to_release(int $courseid, element $element): void {
        $module = self::get_module($element);
        $released = $module->set_element_to_release($element);
        if (!$released) {
            logger::add($courseid, logger::LOGERROR,
                    $element->get_identifier() . ' set to release did not work', get_class($module));
        }
    }

    /**
     * Return a language string of the writable fields the subplugin writes back to its source.
     *
     * @param element $element
     * @return ?string
     * @throws \coding_exception
     */
    public static function get_writable_fields(element $element): ?string {
        $module = self::get_module($element);
        $fields = $module->writable_fields();
        if (empty($fields)) {
            return null;
        }
        $language = [];
        foreach ($fields as $field) {
            $language[] = get_string($field[0], $field[1]);
        }
        $writable = implode(', ', $language);
        return get_string('writablefields', 'local_oer', ['fields' => $writable]);
    }

    /**
     * Get the subplugin module class for an element.
     *
     * @param element $element
     * @return module
     * @throws \coding_exception
     */
    private static function get_module(element $element): module {
        $subplugin = $element->get_subplugin();
        if (!class_exists($subplugin)) {
            throw new \coding_exception("Class $subplugin does not exist");
        }
        return new $subplugin();
    }

    /**
     * Return a list of supported licences for this element.
     *
     * @param element $element
     * @return array
     * @throws \coding_exception
     */
    public static function get_supported_licences(element $element): array {
        $module = self::get_module($element);
        return $module->supported_licences();
    }

    /**
     * Return a list of supported roles for this element.
     *
     * @param string $subplugin Name of the subplugin, created the element.
     * @return array
     * @throws \coding_exception
     */
    public static function get_supported_roles(string $subplugin): array {
        $module = new $subplugin();
        return $module->supported_roles();
    }
}
