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
 * Release reminder handling.
 *
 * @package    local_oer
 * @copyright  2026 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

use local_oer\helper\multilang;
use local_oer\time\time_settings;
use local_oer\userlist\userlist;

/**
 * Sends configured emails for upcoming OER releases.
 */
class release_reminder {
    /**
     * Enable release reminder emails.
     */
    const CONF_ENABLED = 'releasereminder_enabled';

    /**
     * Days before release when reminder should be sent.
     */
    const CONF_DAYSBEFORE = 'releasereminder_daysbefore';

    /**
     * Configured reminder subject.
     */
    const CONF_SUBJECT = 'releasereminder_subject';

    /**
     * Configured reminder body.
     */
    const CONF_BODY = 'releasereminder_body';

    /**
     * Last release timestamp for which reminders have been sent.
     */
    const CONF_LASTSENT = 'releasereminder_lastsent';

    /**
     * Sends emails if the configured release window is due.
     *
     * @return int Number of sent emails
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function send_if_due(): int {
        $settings = self::get_settings();
        if ($settings === null) {
            return 0;
        }

        $releasetime = (int) get_config('local_oer', time_settings::RELEASETIME);
        if (empty($releasetime) || (int) get_config('local_oer', self::CONF_LASTSENT) >= $releasetime) {
            return 0;
        }

        $sendtime = $releasetime - ((int) $settings['daysbefore'] * DAYSECS);
        if (time() < $sendtime) {
            return 0;
        }

        $sent = self::send($releasetime, $settings['subject'], $settings['body']);
        if ($sent === 0) {
            return 0;
        }

        set_config(self::CONF_LASTSENT, $releasetime, 'local_oer');
        logger::add(
            0,
            logger::LOGSUCCESS,
            'Sent ' . $sent . ' OER release email(s) for release window ' . userdate($releasetime) . '.'
        );

        return $sent;
    }

    /**
     * Return configured email settings, or null if disabled/incomplete.
     *
     * @return array|null
     * @throws \dml_exception
     */
    private static function get_settings(): ?array {
        if (!get_config('local_oer', self::CONF_ENABLED)) {
            return null;
        }

        $subject = get_config('local_oer', self::CONF_SUBJECT);
        $body = get_config('local_oer', self::CONF_BODY);
        if (empty($subject) || empty($body)) {
            return null;
        }

        return [
            'daysbefore' => (int) get_config('local_oer', self::CONF_DAYSBEFORE),
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Send release email to all recipients.
     *
     * @param int $releasetime Release timestamp
     * @param string $subject Subject template
     * @param string $body Body template
     * @return int Number of sent emails
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private static function send(int $releasetime, string $subject, string $body): int {
        $sent = 0;
        $recipients = self::get_recipients();
        foreach ($recipients as $user) {
            if (self::send_email($user, $releasetime, $subject, $body)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Get reminder recipients depending on the active allowance/disallowance list mode.
     *
     * @return \stdClass[]
     * @throws \dml_exception
     */
    private static function get_recipients(): array {
        global $DB;

        if (get_config('local_oer', 'allowedlist') == '1') {
            $sql = "SELECT DISTINCT u.*
                      FROM {user} u
                      JOIN {local_oer_userlist} ul
                        ON ul.userid = u.id AND ul.type = :listtype
                     WHERE u.deleted = 0 AND u.suspended = 0
                  ORDER BY u.lastname ASC, u.firstname ASC";
            return $DB->get_records_sql($sql, [
                'listtype' => userlist::TYPE_A,
            ]);
        }

        $sql = "SELECT DISTINCT u.*
                  FROM {user} u
                  JOIN {local_oer_elements} e
                    ON e.usermodified = u.id AND e.releasestate = :releasestate
             LEFT JOIN {local_oer_userlist} ul
                    ON ul.userid = u.id AND ul.type = :listtype
                 WHERE u.deleted = 0 AND u.suspended = 0 AND ul.id IS NULL
              ORDER BY u.lastname ASC, u.firstname ASC";
        return $DB->get_records_sql($sql, [
            'listtype' => userlist::TYPE_D,
            'releasestate' => 1,
        ]);
    }

    /**
     * Send an email with all placeholders replaced.
     *
     * @param \stdClass $user Moodle user object
     * @param int $releasetime Release timestamp
     * @param string $subject Subject template
     * @param string $body Body template
     * @return bool
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private static function send_email(
        \stdClass $user,
        int $releasetime,
        string $subject,
        string $body
    ): bool {
        $emailsubject = self::get_email_subject($user, $releasetime, $subject);
        $emailbody = self::get_email_body($user, $releasetime, $body);
        $emailbodyhtml = text_to_html($emailbody, false, false);

        return email_to_user($user, \core_user::get_support_user(), $emailsubject, $emailbody, $emailbodyhtml);
    }

    /**
     * Returns the email subject formatted in the user's language with placeholders replaced.
     *
     * @param \stdClass $user Moodle user object
     * @param int $releasetime Release timestamp
     * @param string $subject Subject template
     * @return string
     */
    private static function get_email_subject(\stdClass $user, int $releasetime, string $subject): string {
        $mlanglangs = multilang::extract_provided_languages_in_text_via_filter_mlang2($subject);
        $emailsubject = multilang::with_forced_language(
            $user->lang,
            $mlanglangs,
            static function () use ($subject): string {
                return format_string($subject);
            }
        );

        return strtr($emailsubject, self::get_subject_replacements($releasetime));
    }

    /**
     * Returns the email body formatted in the user's language with placeholders replaced.
     *
     * @param \stdClass $user Moodle user object
     * @param int $releasetime Release timestamp
     * @param string $body Body template
     * @return string
     */
    private static function get_email_body(\stdClass $user, int $releasetime, string $body): string {
        $mlanglangs = multilang::extract_provided_languages_in_text_via_filter_mlang2($body);
        $emailbody = multilang::with_forced_language(
            $user->lang,
            $mlanglangs,
            static function () use ($body): string {
                return format_text($body, FORMAT_HTML, ['noclean' => true]);
            }
        );

        return strtr($emailbody, self::get_body_replacements($user, $releasetime));
    }

    /**
     * Prepare subject placeholder replacements.
     *
     * @param int $releasetime Release timestamp
     * @return array
     * @throws \coding_exception
     */
    private static function get_subject_replacements(int $releasetime): array {
        return [
            '{releasedate}' => userdate($releasetime),
        ];
    }

    /**
     * Prepare body placeholder replacements.
     *
     * @param \stdClass $user Moodle user object
     * @param int $releasetime Release timestamp
     * @return array
     * @throws \coding_exception
     */
    private static function get_body_replacements(\stdClass $user, int $releasetime): array {
        return [
            '{firstname}' => $user->firstname,
            '{lastname}' => $user->lastname,
            '{releasedate}' => userdate($releasetime),
        ];
    }
}
