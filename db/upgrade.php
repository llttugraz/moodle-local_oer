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
 * @copyright  2019-2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Moodle default upgrade hook
 *
 * @param int $oldversion Moodle version before upgrade
 * @return bool
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_local_oer_upgrade($oldversion) {
    global $CFG, $DB;

    require_once($CFG->libdir . '/db/upgradelib.php'); // Core Upgrade-related functions.

    $dbman = $DB->get_manager();

    if ($oldversion < 2019052202) {

        // Define table local_oer_queue to be created.
        $table = new xmldb_table('local_oer_queue');

        // Adding fields to table local_oer_queue.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeadded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attempt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('coursesize', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_oer_queue.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_oer_queue.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2019052202, 'local', 'oer');
    }

    if ($oldversion < 2019053100) {

        // Define table local_oer_log to be created.
        $table = new xmldb_table('local_oer_log');

        // Adding fields to table local_oer_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timeadded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_oer_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_oer_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_oer_logs to be dropped.
        $table = new xmldb_table('local_oer_logs');

        // Conditionally launch drop table for local_oer_logs.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2019053100, 'local', 'oer');
    }

    if ($oldversion < 2021083000) {
        global $DB;
        $records = $DB->get_records('local_oer_userlist');
        foreach ($records as $record) {
            if ($record->type == 'whitelist') {
                $record->type = 'allow';
                $DB->update_record('local_oer_userlist', $record);
            } else if ($record->type == 'blacklist') {
                $record->type = 'disallow';
                $DB->update_record('local_oer_userlist', $record);
            }
        }
        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2021083000, 'local', 'oer');
    }

    if ($oldversion < 2021121400) {

        // Define table local_oer_courseinfo to be created.
        $table = new xmldb_table('local_oer_courseinfo');

        // Adding fields to table local_oer_courseinfo.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('coursecode', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ignored', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('external_courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('external_sourceid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('coursename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('coursename_edited', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('structure', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('structure_edited', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('description_edited', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('objectives', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('objectives_edited', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('organisation', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('organisation_edited', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('language', XMLDB_TYPE_CHAR, '2', null, null, null, null);
        $table->add_field('language_edited', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lecturer', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('lecturer_edited', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('subplugin', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'base');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_oer_courseinfo.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('courseidcoursecode', XMLDB_KEY_UNIQUE, ['courseid', 'coursecode']);

        // Conditionally launch create table for local_oer_courseinfo.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2021121400, 'local', 'oer');
    }

    if ($oldversion < 2022011800) {

        // Define table local_oer_files to be created.
        $table = new xmldb_table('local_oer_files');

        // Adding fields to table local_oer_files.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('context', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('license', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'unknown');
        $table->add_field('role', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'Author');
        $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('language', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, 'de');
        $table->add_field('resourcetype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('classification', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('state', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('preference', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timeuploaded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_oer_files.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('unique_entry', XMLDB_KEY_UNIQUE, ['courseid', 'contenthash']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('contenthash', XMLDB_KEY_FOREIGN, ['contenthash'], 'files', ['contenthash']);
        $table->add_key('license', XMLDB_KEY_FOREIGN, ['license'], 'license', ['shortname']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for local_oer_files.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022011800, 'local', 'oer');
    }

    if ($oldversion < 2022011900) {

        // Define field persons to be added to local_oer_files.
        $table = new xmldb_table('local_oer_files');
        $field = new xmldb_field('persons', XMLDB_TYPE_TEXT, null, null, null, null, null, 'role');

        // Conditionally launch add field persons.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022011900, 'local', 'oer');
    }

    if ($oldversion < 2022012500) {

        // Define table local_oer_preference to be created.
        $table = new xmldb_table('local_oer_preference');

        // Adding fields to table local_oer_preference.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('context', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('license', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('role', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('persons', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('language', XMLDB_TYPE_CHAR, '2', null, null, null, null);
        $table->add_field('resourcetype', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('classification', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('state', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_oer_preference.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'course', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for local_oer_preference.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022012500, 'local', 'oer');
    }

    if ($oldversion < 2022020100) {
        global $DB, $USER;
        $records = $DB->get_records('local_oer_log');

        // Define table local_oer_log to be dropped.
        $table = new xmldb_table('local_oer_log');

        // Conditionally launch drop table for local_oer_log.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table local_oer_log to be created.
        $table = new xmldb_table('local_oer_log');

        // Adding fields to table local_oer_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('component', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'local_oer');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_oer_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Conditionally launch create table for local_oer_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        foreach ($records as $record) {
            unset($record->id);
            $record->courseid = $record->course;
            $record->timemodified = $record->timeadded;
            $record->timecreated = $record->timeadded;
            unset($record->timeadded);
            $record->usermodified = $USER->id;
            $record->component = 'local_oer';
            $DB->insert_record('local_oer_log', $record);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022020100, 'local', 'oer');
    }

    if ($oldversion < 2022020200) {
        global $DB, $USER;
        $items = $DB->get_records('local_oer_items');
        $resourcetypes = \local_oer\forms\fileinfo_form::lom_resource_types(false);
        foreach ($items as $record) {
            unset($record->id);
            // Cannot access the file through get_fast_mod_info.
            // This approach could get the wrong filename, if the same file is used more than once in system.
            $files = $DB->get_records('files', ['contenthash' => $record->contenthash]);
            if ($files) {
                $record->title = reset($files)->filename;
                $record->persons = reset($files)->author;
            } else {
                $record->title = 'File not found';
                $record->persons = '';
            }
            // Only Higher Education used in previous version.
            $record->context = 1;
            $record->description = '';
            $record->role = ucfirst($record->role);
            $found = false;
            foreach ($resourcetypes as $key => $value) {
                if ($value == $record->resourcetype) {
                    $found = true;
                    $record->resourcetype = $key;
                }
            }
            if (!$found) {
                $record->resourcetype = 0;
            }
            if (!empty($record->oefos)) {
                $oefos = explode(',', $record->oefos);
                $classification = [
                        'oefos' => $oefos,
                ];
                $record->classification = json_encode($classification);
            } else {
                $record->classification = null;
            }
            unset($record->oefos);
            $record->state = $record->ignore_file == 1 ? 2 : $record->upload;
            unset($record->upload);
            unset($record->uploaded);
            unset($record->ignore_file);
            $record->timeuploaded = $record->uploaded_at;
            unset($record->uploaded_at);
            $record->timecreated = $record->created_at;
            unset($record->created_at);
            $record->timemodified = $record->modified_at;
            unset($record->modified_at);
            $record->usermodified = $record->modified_by;
            unset($record->modified_by);
            $DB->insert_record('local_oer_files', $record);
        }
        $prefs = $DB->get_records('local_oer_user_pref');
        foreach ($prefs as $record) {
            unset($record->id);
            $record->context = 1;
            $record->role = $record->role == 'nopref' ? null : ucfirst($record->role);
            $record->language = $record->language == 'nopref' ? null : $record->language;
            $record->persons = $record->author;
            unset($record->author);
            foreach ($resourcetypes as $key => $value) {
                if ($value == $record->resourcetype) {
                    $found = true;
                    $record->resourcetype = $key;
                }
            }
            if (!$found) {
                $record->resourcetype = null;
            }
            if (!empty($record->oefos)) {
                $oefos = explode(',', $record->oefos);
                $classification = [
                        'oefos' => $oefos,
                ];
                $record->classification = json_encode($classification);
            } else {
                $record->classification = null;
            }
            $state = $record->radio == 'ignore' ? 2 : 0;
            $state = $record->radio == 'upload' ? 1 : $state;
            $state = $record->radio == 'nopref' ? null : $state;
            $record->state = $state;
            unset($record->radio);
            $record->timecreated = $record->modified_at;
            $record->timemodified = $record->modified_at;
            unset($record->modified_at);
            $record->usermodified = $record->modified_by;
            unset($record->modified_by);

            $DB->insert_record('local_oer_preference', $record);

            // Changing the default of field timecreated on table local_oer_userlist to 0.
            $table = new xmldb_table('local_oer_userlist');
            $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'type');
            // Launch change of default for field timecreated.
            $dbman->change_field_default($table, $field);

            // Changing nullability of field timecreated on table local_oer_userlist to not null.
            $table = new xmldb_table('local_oer_userlist');
            $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'type');
            // Launch change of nullability for field timecreated.
            $dbman->change_field_notnull($table, $field);

            // Define field usermodified to be added to local_oer_userlist.
            $table = new xmldb_table('local_oer_userlist');
            $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');
            // Conditionally launch add field usermodified.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            // Define field timemodified to be added to local_oer_userlist.
            $table = new xmldb_table('local_oer_userlist');
            $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'usermodified');
            // Conditionally launch add field timemodified.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            // MDL-0 TODO Future update: delete local_oer_items table, delete local_oer_user_pref table.
            // delete local_oer_queue table (queue moved to subplugin).
        }
        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022020200, 'local', 'oer');
    }

    if ($oldversion < 2022020301) {
        // Define table local_oer_activecourses to be created.
        $table = new xmldb_table('local_oer_activecourses');

        // Adding fields to table local_oer_activecourses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_oer_activecourses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_UNIQUE, ['courseid']);

        // Conditionally launch create table for local_oer_activecourses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022020301, 'local', 'oer');
    }

    if ($oldversion < 2022020800) {
        // Changing type of field lecturer on table local_oer_courseinfo to text.
        $table = new xmldb_table('local_oer_courseinfo');
        $field = new xmldb_field('lecturer', XMLDB_TYPE_TEXT, null, null, null, null, null, 'language_edited');

        // Launch change of type for field lecturer.
        $dbman->change_field_type($table, $field);

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022020800, 'local', 'oer');
    }

    if ($oldversion < 2022020801) {
        // Add courses that already have file metadata to active courses.
        $files = $DB->get_records('local_oer_files');
        foreach ($files as $file) {
            $active = new stdClass();
            $active->courseid = $file->courseid;
            $active->usermodified = 2;
            $active->timecreated = time();
            $active->timemodified = time();
            if (!$DB->record_exists('local_oer_activecourses', ['courseid' => $active->courseid])) {
                $DB->insert_record('local_oer_activecourses', $active);
            }
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022020801, 'local', 'oer');
    }

    if ($oldversion < 2022030700) {

        // Define table local_oer_snapshot to be created.
        $table = new xmldb_table('local_oer_snapshot');

        // Adding fields to table local_oer_snapshot.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('context', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('license', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('role', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('persons', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('language', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('resourcetype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('classification', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_oer_snapshot.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for local_oer_snapshot.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022030700, 'local', 'oer');
    }

    if ($oldversion < 2022030701) {

        // Define field coursemetadata, additionaldata and releasehash to be added to local_oer_snapshot.
        $table = new xmldb_table('local_oer_snapshot');
        $field1 = new xmldb_field('coursemetadata', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'classification');
        $field2 = new xmldb_field('additionaldata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'coursemetadata');
        $field3 = new xmldb_field('releasehash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, 'additionaldata');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022030701, 'local', 'oer');
    }

    if ($oldversion < 2022030800) {
        // Define field timeuploaded to be dropped from local_oer_files.
        $table = new xmldb_table('local_oer_files');
        $field = new xmldb_field('timeuploaded');

        // Conditionally launch drop field timeuploaded.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define table local_oer_items to be dropped.
        $table = new xmldb_table('local_oer_items');

        // Conditionally launch drop table for local_oer_items.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table local_oer_user_pref to be dropped.
        $table = new xmldb_table('local_oer_user_pref');

        // Conditionally launch drop table for local_oer_user_pref.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table local_oer_queue to be dropped.
        $table = new xmldb_table('local_oer_queue');

        // Conditionally launch drop table for local_oer_queue.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022030800, 'local', 'oer');
    }

    if ($oldversion < 2022030900) {

        // Define table local_oer_activecourses to be dropped.
        $table = new xmldb_table('local_oer_activecourses');

        // Conditionally launch drop table for local_oer_activecourses.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022030900, 'local', 'oer');
    }

    if ($oldversion < 2022031001) {

        // Define field preference to be dropped from local_oer_files.
        $table = new xmldb_table('local_oer_files');
        $field = new xmldb_field('preference');

        // Conditionally launch drop field preference.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022031001, 'local', 'oer');
    }

    if ($oldversion < 2022031600) {
        global $DB;
        $records = $DB->get_records('local_oer_files');
        foreach ($records as $record) {
            if (empty($record->persons)) {
                continue;
            }
            $record->persons = xmldb_local_oer_prepare_persons($record->role, $record->persons);
            $DB->update_record('local_oer_files', $record);
        }
        $records = $DB->get_records('local_oer_preference');
        foreach ($records as $record) {
            if (empty($record->persons)) {
                continue;
            }
            $record->persons = xmldb_local_oer_prepare_persons($record->role, $record->persons);
            $DB->update_record('local_oer_preference', $record);
        }
        $records = $DB->get_records('local_oer_snapshot');
        foreach ($records as $record) {
            if (empty($record->persons)) {
                continue;
            }
            $record->persons = xmldb_local_oer_prepare_persons($record->role, $record->persons);
            $DB->update_record('local_oer_snapshot', $record);
        }

        // Define field preference to be dropped from local_oer_files.
        $table = new xmldb_table('local_oer_files');
        $field = new xmldb_field('role');

        // Conditionally launch drop field preference.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field preference to be dropped from local_oer_files.
        $table = new xmldb_table('local_oer_preference');
        $field = new xmldb_field('role');

        // Conditionally launch drop field preference.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field preference to be dropped from local_oer_files.
        $table = new xmldb_table('local_oer_snapshot');
        $field = new xmldb_field('role');

        // Conditionally launch drop field preference.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022031600, 'local', 'oer');
    }

    if ($oldversion < 2022102000) {

        // Define field customfields to be added to local_oer_courseinfo.
        $table = new xmldb_table('local_oer_courseinfo');
        $field = new xmldb_field('customfields', XMLDB_TYPE_TEXT, null, null, null, null, null, 'lecturer_edited');

        // Conditionally launch add field customfields.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022102000, 'local', 'oer');
    }

    if ($oldversion < 2022111700) {

        // Define table local_oer_coursetofile to be created.
        $table = new xmldb_table('local_oer_coursetofile');

        // Adding fields to table local_oer_coursetofile.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('coursecode', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('state', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_oer_coursetofile.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('foreigncourse', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('uniquefields', XMLDB_KEY_UNIQUE, ['contenthash', 'courseid', 'coursecode']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for local_oer_coursetofile.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2022111700, 'local', 'oer');
    }

    if ($oldversion < 2023111700) {

        // Define table local_oer_elements to be created.
        $table = new xmldb_table('local_oer_elements');

        // Adding fields to table local_oer_elements.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('identifier', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('context', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('license', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'unknown');
        $table->add_field('persons', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('language', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, 'en');
        $table->add_field('resourcetype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('classification', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('releasestate', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_oer_elements.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('license', XMLDB_KEY_FOREIGN, ['license'], 'license', ['shortname']);

        // Adding indexes to table local_oer_elements.
        $table->add_index('identifier', XMLDB_INDEX_UNIQUE, ['identifier']);

        // Conditionally launch create table for local_oer_elements.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $records = $DB->get_records('local_oer_files');
        foreach ($records as $record) {
            unset($record->id);
            $identifier = \local_oer\identifier::compose(
                    'moodle', $CFG->wwwroot,
                    'file', 'contenthash', $record->contenthash
            );

            $record->identifier = $identifier;
            unset($record->contenthash);
            $record->type = \local_oer\modules\element::OERTYPE_MOODLEFILE;
            $record->releasestate = $record->state;
            unset($record->state);
            $DB->insert_record('local_oer_elements', $record);
        }

        // Define table local_oer_files to be dropped.
        $table = new xmldb_table('local_oer_files');

        // Conditionally launch drop table for local_oer_files.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2023111700, 'local', 'oer');
    }

    if ($oldversion < 2023111701) {

        $records = $DB->get_records('local_oer_snapshot');

        // Rename field contenthash on table local_oer_snapshot to identifier.
        $table = new xmldb_table('local_oer_snapshot');
        $field = new xmldb_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'courseid');

        // Launch rename field contenthash.
        $dbman->rename_field($table, $field, 'identifier');

        // Changing precision of field identifier on table local_oer_snapshot to (255).
        $table = new xmldb_table('local_oer_snapshot');
        $field = new xmldb_field('identifier', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'courseid');

        // Launch change of precision for field identifier.
        $dbman->change_field_precision($table, $field);

        foreach ($records as $record) {
            $identifier = \local_oer\identifier::compose(
                    'moodle', $CFG->wwwroot,
                    'file', 'contenthash', $record->contenthash
            );

            $record->identifier = $identifier;
            unset($record->contenthash);
            $DB->update_record('local_oer_snapshot', $record);
        }

        // Define index courseid (not unique) to be added to local_oer_snapshot.
        $table = new xmldb_table('local_oer_snapshot');
        $index = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);

        // Conditionally launch add index courseid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index identifier (not unique) to be added to local_oer_snapshot.
        $table = new xmldb_table('local_oer_snapshot');
        $index = new xmldb_index('identifier', XMLDB_INDEX_NOTUNIQUE, ['identifier']);

        // Conditionally launch add index identifier.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2023111701, 'local', 'oer');
    }

    if ($oldversion < 2024022200) {

        // Define field releasenumber to be added to local_oer_snapshot.
        $table = new xmldb_table('local_oer_snapshot');
        $field = new xmldb_field('releasenumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'releasehash');

        // Conditionally launch add field releasenumber.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update all existing snapshots to get the releasenumber.
        $records = $DB->get_records('local_oer_snapshot', null, 'timecreated ASC');
        $release = 1;
        $lastdaystart = 0;
        $transaction = $DB->start_delegated_transaction();
        foreach ($records as $record) {
            if ($lastdaystart == 0) {
                // Init first release.
                $lastdaystart = strtotime('midnight', $record->timecreated);
                $lastdayend = strtotime('tomorrow', $record->timecreated);
            }
            if ($record->timecreated >= $lastdaystart && $record->timecreated < $lastdayend) {
                $record->releasenumber = $release;
                $DB->update_record('local_oer_snapshot', $record);
            } else if ($record->timecreated >= $lastdayend) {
                // Next release number.
                $lastdaystart = strtotime('midnight', $record->timecreated);
                $lastdayend = strtotime('tomorrow', $record->timecreated);
                $record->releasenumber = ++$release;
                $DB->update_record('local_oer_snapshot', $record);
            } else {
                // How can it be smaller? The values were sorted by timecreated.
                echo html_writer::div($record->identifier .
                        ': "releasenumber" could not be updated, has to be changed manually',
                        'adminwarning');
            }
        }
        $transaction->allow_commit();

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2024022200, 'local', 'oer');
    }

    if ($oldversion < 2024022601) {

        // Define field type to be added to local_oer_snapshot.
        $table = new xmldb_table('local_oer_snapshot');
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'releasenumber');

        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field typedata to be added to local_oer_snapshot.
        $table = new xmldb_table('local_oer_snapshot');
        $field = new xmldb_field('typedata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'type');

        // Conditionally launch add field typedata.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // At the moment, there are only OERMOD_MOODLEFILE elements stored.
        // To update the typedata field, all released elements have to be loaded from the courses and the data has to be added.
        $courses = \local_oer\helper\activecourse::get_list_of_courses(true);
        foreach ($courses as $course) {
            $releasedfiles = $DB->get_records('local_oer_snapshot', ['courseid' => $course->courseid]);
            // Function get_fast_modinfo() not allowed during upgrade. So files have to be loaded manually.
            foreach ($releasedfiles as $releasedfile) {
                $decomposed = \local_oer\identifier::decompose($releasedfile->identifier);
                if ($decomposed->valuetype != 'contenthash') {
                    echo html_writer::div($releasedfile->identifier .
                            ': Element is not a Moodle stored file. How is that possible during this update?',
                            'adminwarning');
                    continue;
                }
                // Contenthash always leads to the same file.
                $files = $DB->get_records('files', ['contenthash' => $decomposed->value]);
                foreach ($files as $file) {
                    if (is_null($file->mimetype)) {
                        continue;
                    }
                    $fs = get_file_storage();
                    $storedfile = $fs->get_file($file->contextid, $file->component, $file->filearea, $file->itemid, $file->filepath,
                            $file->filename);
                    if (!$storedfile) {
                        echo html_writer::div($releasedfile->identifier .
                                ': File does not exist anymore. Entry has to be cleaned up manually',
                                'adminwarning');
                        continue;
                    }
                    $url = \moodle_url::make_pluginfile_url($storedfile->get_contextid(),
                            $storedfile->get_component(),
                            $storedfile->get_filearea(),
                            $storedfile->get_itemid(),
                            $storedfile->get_filepath(),
                            $storedfile->get_filename());
                    $releasedfile->typedata = json_encode([
                            'mimetype' => $storedfile->get_mimetype(),
                            'filesize' => $storedfile->get_filesize(),
                            'source' => $url->out(),
                    ]);
                    $DB->update_record('local_oer_snapshot', $releasedfile);
                    break;
                }
            }
        }

        // Oer savepoint reached.
        upgrade_plugin_savepoint(true, 2024022601, 'local', 'oer');
    }

    return true;
}

/**
 * To upgrade the persons field it has to be rearranged in several tables.
 *
 * @param string|null $role Role from old db field role
 * @param string $persons CSV string of names
 * @return string
 */
function xmldb_local_oer_prepare_persons(?string $role, string $persons): string {
    $role = $role ?? 'Author';
    $result = new stdClass();
    $result->persons = [];
    $names = explode(',', $persons);
    foreach ($names as $name) {
        $person = new stdClass();
        $person->role = $role;
        $nameparts = explode(' ', $name);
        $person->lastname = end($nameparts);
        unset($nameparts[count($nameparts) - 1]);
        $person->firstname = implode(' ', $nameparts);
        $result->persons[] = $person;
    }
    return json_encode($result);
}
