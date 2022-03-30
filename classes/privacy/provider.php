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
use core_privacy\local\request\userlist;

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
     * TODO
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // TODO: Implement delete_data_for_all_users_in_context() method.
    }

    /**
     * TODO
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // TODO: Implement delete_data_for_user() method.
    }

    /**
     * TODO
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // TODO: Implement delete_data_for_users() method.
    }

    /**
     * TODO
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // TODO: Implement export_user_data() method.
    }

    /**
     * TODO
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // TODO: Implement get_contexts_for_userid() method.
        $contextlist = new contextlist();
        return $contextlist;
    }

    /**
     * TODO
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        // TODO: Implement get_users_in_context() method.
    }
}
