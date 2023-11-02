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
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Output from 'local_oer/output';

/**
 * Add listener to the filecard file menu
 *
 * @param {string} contenthash
 */
export const initActionMenuListener = (contenthash) => {
    let menu = document.getElementById("local_oer-action-menu-" + contenthash);
    if (menu === null) {
        return;
    }

    menu.addEventListener("click", function (action) {
        action.preventDefault();
        let type = action.target.dataset.action;
        let ch = action.target.dataset.contenthash;
        let title = action.target.dataset.title;
        switch (type) {
            case 'edit-file':
                Output.showForm("FileinfoForm", title, {contenthash: ch});
                break;
            case 'course-to-file':
                Output.showForm("CourseToFileForm", title, {contenthash: ch});
                break;
        }
    });
};