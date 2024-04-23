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
 * @copyright  2019 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

$context = context_system::instance();
$PAGE->set_context($context);

require_login();
require_capability('local/oer:manage', $context);

$url = new moodle_url('/local/oer/views/releasehistory.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_oer'));
$PAGE->set_heading(get_string('manageview', 'local_oer'));
$PAGE->set_pagelayout('admin');

global $DB, $CFG;

if (optional_param('create_snapshots', false, PARAM_BOOL)) {
    \local_oer\helper\snapshothelper::create_snapshots_of_all_active_courses();
}

$activecourses = \local_oer\helper\activecourse::get_list_of_courses(true);
$data = [];
foreach ($activecourses as $course) {
    $coursename = $DB->get_field('course', 'fullname', ['id' => $course->courseid]);
    $snapshot = new \local_oer\snapshot($course->courseid);
    $files = $snapshot->get_latest_course_snapshot();
    $data['courses'][] = [
            'url' => new moodle_url('/local/oer/views/main.php', ['id' => $course->courseid]),
            'course' => $course->courseid . ': ' . $coursename,
            'files' => count($files),
        // MDL-0 TODO - detailed infos about files - history etc..
    ];
}

$data['releaseurl'] = $url;
$data['isadmin'] = is_siteadmin();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_oer/releasehistory', $data);
echo $OUTPUT->footer();
