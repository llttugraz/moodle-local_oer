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
 * @copyright  2017 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_oer\time\time_settings;

/**
 * Parameters that will be set on installing this plugin.
 */
function xmldb_local_oer_install() {
    $plugin = 'local_oer';
    set_config(time_settings::CONF_RELEASETIME, time_settings::MONTH, $plugin);
    set_config(time_settings::CONF_RELEASEHOUR, '4:00', $plugin);
    set_config(time_settings::CONF_CUSTOMDATES, '', $plugin);
    time_settings::set_next_upload_window();
}
