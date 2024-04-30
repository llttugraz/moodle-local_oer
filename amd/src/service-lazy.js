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
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2022-2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Ajax from 'core/ajax';

/**
 * Load all file information from backend with ajax service.
 *
 * @returns {*}
 */
export const loadFiles = () => {
    const courseid = document.getElementById("local_oer_files_main_area").dataset.courseid;
    return Ajax.call([
        {
            methodname: 'local_oer_get_files',
            args: {courseid: courseid}
        },
    ]);
};

/**
 * Load information from a single file from backend.
 *
 * @param {string} identifier
 * @returns {*}
 */
export const loadFile = (identifier) => {
    const courseid = document.getElementById("local_oer_files_main_area").dataset.courseid;
    return Ajax.call([
        {
            methodname: 'local_oer_get_file',
            args: {courseid: courseid, identifier: identifier}
        },
    ]);
};