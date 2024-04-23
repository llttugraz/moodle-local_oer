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
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

use local_oer\helper\license;
use local_oer\userlist\userlist;

/**
 * Class userlist_test
 *
 * @coversDefaultClass \local_oer\userlist\userlist
 */
final class userlist_test extends \advanced_testcase {
    /**
     * Test if a user is allowed to use oer or not.
     *
     * @return void
     * @throws \dml_exception
     * @covers \local_oer\userlist\userlist::user_is_allowed
     */
    public function test_user_is_allowed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB, $USER;

        // Ensure the config is set to allowed list.
        set_config('allowedlist', 1, 'local_oer');

        $users = [];
        for ($i = 1; $i < 10; $i++) {
            $users[] = $this->getDataGenerator()->create_user();
        }

        $select = rand(0, count($users) - 1);
        $select2 = $select == array_key_first($users) ? array_key_last($users) : array_key_first($users);

        $user = $users[$select];
        $user2 = $users[$select2];
        $this->assertFalse(userlist::user_is_allowed($user->id), 'The list is empty');
        $this->assertFalse(userlist::user_is_allowed($user2->id), 'The list is empty');
        $entry = new \stdClass();
        $entry->userid = $user->id;
        $entry->type = userlist::TYPE_A;
        $entry->timecreated = time();
        $entry->usermodified = $USER->id;
        $entry->timemodified = time();
        $entry->id = $DB->insert_record('local_oer_userlist', $entry);
        $this->assertTrue(userlist::user_is_allowed($user->id));
        $this->assertFalse(userlist::user_is_allowed($user2->id));

        // Now change the config to disallowed list.
        set_config('allowedlist', 0, 'local_oer');

        $this->assertTrue(userlist::user_is_allowed($user->id));
        $this->assertTrue(userlist::user_is_allowed($user2->id));
        $entry->type = userlist::TYPE_D;
        $DB->update_record('local_oer_userlist', $entry);
        $this->assertFalse(userlist::user_is_allowed($user->id));
        $this->assertTrue(userlist::user_is_allowed($user2->id));
    }
}
