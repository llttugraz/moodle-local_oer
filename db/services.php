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

defined('MOODLE_INTERNAL') || die();

$functions = [
        'local_oer_get_files' => [
                'classname' => 'local_oer\\services\\get_files',
                'methodname' => 'service',
                'description' => 'Get basic metadata of oer files of a course.',
                'type' => 'read',
                'ajax' => true,
                'loginrequired' => true,
        ],
        'local_oer_get_file' => [
                'classname' => 'local_oer\\services\\get_file',
                'methodname' => 'service',
                'description' => 'Get basic metadata of one oer file of a course.',
                'type' => 'read',
                'ajax' => true,
                'loginrequired' => true,
        ],
];
