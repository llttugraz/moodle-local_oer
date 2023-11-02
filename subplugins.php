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
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$type = optional_param('type', '', PARAM_PLUGIN);
$disable = optional_param('disable', '', PARAM_PLUGIN);
$enable = optional_param('enable', '', PARAM_PLUGIN);
$return = optional_param('return', 'overview', PARAM_ALPHA);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/oer/subplugins.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

if ($return === 'settings') {
    $returnurl = new moodle_url('/admin/settings.php', ['section' => 'local_oer']);
} else {
    $returnurl = new moodle_url('/admin/plugins.php');
}

if (!$type) {
    redirect($returnurl);
}

$settingname = 'enabled' . $type . 'plugins';

$enabled = [];
$enabledsubplugins = get_config('local_oer', $settingname);
if ($enabledsubplugins) {
    $enabledsubplugins = explode(',', $enabledsubplugins);
    foreach ($enabledsubplugins as $sp) {
        $sp = trim($sp);
        if ($sp !== '') {
            $enabled[$sp] = $sp;
        }
    }
}

if ($disable) {
    unset($enabled[$disable]);
} else if ($enable) {
    $enabled[$enable] = $enable;
}

set_config($settingname, implode(',', $enabled), 'local_oer');
core_plugin_manager::reset_caches();

redirect($returnurl);
