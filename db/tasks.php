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

defined('MOODLE_INTERNAL') || die();

$tasks = [
        [
                'classname' => 'local_oer\task\update_courseinfo_task',
                'blocking' => 0,
                'minute' => '0',
                'hour' => '2',
                'day' => '*',
                'dayofweek' => '*',
                'month' => '*',
                'disabled' => 0,
        ],
        [
                'classname' => 'local_oer\task\create_snapshot_task',
                'blocking' => 0,
                'minute' => '*/20',
                'hour' => '*',
                'day' => '*',
                'dayofweek' => '*',
                'month' => '*',
                'disabled' => 0,
        ],
];
