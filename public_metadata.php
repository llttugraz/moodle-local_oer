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

// TODO:
// Create cache of these files - as all information is coming from snapshots
// The lifetime can be coupled with creating snapshots.

// This script serves public accessible information.
// No guest user check or other login is required.
// Metadata served by this script has been released.
// @codingStandardsIgnoreLine
require_once('../../config.php');

if (get_config('local_oer', 'pullservice') != 1) {
    http_response_code(403);
    throw new moodle_exception('OER Pull service is deactivated');
}

$identifier = optional_param('identifier', false, PARAM_TEXT);
$release = optional_param('release', false, PARAM_INT);
if ($identifier) {
    \local_oer\identifier::validate($identifier);
}

// Increase application profile when metadata changes.
// Update 2024-02-22: Metadata has been extended for external elements.
// Backwards compatible.
$result = [
        'applicationprofile' => 'v1.0.1',
];

$context = context_system::instance();
global $PAGE;
$PAGE->set_context($context);
$result = array_merge($result, \local_oer\release::get_latest_releases());

header('Content-Type: application/json');
echo json_encode($result);

