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

/**
 * Class message
 */
class message {
    /**
     * Notify the user that the requirements have changed and the file does not fullfill all of it.
     * This can only happen when the settings for required fields is changed by an administrator.
     *
     * @param \stdClass $user     Moodle user object
     * @param array     $files    file records from local_oer_files table
     * @param int       $courseid Moodle courseid
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function send_requirementschanged(\stdClass $user, array $files, int $courseid) {
        $course                     = get_course($courseid);
        $message                    = new \core\message\message();
        $message->component         = 'local_oer';
        $message->name              = 'requirementschanged';
        $message->userfrom          = \core_user::get_noreply_user();
        $message->userto            = $user;
        $message->subject           = get_string('requirementschanged_subject', 'local_oer');
        $message->fullmessage       = get_string('requirementschanged_body', 'local_oer',
                                                 ['course' => $course->fullname]);
        $message->fullmessageformat = FORMAT_HTML;
        $fullmessage                = '<p>' . get_string('requirementschanged_body', 'local_oer',
                                                         ['course' => $course->fullname]);
        $filelist                   = '';
        foreach ($files as $file) {
            $filelist .= '* ' . $file->title . '<br>';
        }
        $fullmessage              .= $filelist . '</p>';
        $message->fullmessagehtml = $fullmessage;
        $message->smallmessage    = get_string('requirementschanged_small', 'local_oer');
        $message->notification    = 1;
        $message->contexturl      = (new \moodle_url('/local/oer/views/main.php',
                                                     ['id' => $courseid]))->out(false);
        $message->contexturlname  = 'OER course files';
        $content                  = array('*' => array('header' => ' OER Requirements changed ',
                                                       'footer' => ' OER Requirements changed '));
        $message->set_additional_content('email', $content);
        message_send($message);
    }
}
