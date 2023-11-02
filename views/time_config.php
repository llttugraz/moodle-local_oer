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
 * @copyright  2017-2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

use local_oer\time\time_form;
use local_oer\time\time_settings;

$context = context_system::instance();
$url = new moodle_url('/local/oer/views/time_config.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_oer'));
$PAGE->set_heading(get_string('pluginname', 'local_oer'));
$PAGE->set_pagelayout('admin');
require_capability('local/oer:manage', $context);
require_login();

$mform = new time_form();

$returnurl = new moodle_url('/admin/settings.php?section=local_oer');

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($fromform = $mform->get_data()) {
    set_config(time_settings::CONF_RELEASETIME, $fromform->releasetime, 'local_oer');
    set_config(time_settings::CONF_RELEASEHOUR, $fromform->releasehour, 'local_oer');
    set_config(time_settings::CONF_CUSTOMDATES, $fromform->customdates, 'local_oer');
    time_settings::set_next_upload_window();
    $mform = new time_form();
}

echo $OUTPUT->header();
echo '<h4>' . get_string('configtime', 'local_oer') . '</h4>';
echo time_settings::get_timeslot_output(1);
$mform->display();
echo $OUTPUT->footer();
