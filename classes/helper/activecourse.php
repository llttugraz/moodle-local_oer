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
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\helper;

/**
 * Class activecourse
 */
class activecourse {
    /**
     * Returns a list of courseids.
     * The list is either loaded from released files (snapshot table) or
     * from files that have been edited in courses (files table)
     * It is not necessary to iterate over all courses of the system.
     * Only courses with stored metadata are taken into account for
     * many workflows of the oer plugin.
     * Ignore entries when a moodle course is missing, should have been
     * cleaned up elsewhere.
     *
     * @param bool $snapshot True when the list should be of courses that have released files.
     * @return array
     * @throws \dml_exception
     */
    public static function get_list_of_courses($snapshot = false): array {
        global $DB;
        $table = $snapshot ? '{local_oer_snapshot}' : '{local_oer_elements}';
        $sql = "SELECT DISTINCT(courseid) FROM $table t " .
                "JOIN {course} c ON t.courseid = c.id " .
                "ORDER BY courseid ASC";
        return $DB->get_records_sql($sql);
    }
}
