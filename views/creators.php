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
 * @copyright  2025 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
require('../../../config.php');

$context = context_system::instance();
global $PAGE, $OUTPUT;
$PAGE->set_context($context);

$url = new moodle_url('/local/oer/views/creators.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('oer_creators_short', 'local_oer'));
$PAGE->set_pagelayout('standard');

$data = \local_oer\userlist\userlist::creators_list();
$data['clarification'] = format_text(get_config('local_oer', 'creatorsviewinfo'), FORMAT_HTML);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_oer/creators', $data);
echo $OUTPUT->footer();
