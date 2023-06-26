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

namespace local_oer;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\transform;
use \local_oer\privacy\provider;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\tests\provider_testcase;
use \core_privacy\local\request\approved_userlist;
use local_oer\userlist\userlist;

/**
 * Privacy test for the local_oer plugin
 *
 * @coversDefaultClass \local_oer\privacy\provider
 */
class privacy_provider_test extends provider_testcase {
    /**
     * Test get metadata function.
     *
     * @return void
     * @covers ::get_metadata
     */
    public function test_get_metadata() {
        $this->resetAfterTest();
        $collection = new collection('local_oer');
        $collection = provider::get_metadata($collection);
        $this->assertEquals('core_privacy\local\metadata\collection', get_class($collection));
    }

    /**
     * Add a user to the userlist table and return the created entry for unit test asserts.
     *
     * @param int $userid
     * @return \stdClass
     * @throws \dml_exception
     */
    private function add_user_to_list(int $userid): \stdClass {
        global $DB;
        $entry = new \stdClass();
        $entry->userid = $userid;
        $entry->type = userlist::TYPE_A;
        $entry->usermodified = 2;
        $entry->timecreated = time();
        $entry->timemodified = time();
        $DB->insert_record('local_oer_userlist', $entry);
        return $entry;
    }

    /**
     * Check that a user context is returned if there is any user data for this user.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->assertEmpty(provider::get_contexts_for_userid($user->id));

        $this->add_user_to_list($user->id);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);

        $context = \context_system::instance();
        $this->assertEquals($context->id, $contextlist->get_contextids()[0]);
    }

    /**
     * Test that user data is exported correctly.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @covers ::export_user_data
     */
    public function test_export_user_data() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $context = \context_system::instance();

        $entry = $this->add_user_to_list($user->id);
        $this->add_user_to_list($user2->id);

        $writer = writer::with_context($context);
        $this->assertFalse($writer->has_any_data());
        $approvedlist = new approved_contextlist($user, 'local_oer', [$context->id]);
        provider::export_user_data($approvedlist);
        $data = $writer->get_data([get_string('pluginname', 'local_oer')]);
        $this->assertEquals($user->id, $data->userid);
        $this->assertEquals(userlist::TYPE_A, $data->type);
        $this->assertEquals(transform::datetime($entry->timecreated), $data->timecreated);
    }

    /**
     * Test delete all users from userlist because of system context
     *
     * @return void
     * @throws \dml_exception
     * @covers ::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context() {
        $this->resetAfterTest();
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->add_user_to_list($user1->id);
        $this->add_user_to_list($user2->id);

        $userlist = $DB->get_records('local_oer_userlist');
        $this->assertCount(2, $userlist);

        provider::delete_data_for_all_users_in_context(\context_system::instance());

        $userlist = $DB->get_records('local_oer_userlist');
        $this->assertCount(0, $userlist);
    }

    /**
     * Test delete the entries of a user from userlist.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::delete_data_for_user
     */
    public function test_delete_data_for_user() {
        $this->resetAfterTest();
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->add_user_to_list($user1->id);
        $this->add_user_to_list($user2->id);

        $userlist = $DB->get_records('local_oer_userlist');
        $this->assertCount(2, $userlist);

        $approvedlist = new approved_contextlist($user1, 'local_oer', [\context_system::instance()->id]);
        provider::delete_data_for_user($approvedlist);

        $userlistpart = $DB->get_records('local_oer_userlist', ['userid' => $user1->id]);
        $this->assertCount(0, $userlistpart);

        $userlist = $DB->get_records('local_oer_userlist');
        $this->assertCount(1, $userlist);
    }

    /**
     * Test that all users in userlist are fetched
     *
     * @return void
     * @throws \dml_exception
     * @covers ::get_users_in_context
     */
    public function test_get_users_in_context() {
        $this->resetAfterTest();

        $component = 'local_oer';
        $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $userlist = new \core_privacy\local\request\userlist(\context_system::instance(), $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);

        $this->add_user_to_list($user2->id);
        $this->add_user_to_list($user3->id);

        $userlist = new \core_privacy\local\request\userlist(\context_system::instance(), $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist);

        $userlist = new \core_privacy\local\request\userlist($coursecontext, $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);
    }

    /**
     * Test that data for users in approved userlist is deleted.
     * Works the same as the delete_data_for_all_users_in_context.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::delete_data_for_users
     * @covers ::delete_user_data
     */
    public function test_delete_data_for_users() {
        $this->resetAfterTest();

        $component = 'local_oer';
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        global $DB;
        $list = $DB->count_records('local_oer_userlist');
        $this->assertEquals(0, $list);

        $userlist = new \core_privacy\local\request\userlist(\context_system::instance(), $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);

        $this->add_user_to_list($user1->id);
        $this->add_user_to_list($user2->id);
        $list = $DB->count_records('local_oer_userlist');
        $this->assertEquals(2, $list);

        $userlist = new \core_privacy\local\request\userlist(\context_system::instance(), $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist);
        $expected = [$user1->id, $user2->id];
        $actual = $userlist->get_userids();
        $this->assertEquals($expected, $actual);

        $approvedlist = new approved_userlist(\context_system::instance(), $component, $userlist->get_userids());
        provider::delete_data_for_users($approvedlist);

        $userlist = new \core_privacy\local\request\userlist(\context_system::instance(), $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);
        $list = $DB->count_records('local_oer_userlist');
        $this->assertEquals(0, $list);
    }
}
