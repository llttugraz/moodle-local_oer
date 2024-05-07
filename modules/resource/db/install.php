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
 * OER subplugin for loading mod_resource files
 *
 * @package    oermod_resource
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2024 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Parameters that will be set on installing this plugin.
 */
function xmldb_oermod_resource_install() {
    $enabledsubplugins = get_config('local_oer', 'enabledmodplugins');
    $enabled = explode(',', $enabledsubplugins);
    if (!in_array('resource', $enabled)) {
        $enabled[] = 'resource';
        set_config('enabledmodplugins', implode(',', $enabled), 'local_oer');
        core_plugin_manager::reset_caches();
    }
}
