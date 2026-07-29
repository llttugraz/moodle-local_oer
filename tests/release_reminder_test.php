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
 * Tests release reminder handling.
 *
 * @package    local_oer
 * @copyright  2026 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

use local_oer\userlist\userlist;

/**
 * Class release_reminder_test
 *
 * @coversDefaultClass \local_oer\release_reminder
 */
final class release_reminder_test extends \advanced_testcase {
    /**
     * Set up test defaults.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once(__DIR__ . '/helper/testcourse.php');

        set_config(release_reminder::CONF_ENABLED, 1, 'local_oer');
        set_config(release_reminder::CONF_DAYSBEFORE, 1, 'local_oer');
        set_config(release_reminder::CONF_SUBJECT, 'Release {releasedate}', 'local_oer');
        set_config(
            release_reminder::CONF_BODY,
            'Hello {firstname} {lastname}' . "\n" . '{releasedate}',
            'local_oer'
        );
        set_config(release_reminder::CONF_LASTSENT, 0, 'local_oer');
        set_config(\local_oer\time\time_settings::RELEASETIME, time() + HOURSECS, 'local_oer');
    }

    /**
     * Test that due reminders are sent to all allowed users.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::send_if_due
     */
    public function test_send_if_due_to_allowed_users(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Ada', 'lastname' => 'Lovelace']);
        $otheruser = $this->getDataGenerator()->create_user(['firstname' => 'Grace']);
        $releasedate = userdate(get_config('local_oer', \local_oer\time\time_settings::RELEASETIME));

        set_config('allowedlist', 1, 'local_oer');
        $this->add_userlist_entry($user->id, userlist::TYPE_A);
        $this->add_userlist_entry($otheruser->id, userlist::TYPE_A);

        $sink = $this->redirectEmails();
        $this->assertEquals(2, release_reminder::send_if_due());
        $emails = $sink->get_messages();

        $this->assertCount(2, $emails);
        $emailsbyrecipient = [];
        foreach ($emails as $email) {
            $emailsbyrecipient[$email->to] = $email;
            $this->assertStringContainsString($releasedate, $email->subject);
            $this->assertStringContainsString($releasedate, $email->body);
        }
        $this->assertStringContainsString('Ada Lovelace', $emailsbyrecipient[$user->email]->body);
        $this->assertStringContainsString('Grace', $emailsbyrecipient[$otheruser->email]->body);

        $sink = $this->redirectEmails();
        $this->assertEquals(0, release_reminder::send_if_due());
        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * Test disallowance list mode sends to users with files except blocked users.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::send_if_due
     */
    public function test_send_if_due_with_disallowance_list(): void {
        $courseid = $this->create_release_elements(2);
        $alloweduser = $this->getDataGenerator()->create_user();
        $blockeduser = $this->getDataGenerator()->create_user();

        set_config('allowedlist', 0, 'local_oer');
        $this->add_userlist_entry($blockeduser->id, userlist::TYPE_D);
        $this->set_release_file_users($courseid, [$alloweduser->id, $blockeduser->id]);

        $sink = $this->redirectEmails();
        $this->assertEquals(1, release_reminder::send_if_due());
        $emails = $sink->get_messages();

        $this->assertCount(1, $emails);
        $this->assertSame($alloweduser->email, $emails[0]->to);
    }

    /**
     * Test subject and body are formatted in the user's language.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::send_if_due
     */
    public function test_send_if_due_formats_subject_and_body_with_forced_language(): void {
        if (!in_array('multilang2', array_keys(\core_component::get_plugin_list('filter')))) {
            $this->markTestSkipped('filter_multilang2 is not installed.');
        }

        get_string_manager()->reset_caches();
        $controller = new \tool_langimport\controller();
        $controller->install_languagepacks('de');
        filter_set_global_state('multilang2', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang2', true);

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Bertha',
            'lastname' => 'Benz',
            'lang' => 'de',
        ]);

        set_config('allowedlist', 1, 'local_oer');
        set_config(
            release_reminder::CONF_SUBJECT,
            '{mlang en}Release {releasedate}{mlang}{mlang de}Freigabe {releasedate}{mlang}',
            'local_oer'
        );
        set_config(
            release_reminder::CONF_BODY,
            '{mlang en}Hello {firstname} {lastname}{mlang}{mlang de}Hallo {firstname} {lastname}{mlang}',
            'local_oer'
        );
        $this->add_userlist_entry($user->id, userlist::TYPE_A);

        $sink = $this->redirectEmails();
        $this->assertEquals(1, release_reminder::send_if_due());
        $emails = $sink->get_messages();

        $this->assertCount(1, $emails);
        $this->assertStringContainsString('Freigabe', $emails[0]->subject);
        $this->assertStringNotContainsString('Release', $emails[0]->subject);
        $this->assertStringContainsString('Hallo Bertha Benz', $emails[0]->body);
        $this->assertStringNotContainsString('Hello', $emails[0]->body);
        $this->assertStringNotContainsString('{mlang', $emails[0]->subject);
        $this->assertStringNotContainsString('{mlang', $emails[0]->body);
    }

    /**
     * Test allowed users receive one email each without course differentiation.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::send_if_due
     */
    public function test_send_if_due_sends_one_email_per_allowed_user(): void {
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        set_config('allowedlist', 1, 'local_oer');
        $this->add_userlist_entry($user->id, userlist::TYPE_A);
        $this->add_userlist_entry($otheruser->id, userlist::TYPE_A);

        $sink = $this->redirectEmails();
        $this->assertEquals(2, release_reminder::send_if_due());
        $emails = $sink->get_messages();

        $this->assertCount(2, $emails);
        $emailsbyrecipient = [];
        foreach ($emails as $email) {
            $emailsbyrecipient[$email->to] = $email;
        }

        $this->assertArrayHasKey($user->email, $emailsbyrecipient);
        $this->assertArrayHasKey($otheruser->email, $emailsbyrecipient);
    }

    /**
     * Test allow-list reminders do not require course files.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::send_if_due
     */
    public function test_send_if_due_does_not_require_course_files_for_allowed_users(): void {
        $user = $this->getDataGenerator()->create_user();

        set_config('allowedlist', 1, 'local_oer');
        $this->add_userlist_entry($user->id, userlist::TYPE_A);

        $sink = $this->redirectEmails();
        $this->assertEquals(1, release_reminder::send_if_due());
        $emails = $sink->get_messages();

        $this->assertCount(1, $emails);
        $this->assertSame($user->email, $emails[0]->to);
    }

    /**
     * Test reminder waits until configured send time.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::send_if_due
     */
    public function test_send_if_due_waits_until_send_time(): void {
        $user = $this->getDataGenerator()->create_user();

        set_config('allowedlist', 1, 'local_oer');
        set_config(release_reminder::CONF_DAYSBEFORE, 7, 'local_oer');
        set_config(\local_oer\time\time_settings::RELEASETIME, time() + (8 * DAYSECS), 'local_oer');
        $this->add_userlist_entry($user->id, userlist::TYPE_A);

        $sink = $this->redirectEmails();
        $this->assertEquals(0, release_reminder::send_if_due());
        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * Test a release window is not marked as sent when no email was sent.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::send_if_due
     */
    public function test_send_if_due_does_not_set_lastsent_without_emails(): void {
        set_config('allowedlist', 1, 'local_oer');

        $sink = $this->redirectEmails();
        $this->assertEquals(0, release_reminder::send_if_due());
        $this->assertCount(0, $sink->get_messages());
        $this->assertEquals(0, get_config('local_oer', release_reminder::CONF_LASTSENT));
    }

    /**
     * Add a user list entry.
     *
     * @param int $userid User id
     * @param string $type List type
     * @return void
     * @throws \dml_exception
     */
    private function add_userlist_entry(int $userid, string $type): void {
        global $DB, $USER;

        $entry = new \stdClass();
        $entry->userid = $userid;
        $entry->type = $type;
        $entry->timecreated = time();
        $entry->timemodified = time();
        $entry->usermodified = $USER->id;
        $DB->insert_record('local_oer_userlist', $entry);
    }

    /**
     * Create release element records for disallow-list recipient lookup.
     *
     * @param int $amount Number of files to mark for release
     * @return int Course id containing the release element records
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function create_release_elements(int $amount = 1): int {
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $helper->sync_course_info($course->id);
        $helper->set_files_to($course->id, $amount, true);

        return $course->id;
    }

    /**
     * Assign usermodified values to release file records in deterministic order.
     *
     * @param int $courseid Course id
     * @param array $userids User ids
     * @return void
     * @throws \dml_exception
     */
    private function set_release_file_users(int $courseid, array $userids): void {
        global $DB;

        $records = $DB->get_records(
            'local_oer_elements',
            ['courseid' => $courseid, 'releasestate' => 1],
            'id ASC'
        );
        foreach (array_values($records) as $key => $record) {
            if (!isset($userids[$key])) {
                break;
            }
            $record->usermodified = $userids[$key];
            $DB->update_record('local_oer_elements', $record);
        }
    }
}
