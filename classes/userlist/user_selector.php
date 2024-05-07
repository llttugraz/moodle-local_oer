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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/selector/lib.php');

/**
 * Class user_selector
 */
class user_selector extends \user_selector_base {
    /**
     * @var mixed
     */
    protected $type;

    /**
     * Set to true if the selector displays the ...
     * Allowed users on this service ...
     * Set to false if the selector displays the ...
     * Other users (false is the default).
     *
     * @var bool
     */
    protected $displayallowedusers;

    /**
     * Constructor
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        parent::__construct($name, $options);

        $this->displayallowedusers = !empty($options['displayallowedusers']);
        $this->type = $options['type'];
    }

    /**
     * Find allowed or not allowed users of a service (depend on $this->displayallowedusers)
     *
     * @param string $search
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function find_users($search) {
        global $DB;
        // By default wherecondition retrieves all users except the deleted, not ...
        // ... confirmed and guest.
        [$wherecondition, $params] = $this->search_sql($search, 'u');

        $fields = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        if ($this->displayallowedusers) {
            $sql = " FROM {user} u, {local_oer_userlist} oul
                 WHERE $wherecondition
                       AND u.deleted = 0
                       AND oul.userid = u.id
                       AND oul.type = '$this->type'";
        } else {
            $sql = " FROM {user} u WHERE $wherecondition AND u.deleted = 0
                 AND NOT EXISTS (SELECT oul.userid FROM {local_oer_userlist} oul
                                                  WHERE oul.userid = u.id AND oul.type = '$this->type')"; // MDL-0 TODO list type.
        }

        [$sort, $sortparams] = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return [];
        }

        if ($search) {
            $groupname = ($this->displayallowedusers) ?
                    get_string('usersmatching', 'local_oer', $search)
                    : get_string('potusersmatching', 'local_oer', $search);
        } else {
            $groupname = ($this->displayallowedusers) ?
                    get_string('oerusers', 'local_oer')
                    : get_string('potusers', 'local_oer');
        }

        return [$groupname => $availableusers];
    }
}
