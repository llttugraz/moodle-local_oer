### 2024-10-31 - v2.3.1
* Update for compatibility with Moodle 4.5
* Add tests for included subplugins oermod_folder and oermod_resource


### 2024-04-16 - v2.3.0
Please make a backup of the local_oer database tables before upgrading, some rearrangments and extensions are made.

- Introduce new subplugin type oermod for loading OER elements.
- Subplugin oermod_resource for loading mod_resource files.
- Subplugin oermod_folder for loading mod_folder files.
- New unique identifier that also can handle non-moodle content.
- New application profile v2.0.0 for JSON release metadata, see README for more information.
- Application profile can be selected in settings (v1.0.0 for old format).
- New parameters for pull service, see README for more information.
- Roles can now have a required flag.
- A person with author role is now required.
- Badges on the card/list view help to faster identify the state of an element.
- Some visual improvements.
- Mark Subplugin type oeruploader as deprecated, will be removed in a future release.
- Mark Zipper as deprecated, will be removed in a future release.
- Compatibility for Moodle 4.1-4.4 and PHP 7.4 - 8.3



### 2023-11-27
* Add composer.json file

### 2023-11-08 - v2.2.1
* Bugfix for HTML special characters [#23](https://github.com/llttugraz/moodle-local_oer/issues/23)
* Bugfix for wrong shown release timestamps [#24](https://github.com/llttugraz/moodle-local_oer/tree/24-bug-wrongmissing-timestamp-on-filecard-for-release-date)

### 2023-06-28 - v2.2.0
* add CHANGELOG
* add CI support for Moodle 4.3 and PHP 8.2
* remove CI support for Moodle 3.11

### 2023-02-07 - v2.1.4 Feature update

- Detect if a file is used in multiple courses

    - Information is shown on the file in which courses it is used
    - The course editing the metadata of the file first is the editor of the file, editing is blocked in other courses
    (optional - new setting) Overwrite course metadata per file and add metadata of other courses where file is used

- Add course customfields to course metadata

    - When enabled course customfields are added to the course metadata based on the customfields visibility settings
    - Customfields that should not be added can be ignored (global setting)


### 2022-06-22 - v2.1.13 Minor fixes

- Fix: frontend validation of empty person form
- Fix: person_help german language string
- Fix: message content plaintext missing filelist



### 2022-06-21 - v2.1.12 Privacy API and custom required fields

- Fix: Snapshot hash comparison only tests the latest hash of metadata of a file
- Fix: Course metadata synchronisation now correctly deletes entries
- Fix: mform element autocomplete has a strange empty value -> this is
    taken into account when processing form fields in backend
- Update: Resolve todos in privacy api
- Update: Setting nodes have been moved
        - Administrators now see all oer nodes in local plugins section
        - Managers see them in root in oer section
- Feature: New setting for required metadata fields. The requirements for required fields in the OEAA project have changed. So the system is extended to select which fields are required. Classification subplugins are also available to select from.
- Feature: Message API is used to inform OER cleared teachers if some files does not met the requirements, when the requirements setting has been changed.


### 2022-04-13 - v2.1.10

- Fix issue with storing moodle autocomplete field qf.. string when no selection has made, stored value now is null in this case
- Fix some php warnings in fileinfo_form class
- Add more unit tests


### 2022-04-04 - v2.1.9

First release of the Open Educational Resources plugin.

- GUI to edit metadata of files in courses
- GUI to manage access to OER metadata editor
- Task to make snapshots of file metadata for release
- Public access to released files and metadata in JSON format (when enabled)


