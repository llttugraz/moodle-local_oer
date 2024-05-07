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

require('../../../config.php');

global $PAGE, $USER, $OUTPUT, $DB;

$courseid = required_param('id', PARAM_INT);

$context = context_course::instance($courseid);
$id = $context->id;
$PAGE->set_context($context);

$course = get_course($courseid);
require_login($course);
require_capability('local/oer:viewitems', $context);

$url = new moodle_url('/local/oer/views/main.php', ['id' => $courseid]);
$PAGE->set_url($url, ['id' => $courseid]);
$PAGE->set_title(get_string('pluginname', 'local_oer'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$allowed = \local_oer\userlist\userlist::user_is_allowed($USER->id);

if ($allowed) {
    // If no courseinfo is stored yet, run the sync task.
    if (!$DB->record_exists('local_oer_courseinfo', ['courseid' => $courseid])) {
        $sync = new \local_oer\metadata\courseinfo_sync();
        $sync->sync_course($courseid);
    }
    $PAGE->requires->js_call_amd('local_oer/main-lazy', 'init', ['courseid' => $courseid]);
}

if (optional_param('download_zip', false, PARAM_BOOL)) {
    $zipper = new \local_oer\zipper();
    $zipper->download_zip_file($courseid);
}

$data = [
        'courseid' => $courseid,
        'context' => $context->id,
        'cantooglecourseactivation' => $allowed && has_capability('local/oer:manage', $context),
        'canmanage' => has_capability('local/oer:manage', $context),
        'downloadurl' => new moodle_url('/local/oer/views/main.php', ['id' => $courseid]),
        'allowed' => $allowed,
        'notallowed' => get_config('local_oer', 'notallowedtext'),
        'releaseinfo' => \local_oer\time\time_settings::get_timeslot_output(1),
        'isadmin' => is_siteadmin(), // Used for ZIP Download button, eventually add a new capability.
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_oer/main', $data);
echo $OUTPUT->footer();
