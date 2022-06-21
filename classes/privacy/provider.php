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
class provider implements
        // This tool stores user data.
        \core_privacy\local\metadata\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider,

        // This tool may provide access to and deletion of user data.
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
                        'userid'      => 'privacy:metadata:local_oer_userlist:userid',
                        'type'        => 'privacy:metadata:local_oer_userlist:type',
                        'timecreated' => 'privacy:metadata:local_oer_userlist:timecreated',
                ],
                'privacy:metadata:local_oer_userlist'
        );

        return $collection;
    }

    /**
     * Delete all users from userlist.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        global $DB;
        $users = $DB->get_records('local_oer_userlist');
        foreach ($users as $user) {
            static::delete_user_data($user->userid);
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
        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }
            static::delete_user_data($userid);
        }
    }

    /**
     * Delete all given users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $users = $userlist->get_userids();
        foreach ($users as $userid) {
            static::delete_user_data($userid);
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

        $sql = "SELECT * FROM {local_oer_userlist} ou WHERE ou.userid = :userid";
        if ($userrecord = $DB->get_record_sql($sql, ['userid' => $contextlist->get_user()->id])) {
            $data = (object) [
                    'userid'      => $userrecord->userid,
                    'type'        => $userrecord->type,
                    'timecreated' => transform::datetime($userrecord->timecreated),
            ];
            writer::with_context(\context_system::instance())->export_data(
                    [
                            get_string('pluginname', 'local_oer')
                    ], $data);
        }
    }

    /**
     * Userlist is always in system context.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();
        if (!$DB->record_exists('local_oer_userlist', ['userid' => $userid])) {
            return $contextlist;
        }
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * All users are stored in system context. So get all users from userlist table.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }

        $sql = "SELECT userid FROM {local_oer_userlist} ORDER BY userid ASC";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * This does the deletion of user data in the userlist table.
     *
     * @param int $userid Moodle user id
     * @return void
     * @throws \dml_exception
     */
    protected static function delete_user_data(int $userid) {
        global $DB;
        $DB->delete_records('local_oer_userlist', ['userid' => $userid]);
    }
}
