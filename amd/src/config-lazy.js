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

import * as UserPreference from 'local_oer/userpreferences-lazy';

/**
 * Loads all necessary navigation settings and prepares the data for output.
 * Filter the files.
 * Sort the files.
 * Switch card/list view.
 *
 * @param {boolean} init show different ouptput when no files are found
 * @returns {any}
 */
export const getOutputValues = (init) => {
    const filelist = document.getElementById("local-oer-overview-filelist").innerHTML;
    const output = JSON.parse(filelist);
    output.init = init === undefined ? false : init;
    output.shownavigation = output.files.length > 0;
    // Filter options.
    const filters = ['f_all', 'f_upload', 'f_norelease', 'f_ignore', 'f_noignore', 'f_deleted', 'f_origin', 'f_released'];

    output.founddeleted = false;
    output.files.forEach((file) => {
        if (file.deleted === 1) {
            output.founddeleted = true;
        }
    });

    const chosenfilterobject = UserPreference.getFilter();
    const chosenfilter = chosenfilterobject.filter;
    const additionalvalue = chosenfilterobject.value;
    filters.forEach((filter) => {
        output[filter] = false;
        if (filter === chosenfilter) {
            output[filter] = true;
        }
    });

    let countelements = {
        "f_all": output.files.length,
        "f_upload": 0,
        "f_norelease": 0,
        "f_ignore": 0,
        "f_noignore": 0,
        "f_released": 0,
        "f_deleted": 0,
        // Origins are counted in the origin property.
    };
    // MDL-0 TODO: there has to be a better way to calculate this?
    output.files.forEach((file) => {
        if (file.upload === true && file.timeuploadedts === 0) {
            countelements.f_upload++;
        }
        if (file.upload === false && file.ignore === false) {
            countelements.f_norelease++;
        }
        if (file.ignore === true) {
            countelements.f_ignore++;
        }
        if (file.ignore === false) {
            countelements.f_noignore++;
        }
        if (file.timeuploadedts > 0) {
            countelements.f_released++;
        }
        if (file.deleted === true) {
            countelements.f_deleted++;
        }
        file.origins.forEach((origin) => {
            output.origin.forEach((globalorigin, key) => {
                if (globalorigin.origin === origin.origin) {
                    if (typeof output.origin[key].count !== "undefined") {
                        output.origin[key].count++;
                    } else {
                        output.origin[key].count = 1;
                    }
                }
            });
        });
    });
    output.countelements = countelements;

    const filteredFiles = [];
    if (chosenfilter !== 'f_all') {
        output.init = false;
        output.files.forEach((file) => {
            switch (chosenfilter) {
                case 'f_upload':
                    if (file.upload === true && file.timeuploadedts === 0) {
                        filteredFiles.push(file);
                    }
                    break;
                case 'f_norelease':
                    if (file.upload === false && file.ignore === false) {
                        filteredFiles.push(file);
                    }
                    break;
                case 'f_ignore':
                    if (file.ignore === true) {
                        filteredFiles.push(file);
                    }
                    break;
                case 'f_noignore':
                    if (file.ignore === false) {
                        filteredFiles.push(file);
                    }
                    break;
                case 'f_released':
                    if (file.timeuploadedts > 0) {
                        filteredFiles.push(file);
                    }
                    break;
                case 'f_deleted':
                    if (file.deleted === true) {
                        filteredFiles.push(file);
                    }
                    break;
                case 'f_origin':
                    file.origins.forEach((origin) => {
                        if (origin.origin === additionalvalue) {
                            filteredFiles.push(file);
                        }
                    });
                    break;
            }
        });
        output.files = filteredFiles;
    }

    const search = document.getElementById('local_oer_searchFilecardsInput');
    if (search) {
        const searchValue = search.value;
        const searchOutput = [];
        if (searchValue !== '') {
            output.files.forEach((file) => {
                if (file.title.toLowerCase().includes(searchValue.toLowerCase())) {
                    searchOutput.push(file);
                }
            });
            output.files = searchOutput;
        }
    }

    const sortOptions = ['s_default', 's_title_asc', 's_title_desc', 's_mimetype', 's_released'];
    const chosensort = UserPreference.getSort();
    sortOptions.forEach((option) => {
        output[option] = false;
        if (option === chosensort) {
            output[option] = true;
        }
    });
    switch (chosensort) {
        case 's_default':
            output.files.sort((a, b) => a.ignore - b.ignore);
            break;
        case 's_title_asc':
            output.files.sort((a, b) => a.title.toLowerCase().localeCompare(b.title.toLowerCase()));
            break;
        case 's_title_desc':
            output.files.sort((b, a) => a.title.toLowerCase().localeCompare(b.title.toLowerCase()));
            break;
        case 's_mimetype':
            output.files.sort((a, b) => a.mimetype.toLowerCase().localeCompare(b.mimetype.toLowerCase()));
            break;
        case 's_released':
            output.files.sort((b, a) => a.timeuploadedts - b.timeuploadedts);
            break;
    }

    // After sorting and filtering the files, the pagination will be applied.
    const courseid = document.getElementById("local_oer_files_main_area").dataset.courseid;
    let selected = localStorage.getItem('local-oer-pagination-selected-' + courseid);
    let page = parseInt(localStorage.getItem('local-oer-pagination-current-' + courseid), 10);
    if (isNaN(page)) {
        page = 1;
        localStorage.setItem('local-oer-pagination-current-' + courseid, "1");
    }
    // Now it gets weird. Reset the page to 1 if oldfiles differ from the current amount of files.
    const oldfiles = parseInt(localStorage.getItem('local-oer-pagination-filecount-' + courseid), 10);
    localStorage.setItem('local-oer-pagination-filecount-' + courseid, output.files.length.toString());
    if (!isNaN(oldfiles) && oldfiles !== output.files.length && page !== 1) {
        page = 1;
        localStorage.setItem('local-oer-pagination-current-' + courseid, "1");
    }
    const shownFiles = [];
    if (selected !== "all") {
        if (selected === null) {
            selected = "8";
        }
        const amount = parseInt(selected, 10);
        const firstelement = (page - 1) * amount;
        const lastelement = (page * amount) - 1;
        output.files.forEach((file, index) => {
            if (index >= firstelement && index <= lastelement) {
                shownFiles.push(file);
            }
        });
        output.files = shownFiles;
    }

    switch (UserPreference.getLayout()) {
        case 'list':
            output.card = false;
            output.list = true;
            break;
        case 'card':
        default:
            output.card = true;
            output.list = false;
    }

    if (chosenfilter === 'f_origin') {
        output.origin.forEach((origin) => {
            if (additionalvalue === origin.origin) {
                output.oname = origin.originname;
            }
        });
    }
    return output;
};