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

use local_oer\modules\element;

/**
 * Class message
 */
class message {
    /**
     * Notify the user that the requirements have changed and the file does not fullfill all of it.
     * This can only happen when the settings for required fields is changed by an administrator.
     *
     * @param \stdClass $user Moodle user object
     * @param array $elements array of element titles
     * @param int $courseid Moodle courseid
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function send_requirementschanged(\stdClass $user, array $elements, int $courseid) {
        $course = get_course($courseid);
        $message = new \core\message\message();
        $message->component = 'local_oer';
        $message->name = 'requirementschanged';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('requirementschanged_subject', 'local_oer');
        $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
        $message->fullmessageformat = FORMAT_HTML;
        $fullmessage = '<p>' . get_string('requirementschanged_body', 'local_oer',
                        ['url' => $courseurl->out(), 'course' => $course->fullname]);
        $filelisthtml = '';
        foreach ($elements as $title) {
            $filelisthtml .= '* ' . $title . '<br>';
        }
        $fullmessage .= $filelisthtml . '</p>';
        $message->fullmessage = $fullmessage;
        $message->fullmessagehtml = $fullmessage;
        $message->smallmessage = get_string('requirementschanged_small', 'local_oer');
        $message->notification = 1;
        $message->contexturl = (new \moodle_url('/local/oer/views/main.php',
                ['id' => $courseid]))->out(false);
        $message->contexturlname = 'OER course files';
        $support = \core_user::get_support_user();
        $content = [
                '*' => [
                        'header' => 'OER Requirements changed ',
                        'footer' => 'If you have any questions, please contact ' . $support->email,
                ],
        ];
        $message->set_additional_content('email', $content);
        message_send($message);
    }
}
