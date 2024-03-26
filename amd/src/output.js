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

import * as Service from 'local_oer/service';
import * as Templates from "core/templates";
import * as Config from "local_oer/config";
import * as ModalFactory from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';
import * as Fragment from 'core/fragment';
import * as Str from 'core/str';

/**
 * Show the files in the browser.
 *
 * @param {boolean} init
 */
export const showFiles = (init) => {
    const output = Config.getOutputValues(init);
    const oldSearchInput = document.getElementById('local_oer_searchFilecardsInput');
    let oldSearchValue = '';
    if (oldSearchInput !== null) {
        oldSearchValue = oldSearchInput.value;
    }
    Templates.render('local_oer/files', output)
        .then(function(html, js) {
            Templates.replaceNodeContents('#local-oer-overview', html, js);
            const searchInput = document.getElementById('local_oer_searchFilecardsInput');
            if (searchInput !== null) {
                // Workaround to fix losing focus of search after Template.replaceNodeContents.
                // The navigation controls are inside the template and replaced after every search.
                searchInput.value = oldSearchValue;
                searchInput.focus();
            }
            return; // For eslint.
        }).fail(function(error) {
        window.console.debug(error);
    });
    showPagination();
};

/**
 * Load a form from the backend and show it in a modal.
 *
 * @param {string} type
 * @param {string} title
 * @param {object} options
 */
export const showForm = (type, title, options) => {
    const params = JSON.stringify(options);
    const courseid = document.getElementById("local_oer_files_main_area").dataset.courseid;
    const context = document.getElementById("local_oer_files_main_area").dataset.context;
    const args = {
        courseid: courseid,
        formtype: type,
        params: params,
    };
    const form = Fragment.loadFragment('local_oer', 'formdata', context, args);
    form.done(function(data) {
        // TODO: Better way to do this?
        let nosave = data.includes('<input name="nosave" type="hidden" value="1" />');
        if (nosave !== false) {
            type = 'nosave';
        }
        showFormModal(form, type, title, options);
    });
};


const showFormModal = (form, type, title, options) => {
    const element = document.getElementById("local_oer_files_main_area");
    const modaltype = type === 'nosave' ? ModalFactory.types.CANCEL : ModalFactory.types.SAVE_CANCEL;
    ModalFactory.create({
        type: modaltype,
        title: title,
        body: form,
    }).then(function(modal) {
        modal.setLarge();
        modal.show();
        switch (type) {
            case 'FileinfoForm':
            case 'FileinfoFormSave':
                initPersonButtonListener();
                addRemoveTagListener('storedperson');
                initSetPreferenceListener(modal);
                addInputFieldInputListener('stored', 'tags');
                break;
            case 'PreferenceForm':
            case 'PreferenceFormSave':
                // Add additional form listener(s) and special behaviour.
                initPersonButtonListener();
                addRemoveTagListener('storedperson');
                addInputFieldInputListener('stored', 'tags');
                break;
        }
        modal.getRoot().on(ModalEvents.hidden, function() {
            modal.destroy();
        });
        modal.getRoot().on(ModalEvents.save, function() {
            saveForm(modal, element, options, type, title);
        });
        return; // For eslint.
    }).catch(function(error) {
        window.console.debug(error);
    });
};

const saveForm = (modal, element, options, type, title) => {
    const formData = modal.getRoot().find('form').serialize();
    const saveargs = {
        courseid: element.dataset.courseid,
        formtype: type + 'Save',
        params: JSON.stringify(Object.assign(options, {settings: formData}))
    };
    const context = element.dataset.context;
    const formSubmit = Fragment.loadFragment('local_oer', 'formdata', context, saveargs);
    formSubmit.done(function(response) {
        if (response.indexOf('<form') !== -1) {
            modal.destroy();
            if (options.hasOwnProperty('preference')) {
                delete options.preference;
                replaceFileInfo(Service.loadFile(options.identifier));
            }
            showFormModal(formSubmit, type, title, options);
        } else if (type === 'FileinfoForm') {
            replaceFileInfo(Service.loadFile(options.identifier));
        } else if (type === 'PreferenceForm') {
            prepareFiles(Service.loadFiles(), true);
        }
    }).catch(function(error) {
        window.console.debug('Form submission failed', error);
    });
};

const replaceFileInfo = (promises) => {
    promises[0].done(function(response) {
        const filelist = document.getElementById("local-oer-overview-filelist").innerHTML;
        const output = JSON.parse(filelist);
        output.files.forEach(function(file, index) {
            if (file.identifier === response.file.identifier) {
                output.files[index] = response.file;
            }
        });
        document.getElementById("local-oer-overview-filelist").innerHTML = JSON.stringify(output);
        showFiles(false);
    });
};

/**
 * Prepare the filecard informations for output.
 *
 * @param {object} promises
 * @param {boolean} init
 */
export const prepareFiles = (promises, init) => {
    promises[0].done(function(response) {
        document.getElementById("local-oer-overview-filelist").innerHTML = JSON.stringify(response);
        showFiles(init);
    });
};

const addInputFieldInputListener = (prefix, area) => {
    showTags(prefix + area);
    document.getElementById('id_' + area).addEventListener('keypress', function(e) {
        if (e.key !== 'Enter') {
            return;
        }
        addStoredTag(prefix, area);
        showTags(prefix + area);
    });
    document.getElementById('id_' + area).addEventListener('focusout', function() {
        if (area.length === 0) {
            return;
        }
        addStoredTag(prefix, area);
        showTags(prefix + area);
    });
    addRemoveTagListener(prefix + area);
};

const addStoredTag = (prefix, tagarea) => {
    const select = document.getElementById('id_' + tagarea);
    let tag = select.value;
    tag = tag.replace(',', ' ').trim();
    const tags = document.querySelector('[name="' + prefix + tagarea + '"]').value;
    document.getElementById('id_' + tagarea).value = '';
    if (!tags.includes(tag)) {
        if (tags === '') {
            document.querySelector('[name="' + prefix + tagarea + '"]').value = tag;
        } else {
            document.querySelector('[name="' + prefix + tagarea + '"]').value = tags + ',' + tag;
        }
    }
};

const addRemoveTagListener = (tagarea) => {
    document.getElementById('local_oer_' + tagarea + '_tagarea').addEventListener('click', function(e) {
        if (e.target.dataset.name !== tagarea) {
            return;
        }
        if (tagarea === 'storedperson') {
            removePerson(e.target.dataset);
        } else {
            let name = e.target.dataset.value;
            let tags = document.querySelector('[name="' + tagarea + '"]').value.split(',');
            let result = [];
            tags.forEach(function(tag) {
                if (tag !== '' && tag !== name) {
                    result.push(tag);
                }
            });
            document.querySelector('[name="' + tagarea + '"]').value = result.join(',');
            showTags(tagarea);
        }
    });
};

const showTags = (tagarea) => {
    let entries = document.querySelector('[name="' + tagarea + '"]').value;
    let tags = {tags: []};
    if (entries.length > 0) {
        entries = entries.split(',');
        entries.forEach(function(entry) {
            tags.tags.push({tagarea: tagarea, tagvalue: entry, tag: entry});
        });
    } else {
        tags = false;
    }

    Templates.render('local_oer/tags', tags)
        .then(function(html, js) {
            Templates.replaceNodeContents('#local_oer_' + tagarea + '_tagarea', html, js);
            return; // For eslint.
        }).fail(function(e) {
            window.console.log(e);
        }
    );
};

const initSetPreferenceListener = (modal) => {
    const button = document.getElementById("local_oer_preferenceResetButton");
    if (button === null) {
        return;
    }

    button.addEventListener("click", function(action) {
        action.preventDefault();
        let options = {
            identifier: button.dataset.identifier,
            preference: button.dataset.preference
        };
        let element = document.getElementById("local_oer_files_main_area");
        saveForm(modal, element, options, 'FileinfoForm', 'FileinfoForm');
    });
};

const initPersonButtonListener = () => {
    const button = document.getElementById("local_oer_addPersonButton");
    if (button === null) {
        return;
    }

    showPersons();

    button.addEventListener("click", function(action) {
        action.preventDefault();
        showPersonForm();
    });
};

const showPersonForm = (setInvalid) => {
    if (typeof setInvalid === 'undefined') {
        setInvalid = false;
    }
    const creator = document.querySelector('[name="creator"]').value;
    const context = document.getElementById("local_oer_files_main_area").dataset.context;
    const form = Fragment.loadFragment('local_oer', 'personform', context, {'creator': creator});
    form.done(function() {
        let title = Str.get_string('addpersonbtn', 'local_oer');
        title.done(function(localizedTitle) {
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: localizedTitle,
                body: form,
            }).then(function(modal) {
                modal.setSaveButtonText(localizedTitle);
                modal.getRoot().on(ModalEvents.hidden, function() {
                    modal.destroy();
                });
                modal.getRoot().on(ModalEvents.save, function() {
                    const formData = modal.getRoot().find('form').serialize();
                    const fields = formData.split('&');
                    let role = '';
                    let firstname = '';
                    let lastname = '';
                    fields.forEach(function(field) {
                        let pair = field.split('=');
                        let key = pair[0];
                        let value = pair[1];
                        switch (key) {
                            case 'role':
                                role = value;
                                break;
                            case 'firstname':
                                firstname = value;
                                break;
                            case 'lastname':
                                lastname = value;
                                break;
                        }
                    });
                    const name = {
                        role: role,
                        firstname: firstname,
                        lastname: lastname,
                    };
                    addPerson(name);

                });
                modal.show();
                if (setInvalid) {
                    let element = document.getElementById("id_firstname");
                    element.classList.add("is-invalid");
                    element = document.getElementById("id_lastname");
                    element.classList.add("is-invalid");
                }
                return; // For eslint.
            }).catch(function(error) {
                window.console.debug(error);
            });
        });
    });
};

/**
 * People to add always have a first- and lastname and role.
 *
 * When already a person is defined with the same role and a full name,
 * first and lastname is added. This variant will be preferred.
 *
 * @param {{role: string, firstname: string, lastname: string}} person
 */
const addPerson = (person) => {
    let names = document.querySelector('[name="storedperson"]').value;
    if (names !== '') {
        names = JSON.parse(names);
        let found = false;
        let update = false;
        names.persons.forEach(function(storedname, key) {
            if (person.role === storedname.role
                && person.firstname === storedname.firstname
                && person.lastname === storedname.lastname) {
                found = true;
            }
            if (person.role === storedname.role
                && person.firstname + ' ' + person.lastname === storedname.fullname) {
                found = true;
                update = true;
                names.persons[key].firstname = person.firstname;
                names.persons[key].lastname = person.lastname;
            }
        });
        // If one of the required fields is empty, reload the form.
        if (person.firstname === '' || person.lastname === '') {
            showPersonForm(true);
            return;
        } else if (!found) {
            names.persons.push(person);
            document.querySelector('[name="storedperson"]').value = JSON.stringify(names);
        } else if (update) {
            document.querySelector('[name="storedperson"]').value = JSON.stringify(names);
        }
    } else {
        document.querySelector('[name="storedperson"]').value = JSON.stringify({persons: [person]});
    }
    showPersons();
};

const removePerson = (person) => {
    let entries = document.querySelector('[name="storedperson"]').value;
    if (entries === '') {
        return;
    }
    entries = JSON.parse(entries);
    const persons = {persons: []};
    entries.persons.forEach(function(storedperson) {
        if (person.role === storedperson.role
            && ((person.firstname === storedperson.firstname
                    && person.lastname === storedperson.lastname)
                || person.fullname === storedperson.fullname)) {
            // Skip this person.
        } else {
            persons.persons.push(storedperson);
        }
    });
    document.querySelector('[name="storedperson"]').value = JSON.stringify(persons);
    showPersons();
};

const showPersons = () => {
    let entries = document.querySelector('[name="storedperson"]').value;
    if (entries === '') {
        return;
    }
    const roles = JSON.parse(document.querySelector('[name="personroletypes"]').value);
    let strings = [];
    roles.forEach(function(role) {
        strings.push({
            key: role[1],
            component: role[2],
        });
    });
    Str.get_strings(strings).then(function(results) {
        entries = JSON.parse(entries);
        let persons = {persons: []};
        entries.persons.forEach(function(person) {
            let localizedrole = results[0];
            roles.forEach(function(role, index) {
                if (role[0] === person.role) {
                    localizedrole = results[index];
                }
            });
            persons.persons.push({
                role: person.role,
                firstname: person.firstname,
                lastname: person.lastname,
                fullname: person.fullname,
                name: decodeURI(localizedrole + ': ' +
                    (person.fullname === undefined ?
                        person.firstname + ' ' + person.lastname : person.fullname))
            });
        });
        renderPersonsTemplate(persons);
        return; // For eslint.
    }).fail(function(e) {
            window.console.debug(e);
        }
    );
};

const renderPersonsTemplate = (persons) => {
    Templates.render('local_oer/persons', persons)
        .then(function(html, js) {
            Templates.replaceNodeContents('#local_oer_storedperson_tagarea', html, js);
            return; // For eslint.
        }).fail(function(e) {
            window.console.log(e);
        }
    );
};

const showPagination = () => {
    const courseid = document.getElementById("local_oer_files_main_area").dataset.courseid;
    // Filecount is the number of elements currently available due to filter restrictions.
    let filecount = parseInt(localStorage.getItem('local-oer-pagination-filecount-' + courseid), 10);
    const filelist = document.getElementById("local-oer-overview-filelist").innerHTML;
    const output = JSON.parse(filelist);
    const filemax = output.files.length;
    let selected = localStorage.getItem('local-oer-pagination-selected-' + courseid);
    let page = parseInt(localStorage.getItem('local-oer-pagination-current-' + courseid), 10);
    let pages = 1;
    if (isNaN(filecount)) {
        filecount = filemax;
    }
    if (selected !== "all") {
        if (selected === null) {
            selected = "8";
        }
        const amount = parseInt(selected, 10);
        pages = Math.ceil(filecount / amount);
    }
    const result = [];
    if (isNaN(page)) {
        page = 1;
    }
    if (page > pages) {
        localStorage.setItem('local-oer-pagination-current-' + courseid, "1");
        page = 1;
    }
    if (pages <= 10) {
        for (let i = 1; i <= pages; i++) {
            result.push({page: i, active: i === page, disabled: false});
        }
    } else {
        // These two cases have both 7 elements, so the length will not fluctuate when changing elements by << or >>;
        if (page < 3 || page > (pages - 2)) {
            result.push({page: 1, active: page === 1, disabled: false});
            result.push({page: 2, active: page === 2, disabled: false});
            result.push({page: 3, active: page === 3, disabled: false});
            result.push({page: "..", active: false, disabled: true});
            result.push({page: pages - 2, active: page === (pages - 2), disabled: false});
            result.push({page: pages - 1, active: page === (pages - 1), disabled: false});
            result.push({page: pages, active: page === pages, disabled: false});
        } else if (page === 3) {
            result.push({page: 1, active: false, disabled: false});
            result.push({page: 2, active: false, disabled: false});
            result.push({page: 3, active: true, disabled: false});
            result.push({page: 4, active: false, disabled: false});
            result.push({page: "..", active: false, disabled: true});
            result.push({page: pages - 1, active: page === (pages - 1), disabled: false});
            result.push({page: pages, active: page === pages, disabled: false});
        } else if (page === (pages - 2)) {
            result.push({page: 1, active: false, disabled: false});
            result.push({page: 2, active: false, disabled: false});
            result.push({page: "..", active: false, disabled: true});
            result.push({page: pages - 3, active: false, disabled: false});
            result.push({page: pages - 2, active: true, disabled: false});
            result.push({page: pages - 1, active: false, disabled: false});
            result.push({page: pages, active: page === pages, disabled: false});
        } else {
            result.push({page: 1, active: false, disabled: false});
            result.push({page: "..", active: false, disabled: true});
            result.push({page: page - 1, active: false, disabled: false});
            result.push({page: page, active: true, disabled: false});
            result.push({page: page + 1, active: false, disabled: false});
            result.push({page: "..", active: false, disabled: true});
            result.push({page: pages, active: false, disabled: false});
        }
    }
    const data = {
        selectoptions: paginationSelectOptions(selected),
        control: pages !== 1,
        previous: page > 1,
        next: page < pages,
        pages: pages === 1 ? false : result,
        filecount: filecount,
        filemax: filemax
    };
    Templates.render('local_oer/pagination', data)
        .then(function(html, js) {
            Templates.replaceNodeContents('#local-oer-pagination', html, js);
            addPaginationListeners(pages);
            return; // For eslint.
        }).fail(function(error) {
        window.console.debug(error);
    });
};

const paginationSelectOptions = (selected) => {
    return [
        {value: "4", selected: selected === "4"},
        {value: "8", selected: selected === "8"},
        {value: "12", selected: selected === "12"},
        {value: "16", selected: selected === "16"},
        {value: "32", selected: selected === "32"},
        {value: "64", selected: selected === "64"},
        {value: "all", selected: selected === "all"},
    ];
};

const addPaginationListeners = (pages) => {
    let courseid = document.getElementById("local_oer_files_main_area").dataset.courseid;
    let selected = localStorage.getItem('local-oer-pagination-selected-' + courseid);
    let page = localStorage.getItem('local-oer-pagination-current-' + courseid);
    if (page === null || page === 'undefined') {
        localStorage.setItem('local-oer-pagination-current-' + courseid, "1");
    }
    if (selected === null || selected === 'undefined') {
        localStorage.setItem('local-oer-pagination-selected-' + courseid, "8");
    }
    let selectlistener = document.getElementById('local-oer-pagination-select');
    let pagelistener = document.getElementById('local-oer-pagination-pages');

    selectlistener.addEventListener("click", function(action) {
        action.preventDefault();
        let value = action.target.value;
        localStorage.setItem('local-oer-pagination-selected-' + courseid, value);
        localStorage.setItem('local-oer-pagination-current-' + courseid, "1");
        showFiles(false);
    });

    pagelistener.addEventListener("click", function(action) {
        action.preventDefault();
        let value = action.target.dataset.page;
        if (value === undefined || value === '..') {
            return;
        }
        let page = parseInt(localStorage.getItem('local-oer-pagination-current-' + courseid));
        switch (value) {
            case 'previous':
                value = page > 1 ? page - 1 : 1;
                break;
            case 'next':
                value = page < pages ? page + 1 : pages;
                break;
        }
        localStorage.setItem('local-oer-pagination-current-' + courseid, value);
        showFiles(false);
    });
};