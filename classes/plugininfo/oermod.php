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
}
