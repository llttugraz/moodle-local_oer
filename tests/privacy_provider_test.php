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
use local_oer\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use core_privacy\local\request\approved_userlist;
use local_oer\userlist\userlist;

/**
 * Privacy test for the local_oer plugin
 *
 * @coversDefaultClass \local_oer\privacy\provider
 */
final class privacy_provider_test extends provider_testcase {
    /**
     * Test get metadata function.
     *
     * @return void
     * @covers ::get_metadata
     */
    public function test_get_metadata(): void {
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
     * Add a user to the oer_elements table and return the created entry for unit test asserts.
     *
     * @param int $userid
     * @param int $courseid
     * @return \stdClass
     * @throws \dml_exception
     */
    private function add_to_oer_elements_table(int $userid, $courseid = 11): \stdClass {
        global $DB;
        $entry = new \stdClass();
        $entry->usermodified = $userid;
        $entry->courseid = $courseid;
        $entry->description = "Description";
        $entry->tags = "Tags";
        $entry->classification = "Classification";
        $entry->description = "Description";
        $entry->timecreated = time();
        $entry->timemodified = time();
        $DB->insert_record('local_oer_elements', $entry);
        return $entry;
    }
    /**
     * Check that a user context is returned if there is any user data for this user.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->assertEmpty(provider::get_contexts_for_userid($user->id));

        $this->add_user_to_list($user->id);
        $course = $this->getDataGenerator()->create_course();
        $this->add_to_oer_elements_table($user->id, $course->id);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(2, $contextlist);
    }

    /**
     * Test that user data is exported correctly.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @covers ::export_user_data
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->add_to_oer_elements_table($user->id, $course->id);
        $context = \context_system::instance();

        $entry = $this->add_user_to_list($user->id);
        $this->add_user_to_list($user2->id);

        $writer = writer::with_context($context);
        $this->assertFalse($writer->has_any_data());
        $contextid = ($DB->get_record('context', ['instanceid' => $course->id, 'contextlevel' => CONTEXT_COURSE]))->id;
        $approvedlist = new approved_contextlist($user, 'local_oer', [$context->id, $contextid]);
        provider::export_user_data($approvedlist);
        $writer = \core_privacy\local\request\writer::with_context($context);
        $data = $writer->get_data(['local_oer_userlist']);
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
     * @covers ::change_userid_to_adminid
     * @covers ::update_userid_to_adminid
     */
    public function test_delete_data_for_all_users_in_context(): void {
        $this->resetAfterTest();
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->add_to_oer_elements_table($user1->id, $course->id);
        $this->add_user_to_list($user1->id);
        $this->add_user_to_list($user2->id);

        $userlist = $DB->get_records('local_oer_userlist');
        $this->assertCount(2, $userlist);

        provider::delete_data_for_all_users_in_context(\context_system::instance());

        $userlist = $DB->get_records('local_oer_userlist');
        $this->assertCount(0, $userlist);

        $oerlist = $DB->get_records('local_oer_elements');
        $this->assertCount(1, $oerlist);

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));
        $oerlist = $DB->get_records('local_oer_elements', ['usermodified' => $user1->id]);
        $this->assertCount(0, $oerlist);

        $admindata = get_admin();
        $oerlist = $DB->get_records('local_oer_elements', ['usermodified' => $admindata->id]);
        $this->assertCount(1, $oerlist);

    }

    /**
     * Test delete the entries of a user from userlist.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::delete_data_for_user
     * @covers ::change_userid_to_adminid
     * @covers ::update_userid_to_adminid
     */
    public function test_delete_data_for_user(): void {
        $this->resetAfterTest();
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->add_user_to_list($user1->id);
        $this->add_user_to_list($user2->id);
        $this->add_to_oer_elements_table($user2->id, $course->id);

        $userlist = $DB->get_records('local_oer_userlist');
        $this->assertCount(2, $userlist);

        $approvedlist = new approved_contextlist($user1, 'local_oer', [\context_system::instance()->id]);
        provider::delete_data_for_user($approvedlist);

        $userlistpart = $DB->get_records('local_oer_userlist', ['userid' => $user1->id]);
        $this->assertCount(0, $userlistpart);

        $userlist = $DB->get_records('local_oer_userlist');
        $this->assertCount(1, $userlist);

        $contextid = ($DB->get_record('context', ['instanceid' => $course->id, 'contextlevel' => CONTEXT_COURSE]))->id;
        $approvedlist = new approved_contextlist($user2, 'local_oer', [\context_system::instance()->id, $contextid]);
        provider::delete_data_for_user($approvedlist);
        $userlist = $DB->get_records('local_oer_userlist');
        $this->assertCount(0, $userlist);
        $oerlist = $DB->get_records('local_oer_elements', ['usermodified' => $user2->id]);
        $this->assertCount(0, $oerlist);
        $oerlist = $DB->get_records('local_oer_elements');
        $this->assertCount(1, $oerlist);

    }

    /**
     * Test that all users in userlist are fetched
     *
     * @return void
     * @throws \dml_exception
     * @covers ::get_users_in_context
     */
    public function test_get_users_in_context(): void {
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

        $this->add_to_oer_elements_table($user2->id, $course->id);
        $userlist = new \core_privacy\local\request\userlist($coursecontext, $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist);
    }

    /**
     * Test that data for users in approved userlist is deleted.
     * Works the same as the delete_data_for_all_users_in_context.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::delete_data_for_users
     * @covers ::delete_user_data
     * @covers ::change_userid_to_adminid
     * @covers ::update_userid_to_adminid
     */
    public function test_delete_data_for_users(): void {
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
