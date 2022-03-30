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

namespace local_oer\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_oer\helper\snapshothelper;
use local_oer\time\time_settings;

require_once($CFG->libdir . '/clilib.php');

/**
 * Class upload_task
 */
class create_snapshot_task extends scheduled_task {
    /**
     * Get name
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('snapshottask', 'local_oer');
    }

    /**
     * Execute task
     *
     * @throws \dml_exception
     */
    public function execute() {
        $updatetime = get_config('local_oer', time_settings::RELEASETIME);

        if ($updatetime > time()) {
            return;
        }

        snapshothelper::create_snapshots_of_all_active_courses();
        time_settings::set_next_upload_window();
    }
}
