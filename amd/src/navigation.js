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
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as UserPreference from 'local_oer/userpreferences';
import * as Output from 'local_oer/output';
import * as Str from 'core/str';

/**
 * Add listener for layout menu.
 */
export const initLayoutListener = () => {
    var element = document.getElementById("local_oer-select-layout");
    if (element === null) {
        return;
    }

    element.addEventListener("click", function(action) {
        action.preventDefault();
        UserPreference.setLayout(action.target.dataset.value);
        Output.showFiles();
    });
};

/**
 * Add listener for sort menu.
 */
export const initSortListener = () => {
    var element = document.getElementById("local_oer-sort-files");
    if (element === null) {
        return;
    }

    element.addEventListener("click", function(action) {
        action.preventDefault();
        UserPreference.setSort(action.target.dataset.value);
        Output.showFiles();
    });
};

/**
 * Add listener for filter menu.
 */
export const initGroupingListener = () => {
    var element = document.getElementById("local_oer-select-grouping");
    if (element === null) {
        return;
    }

    element.addEventListener("click", function(action) {
        action.preventDefault();
        if (typeof action.target.dataset.value === 'undefined') {
            return;
        }
        let filtervalue = action.target.dataset.filtervalue;
        UserPreference.setFilter(action.target.dataset.value, filtervalue);
        Output.showFiles();
    });
};

/**
 * Add listener for courseinfo button.
 */
export const initCourseinfoListener = () => {
    var element = document.getElementById("local_oer_courseinfo_button");
    if (element === null) {
        return;
    }

    element.addEventListener("click", function(action) {
        action.preventDefault();
        let title = Str.get_string('courseinfobutton', 'local_oer');
        title.done(function(localizedTitle) {
            Output.showForm('CourseinfoForm', localizedTitle, {});
        });
    });
};

/**
 * Add listener for preference button.
 */
export const initPreferenceListener = () => {
    var element = document.getElementById("local_oer_preference_button");
    if (element === null) {
        return;
    }

    element.addEventListener("click", function(action) {
        action.preventDefault();
        let title = Str.get_string('preferencebutton', 'local_oer');
        title.done(function(localizedTitle) {
            Output.showForm('PreferenceForm', localizedTitle, {});
        });
    });
};

/**
 * Add listener for text search field.
 */
export const initSearchListener = () => {
    let searchInput = document.getElementById('local_oer_searchFilecardsInput');
    if (searchInput === null) {
        return;
    }
    let searchFiles = debounce(function() {
        Output.showFiles();
    }, 500, false);

    searchInput.addEventListener("keyup", searchFiles);
    searchInput.addEventListener("search", function() {
        Output.showFiles();
    });
};

/**
 * Returns a function, that, as long as it continues to be invoked, will not
 * be triggered. The function will be called after it stops being called for
 * N milliseconds. If `immediate` is passed, trigger the function on the
 * leading edge, instead of the trailing.
 * Taken from https://davidwalsh.name/javascript-debounce-function
 *
 * @param {function} func
 * @param {int} wait
 * @param {boolean} immediate
 * @returns {(function(): void)|*}
 */
const debounce = (func, wait, immediate) => {
    let timeout;
    return function() {
        let context = this;
        let args = arguments;
        let later = function() {
            timeout = null;
            if (!immediate) {
                func.apply(context, args);
            }
        };
        let callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) {
            func.apply(context, args);
        }
    };
};