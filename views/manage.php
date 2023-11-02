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

require('../../../config.php');

$context = context_system::instance();
$PAGE->set_context($context);

require_login();
require_capability('local/oer:manage', $context);

$url = new moodle_url('/local/oer/views/manage.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_oer'));
$PAGE->set_heading(get_string('manageview', 'local_oer'));
$PAGE->set_pagelayout('admin');

$type = get_config('local_oer', 'allowedlist') == '1'
        ? \local_oer\userlist\userlist::TYPE_A
        : \local_oer\userlist\userlist::TYPE_D;

$potentialuserselector = new \local_oer\userlist\user_selector('addselect',
        [
                'displayallowedusers' => 0,
                'type' => $type,
        ]);
$alloweduserselector = new \local_oer\userlist\user_selector('removeselect',
        [
                'displayallowedusers' => 1,
                'type' => $type,
        ]);

global $DB;
if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoassign = $potentialuserselector->get_selected_users();
    if (!empty($userstoassign)) {
        foreach ($userstoassign as $adduser) {
            $user = new stdClass();
            $user->userid = $adduser->id;
            $user->timecreated = time();
            $user->type = $type;
            $DB->insert_record('local_oer_userlist', $user);
        }
        $potentialuserselector->invalidate_selected_users();
        $alloweduserselector->invalidate_selected_users();
    }
}

if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoremove = $alloweduserselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $removeuser) {
            $user = [];
            $user['userid'] = $removeuser->id;
            $user['type'] = $type;
            $DB->delete_records('local_oer_userlist', $user);
        }
        $potentialuserselector->invalidate_selected_users();
        $alloweduserselector->invalidate_selected_users();
    }
}

$renderer = $PAGE->get_renderer('local_oer');

echo $OUTPUT->header();

$selectoroptions = new stdClass();
$selectoroptions->alloweduserselector = $alloweduserselector;
$selectoroptions->potentialuserselector = $potentialuserselector;
echo $renderer->oer_user_selector($selectoroptions);

// Display the list of allowed users with their options (ip/timecreated / validuntil...).
// Check that the user has the service required capability (if needed).
if (!empty($allowedusers)) {
    $renderer = $PAGE->get_renderer('local_oer');
    echo $renderer->oer_user_list($allowedusers);
}

echo $OUTPUT->footer();
