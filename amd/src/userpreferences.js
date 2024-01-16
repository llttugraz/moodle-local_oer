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

const getCourseid = () => {
    return document.getElementById("local_oer_files_main_area").dataset.courseid;
};

/**
 * Set the card or file layout.
 *
 * @param {string} value
 */
export const setLayout = (value) => {
    setLocalStorage('local-oer-layout-' + getCourseid(), value);
};

/**
 * Return the selected layout type.
 *
 * @returns {string}
 */
export const getLayout = () => {
    return getValue('local-oer-layout-' + getCourseid(), 'card');
};

/**
 * Set the chosen sort type.
 *
 * @param {string} value
 */
export const setSort = (value) => {
    localStorage.setItem('local-oer-sort-' + getCourseid(), value);
};

/**
 * Get the chosen sort type.
 *
 * @returns {string}
 */
export const getSort = () => {
    return getValue('local-oer-sort-' + getCourseid(), 's_default');
};

/**
 * Set the chosen filter type.
 *
 * @param {string} value
 * @param {string} filtervalue
 */
export const setFilter = (value, filtervalue) => {
    localStorage.setItem('local-oer-filter-' + getCourseid(), value);
    localStorage.setItem('local-oer-filtervalue-' + getCourseid(), filtervalue);
};

/**
 * Get the filter value
 *
 * @returns {{filter: string, value: string}}
 */
export const getFilter = () => {
    const filter = getValue('local-oer-filter-' + getCourseid(), 'f_all');
    const filtervalue = getValue('local-oer-filtervalue-' + getCourseid(), undefined);
    return {filter: filter, value: filtervalue};
};

const setLocalStorage = (key, value) => {
    localStorage.setItem(key, value);
};

const getValue = (key, defaultValue) => {
    let retval = localStorage.getItem(key);
    if (retval === null) {
        retval = defaultValue;
    }
    return retval;
};