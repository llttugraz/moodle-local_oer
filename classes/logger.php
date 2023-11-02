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
 * @copyright  2019 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

/**
 * Class logger
 */
class logger {
    /**
     * Database table name for logs
     */
    const LOGTABLE = 'local_oer_log';

    /**
     * Error
     */
    const LOGERROR = 'ERROR';

    /**
     * Success
     */
    const LOGSUCCESS = 'SUCCESS';

    /**
     * Add log entry
     *
     * @param int $courseid Moodle courseid
     * @param string $type Type of message (error / success)
     * @param string $message The message to log
     * @param string $component Which component called the logger, frankenstyle of plugin or subplugin
     * @return void
     * @throws \dml_exception
     */
    public static function add(int $courseid, string $type = self::LOGSUCCESS, string $message = '',
            string $component = 'local_oer') {
        global $DB, $USER;
        $msg = new \stdClass();
        $msg->courseid = $courseid;
        $msg->type = $type;
        $msg->message = $message;
        $msg->component = $component;
        $msg->timecreated = time();
        $msg->timemodified = time();
        $msg->usermodified = $USER->id;

        $DB->insert_record(self::LOGTABLE, $msg);
    }

    /**
     * Get all logs
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_logs() {
        global $DB;

        return $DB->get_records(self::LOGTABLE, null, 'timecreated DESC');
    }
}
