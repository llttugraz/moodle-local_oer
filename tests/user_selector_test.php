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
use local_oer\userlist\user_selector;
use local_oer\userlist\userlist;

/**
 * Class user_selector_test
 *
 * @coversDefaultClass \local_oer\userlist\user_selector
 */
final class user_selector_test extends \advanced_testcase {
    /**
     * Test if users are selected correctly on searching them.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @covers \local_oer\userlist\user_selector::__construct
     * @covers \local_oer\userlist\user_selector::find_users
     */
    public function test_find_users(): void {
        $this->resetAfterTest();

        set_config('maxusersperpage', 50); // Moodle default is 100, reduced it for the test.
        $this->setAdminUser();
        global $DB, $USER;
        $entry = new \stdClass();
        $entry->timecreated = time();
        $entry->usermodified = $USER->id;
        $entry->timemodified = time();

        // All 'Fay' and 'Claude' users will be added to the allowed list. All 'Simon' users not.
        // All 'Claude' and 'Simon' users will be added to the disallowed list.
        $users = [
                ['Fay', 'Daway', 'faydaway@example.com', true, false],
                ['Claude', 'Strophobia', 'claudestrophobia@example.com', true, true],
                ['Simon', 'Sais', 'simonsais@example.com', false, true],
        ];
        $list = [];
        foreach ($users as $user) {
            for ($i = 1; $i <= 100; $i++) {
                $list[] = [
                        $user[0] . $i, $user[1] . $i, $i . $user[2], $user[3], $user[4],
                ];
            }
        }
        // Randomize the list, so that the users will be more distributed in database.
        shuffle($list);
        foreach ($list as $user) {
            $created = $this->getDataGenerator()->create_user(
                    [
                            'firstname' => $user[0],
                            'lastname' => $user[1],
                            'email' => $user[2],
                    ]
            );
            $entry->userid = $created->id;
            if ($user[3]) {
                $entry->type = userlist::TYPE_A;
                $DB->insert_record('local_oer_userlist', $entry);
            }
            if ($user[4]) {
                $entry->type = userlist::TYPE_D;
                $DB->insert_record('local_oer_userlist', $entry);
            }
        }

        // Now there are about 300 users. So lets try to find a bunch of them.
        // Tests for the case 'allowed list'.
        set_config('allowedlist', 1, 'local_oer');
        $settings = [
                'displayallowedusers' => true, // This will search users already on the allowed list.
                'type' => userlist::TYPE_A,
        ];
        $selector = new user_selector('unit test user selector', $settings);
        // First call the function with an empty search string.
        // This will result in too many users string, but the empty case.
        $result = $selector->find_users('');
        $this->assertEquals(
                [
                        get_string('toomanyuserstoshow', '', 200) => [],
                        get_string('pleaseusesearch') => [],
                ],
                $result, 'The result 200 are the 200 users on the allowed list.');

        // Searchstring 'Claude', expected 100 results.
        $result = $selector->find_users('Claude');
        $this->assertEquals(
                [
                        get_string('toomanyusersmatchsearch', '', ['count' => 100, 'search' => 'Claude']) => [],
                        get_string('pleasesearchmore') => [],
                ],
                $result);
        $result = $selector->find_users('Fay1');
        $this->assertCount(12, $result['Authorised users']);
        $delete = end($result['Authorised users']);
        $deleteuser = $DB->get_record('user', ['id' => $delete->id]);
        delete_user($deleteuser);
        $result = $selector->find_users('Fay1');
        $this->assertCount(11, $result['Authorised users']);

        $result = $selector->find_users('Simon');
        $this->assertEmpty($result);

        // Search for users to add to the allow list, here also 'Simon' can be found.
        set_config('maxusersperpage', 100);
        $settings = [
                'type' => userlist::TYPE_A,
        ];
        $selector = new user_selector('unit test user selector', $settings);
        $result = $selector->find_users('Simon');
        $this->assertCount(100, $result['Authorised users']);

        $result = $selector->find_users('Fay');
        $this->assertEmpty($result, 'No one found, because all of them are already on the list.');

        // Now set the list to 'disallowed'.
        set_config('allowedlist', 0, 'local_oer');
        $settings = [
                'displayallowedusers' => true, // This will search users already on the (dis)allowed list.
                'type' => userlist::TYPE_D,
        ];
        $selector = new user_selector('unit test user selector', $settings);
        $result = $selector->find_users('Simon');
        $this->assertCount(100, $result['Authorised users']);
        $result = $selector->find_users('Fay');
        $this->assertEmpty($result);
        $settings = [
                'type' => userlist::TYPE_D,
        ];
        $selector = new user_selector('unit test user selector', $settings);
        $result = $selector->find_users('Simon');
        $this->assertEmpty($result, 'No one found, because all of them are already on the list.');
        $result = $selector->find_users('Fay');
        $this->assertCount(99, $result['Authorised users'], 'One got deleted.');
    }
}
