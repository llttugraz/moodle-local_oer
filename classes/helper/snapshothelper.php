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
 * Single item from Database
 *
 * @package    local_oer
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2017 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\helper;

use local_oer\snapshot;
use local_oer\logger;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/user/lib.php");

/**
 * Class snapshothelper
 */
class snapshothelper {
    /**
     * Creates the snapshots of all activated courses.
     * Triggered from task and per button in history view (only admins).
     *
     * @return void
     * @throws \dml_exception
     */
    public static function create_snapshots_of_all_active_courses(): void {
        global $DB;
        $courses = activecourse::get_list_of_courses();
        $before = $DB->count_records('local_oer_snapshot');
        foreach ($courses as $course) {
            $snapshot = new snapshot($course->courseid);
            $snapshot->create_snapshot_of_course_files();
        }
        $after = $DB->count_records('local_oer_snapshot');
        logger::add(0, logger::LOGSUCCESS,
                'Created snapshots of ' . count($courses) . ' course(s) and ' . ($after - $before) . ' file(s).');
    }

    /**
     * Return the highest timecreated timestamp of all snapshots.
     * This function is intended to be used by uploader subplugins
     * to determine if something new to upload has been added.
     *
     * @return int
     * @throws \dml_exception
     */
    public static function get_latest_snapshot_timestamp(): int {
        global $DB;
        $sql = 'SELECT MAX(timecreated) FROM {local_oer_snapshot}';
        $latest = $DB->get_record_sql($sql);
        $latest = (array) $latest;
        $timestamp = reset($latest);
        return $timestamp ?? 0;
    }
}
