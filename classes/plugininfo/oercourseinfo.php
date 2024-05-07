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
 * @copyright  2021-2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\plugininfo;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/oer/classes/plugininfo/plugininfo.php');

/**
 * Class oercourseinfo
 */
class oercourseinfo extends plugininfo {
    /**
     * @var string
     */
    protected static $subplugin = 'oercourseinfo';

    /**
     * @var string
     */
    protected static $enabledplugins = 'enabledcourseinfoplugins';
}
