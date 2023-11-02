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

namespace local_oer\userlist;

/**
 * Class userlist
 */
class userlist {
    /**
     * Allowance list
     */
    const TYPE_A = 'allow';

    /**
     * Disallowance list
     */
    const TYPE_D = 'disallow';

    /**
     * Test if a user is allowed to use the oer plugin
     *
     * @param int $userid
     * @return bool
     * @throws \dml_exception
     */
    public static function user_is_allowed($userid) {
        global $DB;
        $type = get_config('local_oer', 'allowedlist') == '1' ? self::TYPE_A : self::TYPE_D;
        $exist = $DB->record_exists('local_oer_userlist', ['userid' => $userid, 'type' => $type]);
        return $type == self::TYPE_A ? $exist : !$exist;
    }
}
