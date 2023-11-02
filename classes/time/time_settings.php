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
 * @copyright  2017-2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\time;

defined('MOODLE_INTERNAL') || die();

use local_oer\logger;

require_once($CFG->libdir . "/licenselib.php");

/**
 * Class time_settings
 */
class time_settings {
    /**
     * Day
     */
    const DAY = 'day';

    /**
     * Week
     */
    const WEEK = 'week';

    /**
     * Month
     */
    const MONTH = 'month';

    /**
     * Custom
     */
    const CUSTOM = 'custom';

    /**
     * Next releasetime
     */
    const RELEASETIME = 'next_release_time';

    /**
     * Config uploadtime
     */
    const CONF_RELEASETIME = 'releasetime';

    /**
     * Config releasehour
     */
    const CONF_RELEASEHOUR = 'releasehour';

    /**
     * Config customdates
     */
    const CONF_CUSTOMDATES = 'customdates';

    /**
     * Set the next upload window
     *
     * @throws \dml_exception
     */
    public static function set_next_upload_window() {
        $plugin = 'local_oer';
        $setting = get_config($plugin, self::CONF_RELEASETIME);
        $clock = get_config($plugin, self::CONF_RELEASEHOUR);
        switch ($setting) {
            case self::DAY:
                $next = new \DateTime("tomorrow $clock");
                break;
            case self::WEEK:
                $next = new \DateTime("Monday next week $clock");
                break;
            case self::MONTH:
                $next = new \DateTime("first day of next month $clock");
                break;
            case self::CUSTOM:
                $dates = explode(';', get_config($plugin, self::CONF_CUSTOMDATES));
                $now = new \DateTime();
                $smallest = -1;
                $smallestdate = -1;
                foreach ($dates as $date) {
                    $a = explode('.', $date);
                    $month = $now->format('m');
                    $day = $now->format('d');
                    if ($a[1] < $month || ($a[1] == $month && $a[0] <= $day)) {
                        $year = (int) $now->format('Y') + 1;
                    } else {
                        $year = $now->format('Y');
                    }
                    $timestring = "$year-$a[1]-$a[0] $clock";
                    $time = new \DateTime($timestring);
                    $diff = $time->getTimestamp() - $now->getTimestamp();
                    if ($diff <= 0) {
                        continue;
                    } else if ($smallest == -1 || ($smallest != -1 && $diff < $smallest)) {
                        $smallest = $diff;
                        $smallestdate = $time;
                    }
                }
                $next = $smallestdate;
                break;
        }
        set_config(self::RELEASETIME, $next->getTimestamp(), $plugin);
        logger::add(0, logger::LOGSUCCESS, 'Set next release window to ' . userdate($next->getTimestamp()));
    }

    /**
     * Get the string to show remaining days until next upload
     *
     * @param int $diff
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public static function format_difference($diff) {
        $a = [];
        $a['days'] = floor($diff / (3600 * 24));
        $a['hours'] = floor(($diff % (3600 * 24)) / 3600);
        $a['minutes'] = floor(($diff % 3600) / 60);

        return get_string('timediff', 'local_oer', $a);
    }

    /**
     * Output to show to users.
     * This will display the date and time when the release will happen, and the remaining time in days until then.
     *
     * @param int $courseid
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_timeslot_output(int $courseid): string {
        global $PAGE;
        $context = \context_course::instance($courseid);
        $PAGE->set_context($context);
        $renderer = new \plugin_renderer_base($PAGE, 'course');
        $compare = time();
        $nextrelease = get_config('local_oer', self::RELEASETIME);
        $diff = $nextrelease - $compare;
        $data = [
                'nextrelease' => $nextrelease < time() ? '-' : userdate($nextrelease),
                'nextdiff' => $diff < 0 ? '-' : self::format_difference($diff),
        ];
        return $renderer->render_from_template('local_oer/timeslot', $data);
    }
}
