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
 * @copyright  2017 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Class provider
 */
class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {
    /**
     * Get metadata
     *
     * This plugin does not really store anything of interest for privacy issues.
     * All tables have the Moodle default usermodified field, but the data of the
     * tables itself is mostly metadata of the files. The authors/publishers added
     * to the files do not have a link to a Moodle user. Also when the user
     * is deleted from Moodle, the files are from the course and the releases are
     * also not affected from it.
     * The only table where user data is stored is the userlist table. In this
     * table the allowance to use the OER functionality is stored.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
                'local_oer_userlist',
                [
                        'userid' => 'privacy:metadata:local_oer_userlist:userid',
                        'type' => 'privacy:metadata:local_oer_userlist:type',
                        'timecreated' => 'privacy:metadata:local_oer_userlist:timecreated',
                ],
                'privacy:metadata:local_oer_userlist'
        );

        $collection->add_database_table('local_oer_courseinfo',
                [
                        'courseid' => 'privacy:metadata:courseid',
                        'coursecode' => 'privacy:metadata:coursecode',
                        'deleted' => 'privacy:metadata:deleted',
                        'ignored' => 'privacy:metadata:ignored',
                        'external_courseid' => 'privacy:metadata:external_courseid',
                        'external_sourceid' => 'privacy:metadata:external_sourceid',
                        'coursename' => 'privacy:metadata:coursename',
                        'coursename_edited' => 'privacy:metadata:coursename_edited',
                        'structure' => 'privacy:metadata:structure',
                        'structure_edited' => 'privacy:metadata:structure_edited',
                        'description' => 'privacy:metadata:description',
                        'description_edited' => 'privacy:metadata:description_edited',
                        'objectives' => 'privacy:metadata:objectives',
                        'objectives_edited' => 'privacy:metadata:objectives_edited',
                        'organisation' => 'privacy:metadata:organisation',
                        'organisation_edited' => 'privacy:metadata:organisation_edited',
                        'language' => 'privacy:metadata:language',
                        'language_edited' => 'privacy:metadata:language_edited',
                        'lecturer' => 'privacy:metadata:lecturer',
                        'lecturer_edited' => 'privacy:metadata:lecturer_edited',
                        'customfields' => 'privacy:metadata:customfields',
                        'subplugin' => 'privacy:metadata:subplugin',
                        'usermodified' => 'privacy:metadata:usermodified',
                        'timecreated' => 'privacy:metadata:timecreated',
                        'timemodified' => 'privacy:metadata:timemodified',
                ],
                'privacy:metadata:local_oer_courseinfo'
        );

        $collection->add_database_table('local_oer_coursetofile',
                [
                        'contenthash' => 'privacy:metadata:contenthash',
                        'courseid' => 'privacy:metadata:courseid',
                        'coursecode' => 'privacy:metadata:coursecode',
                        'state' => 'privacy:metadata:state',
                        'usermodified' => 'privacy:metadata:usermodified',
                        'timecreated' => 'privacy:metadata:timecreated',
                        'timemodified' => 'privacy:metadata:timemodified',
                ],
                'privacy:metadata:local_oer_coursetofile'
        );

        $collection->add_database_table('local_oer_elements',
                [
                        'courseid' => 'privacy:metadata:courseid',
                        'identifier' => 'privacy:metadata:identifier',
                        'type' => 'privacy:metadata:type',
                        'title' => 'privacy:metadata:title',
                        'description' => 'privacy:metadata:description',
                        'context' => 'privacy:metadata:context',
                        'license' => 'privacy:metadata:license',
                        'persons' => 'privacy:metadata:persons',
                        'tags' => 'privacy:metadata:tags',
                        'language' => 'privacy:metadata:language',
                        'resourcetype' => 'privacy:metadata:resourcetype',
                        'classification' => 'privacy:metadata:classification',
                        'releasestate' => 'privacy:metadata:releasestate',
                        'usermodified' => 'privacy:metadata:usermodified',
                        'timecreated' => 'privacy:metadata:timecreated',
                        'timemodified' => 'privacy:metadata:timemodified',
                ],
                'privacy:metadata:local_oer_elements'
        );

        $collection->add_database_table('local_oer_log',
                [
                        'courseid' => 'privacy:metadata:courseid',
                        'type' => 'privacy:metadata:type',
                        'message' => 'privacy:metadata:message',
                        'component' => 'privacy:metadata:component',
                        'usermodified' => 'privacy:metadata:usermodified',
                        'timecreated' => 'privacy:metadata:timecreated',
                        'timemodified' => 'privacy:metadata:timemodified',
                ],
                'privacy:metadata:local_oer_log'
        );

        $collection->add_database_table('local_oer_preference',
                [
                        'courseid' => 'privacy:metadata:courseid',
                        'context' => 'privacy:metadata:context',
                        'license' => 'privacy:metadata:license',
                        'persons' => 'privacy:metadata:persons',
                        'tags' => 'privacy:metadata:tags',
                        'language' => 'privacy:metadata:language',
                        'resourcetype' => 'privacy:metadata:resourcetype',
                        'classification' => 'privacy:metadata:classification',
                        'state' => 'privacy:metadata:state',
                        'usermodified' => 'privacy:metadata:usermodified',
                        'timecreated' => 'privacy:metadata:timecreated',
                        'timemodified' => 'privacy:metadata:timemodified',
                ],
                'privacy:metadata:local_oer_preference'
        );

        $collection->add_database_table('local_oer_snapshot',
                [
                        'courseid' => 'privacy:metadata:courseid',
                        'identifier' => 'privacy:metadata:identifier',
                        'title' => 'privacy:metadata:title',
                        'description' => 'privacy:metadata:description',
                        'context' => 'privacy:metadata:context',
                        'license' => 'privacy:metadata:license',
                        'persons' => 'privacy:metadata:persons',
                        'tags' => 'privacy:metadata:tags',
                        'language' => 'privacy:metadata:language',
                        'resourcetype' => 'privacy:metadata:resourcetype',
                        'classification' => 'privacy:metadata:classification',
                        'coursemetadata' => 'privacy:metadata:coursemetadata',
                        'additionaldata' => 'privacy:metadata:additionaldata',
                        'releasehash' => 'privacy:metadata:releasehash',
                        'releasenumber' => 'privacy:metadata:releasenumber',
                        'type' => 'privacy:metadata:type',
                        'typedata' => 'privacy:metadata:typedata',
                        'usermodified' => 'privacy:metadata:usermodified',
                        'timecreated' => 'privacy:metadata:timecreated',
                        'timemodified' => 'privacy:metadata:timemodified',
                ],
                'privacy:metadata:local_oer_snapshot'
        );

        return $collection;
    }

    /**
     * Get table names of tables of the plugin which store personal data.
     *
     *
     */
    public static function get_tablenames_except_userlist() {
        return ['local_oer_courseinfo', 'local_oer_coursetofile', 'local_oer_elements', 'local_oer_log',
                'local_oer_preference', 'local_oer_snapshot'];
    }

    /**
     * Delete all users from userlist.
     *
     * @param \context $context
     * @return void
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $users = $DB->get_records('local_oer_userlist');
            foreach ($users as $user) {
                static::delete_user_data($user->userid);

            }
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            static::change_userid_to_adminid($context->instanceid);
        }
    }

    /**
     * Delete the data of a user
     *
     * @param approved_contextlist $contextlist
     * @return void
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if ($context->contextlevel == CONTEXT_SYSTEM) {
                static::delete_user_data($userid);
            }

            if ($context->contextlevel == CONTEXT_COURSE) {
                static::change_userid_to_adminid($context->instanceid, $userid);
            }

        }
    }

    /**
     * Delete all given users.
     *
     * @param approved_userlist $userlist
     * @return void
     * @throws \dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $users = $userlist->get_userids();
            foreach ($users as $userid) {
                static::delete_user_data($userid);
            }
        }

        if ($context instanceof \context_course) {
            $tablenames = self::get_tablenames_except_userlist();
            [$usersql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $select = "courseid = :courseid AND usermodified {$usersql}";
            $params = ['courseid' => $context->instanceid] + $userparams;

            foreach ($tablenames as $tablename) {
                $records = $DB->get_records_select($tablename, $select, $params);
                if (!empty($records)) {
                    static::update_userid_to_adminid($tablename, $records);
                }
            }
        }

    }

    /**
     * Export the data of the user.
     *
     * @param approved_contextlist $contextlist
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $tablenames = self::get_tablenames_except_userlist();

        // Context_id = 1 <===> Context_level =  SYSTEM_CONTEXT.
        if (in_array(1, $contextlist->get_contextids())) {
            $sql = "SELECT * FROM {local_oer_userlist} ou WHERE ou.userid = :userid";
            if ($userrecord = $DB->get_record_sql($sql, ['userid' => $contextlist->get_user()->id])) {
                $data = (object) [
                        'userid' => $userrecord->userid,
                        'type' => $userrecord->type,
                        'timecreated' => transform::datetime($userrecord->timecreated),
                ];
                writer::with_context(\context_system::instance())->export_data(
                        ['local_oer_userlist'], $data);
            }
        }

        $contexts = array_filter($contextlist->get_contexts(), function($context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                return $context;
            }
        });

        if (empty($contexts)) {
            return;
        }
        $courseids = array_map(function($context) {
            return $context->instanceid;
        }, $contexts);

        foreach ($tablenames as $table) {

            [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $sql = "SELECT s.*, c.id as courseid
                      FROM {" . $table . "} s
                      JOIN {course} c ON s.courseid = c.id
                     WHERE s.usermodified = :usermodified AND c.id $insql
                     ORDER BY c.id ASC";
            $params['usermodified'] = $contextlist->get_user()->id;
            $records = $DB->get_records_sql($sql, $params);

            $statsrecords = [];
            foreach ($records as $record) {
                $context = \context_course::instance($record->courseid);
                if (!isset($statsrecords[$record->courseid])) {
                    $statsrecords[$record->courseid] = new \stdClass();
                    $statsrecords[$record->courseid]->context = $context;
                }
                if (property_exists($record, "timecreated")) {
                    $record->timecreated = transform::datetime($record->timecreated);
                }
                if (property_exists($record, "timemodified")) {
                    $record->timemodified = transform::datetime($record->timemodified);
                }
                $statsrecords[$record->courseid]->entries[] = (array) $record;
            }
            foreach ($statsrecords as $coursestats) {
                \core_privacy\local\request\writer::with_context($coursestats->context)->export_data([$table],
                        (object) $coursestats->entries);
            }

        }
    }

    /**
     *
     *
     * @param int $userid
     * @return contextlist
     * @throws \dml_exception
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();

        $tablenames = self::get_tablenames_except_userlist();
        foreach ($tablenames as $tablename) {
            $params = ['userid' => $userid, 'contextcourse' => CONTEXT_COURSE];
            $sql = "SELECT ctx.id
                FROM {context} ctx
                JOIN {" . $tablename . "} tabname ON tabname.courseid = ctx.instanceid AND tabname.usermodified = :userid
                WHERE ctx.contextlevel = :contextcourse";
            $contextlist->add_from_sql($sql, $params);
        }

        if ($DB->record_exists('local_oer_userlist', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * All users are stored in system context. So get all users from userlist table.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context instanceof \context_system) {
            $sql = "SELECT userid FROM {local_oer_userlist} ORDER BY userid ASC";
            $userlist->add_from_sql('userid', $sql, []);
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            $params = ['courseid' => $context->instanceid];
            $tablenames = self::get_tablenames_except_userlist();
            foreach ($tablenames as $tablename) {
                $sql = "SELECT usermodified FROM {" . $tablename . "} WHERE courseid = :courseid";
                $userlist->add_from_sql('usermodified', $sql, $params);
            }
        }

    }

    /**
     * This does the deletion of user data.
     *
     * @param int $userid Moodle user id
     * @return void
     * @throws \dml_exception
     */
    protected static function delete_user_data(int $userid) {
        global $DB;
        $DB->delete_records('local_oer_userlist', ['userid' => $userid]);
    }

    /**
     * Change userid to adminid.
     *
     * @param int $courseid
     * @param int|null $userid
     * @return void
     */
    protected static function change_userid_to_adminid(int $courseid, ?int $userid = null) {
        global $DB;
        $tablenames = self::get_tablenames_except_userlist();
        $params = (isset($userid)) ? ['courseid' => $courseid, 'usermodified' => $userid] : ['courseid' => $courseid];

        foreach ($tablenames as $tablename) {
            $records = $DB->get_records($tablename, $params);
            if (!empty($records)) {
                static::update_userid_to_adminid($tablename, $records);
            }
        }
    }

    /**
     * Update userid to adminid.
     *
     * @param string $tablename
     * @param array $records
     */
    protected static function update_userid_to_adminid(string $tablename, array $records) {
        global $DB;
        $admindata = get_admin();
        $adminid = $admindata->id;
        foreach ($records as $record) {
            if ($record->usermodified != $adminid) {
                $record->usermodified = $adminid;
                $DB->update_record($tablename, $record);
            }
        }
    }

}
