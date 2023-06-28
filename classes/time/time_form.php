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

require_once("$CFG->libdir/formslib.php");

/**
 * Class time_form
 */
class time_form extends \moodleform {
    /**
     * Moodle Mform Definition
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function definition() {
        $mform = $this->_form;

        $data = new \stdClass();
        $component = 'local_oer';
        $data->releasetime = get_config($component, time_settings::CONF_RELEASETIME);
        $data->releasehour = get_config($component, time_settings::CONF_RELEASEHOUR);
        $data->customdates = get_config($component, time_settings::CONF_CUSTOMDATES);

        $day = '1 ' . get_string('day', 'moodle');
        $week = '1 ' . get_string('week', 'moodle');
        $month = '1 ' . get_string('month', 'moodle');
        $custom = get_string('custom', $component);

        $selectreleasetime = [
                time_settings::DAY => $day,
                time_settings::WEEK => $week,
                time_settings::MONTH => $month,
                time_settings::CUSTOM => $custom,
        ];

        $hour = [];
        for ($i = 0; $i < 24; $i++) {
            $h = str_pad($i, 2, '0', STR_PAD_LEFT);
            $hour["$h:00"] = "$h:00";
        }

        $mform->addElement('select', time_settings::CONF_RELEASETIME, get_string(time_settings::CONF_RELEASETIME, $component),
                $selectreleasetime);
        $mform->setDefault(time_settings::CONF_RELEASETIME, time_settings::MONTH);
        $mform->addHelpButton(time_settings::CONF_RELEASETIME, time_settings::CONF_RELEASETIME, $component);

        $mform->addElement('select', time_settings::CONF_RELEASEHOUR,
                get_string(time_settings::CONF_RELEASEHOUR, $component), $hour);
        $mform->setDefault(time_settings::CONF_RELEASEHOUR, '04:00');
        $mform->addHelpButton(time_settings::CONF_RELEASEHOUR, time_settings::CONF_RELEASEHOUR, $component);

        $mform->addElement('text', time_settings::CONF_CUSTOMDATES, get_string(time_settings::CONF_CUSTOMDATES, $component));
        $mform->disabledIf(time_settings::CONF_CUSTOMDATES, time_settings::CONF_RELEASETIME, $condition = 'neq',
                $value = time_settings::CUSTOM);
        $mform->setType(time_settings::CONF_CUSTOMDATES, PARAM_RAW);
        $mform->addHelpButton(time_settings::CONF_CUSTOMDATES, time_settings::CONF_CUSTOMDATES, $component);

        $this->add_action_buttons(true);

        $this->set_data($data);
    }

    /**
     * Moodle mform validation
     *
     * The checkdate function needs a year. So the current year is taken for this.
     * As the 29.02 only is valid all 4 years, this day is considered as error.
     *
     * @param array $data
     * @param array $files
     * @return array
     * @throws \coding_exception
     */
    public function validation($data, $files) {
        $errors = [];

        if ($data[time_settings::CONF_RELEASETIME] == time_settings::CUSTOM) {
            $february = false;
            $invalid = false;
            $malformed = false;
            $currentyear = date('Y');
            $values = explode(';', $data[time_settings::CONF_CUSTOMDATES]);
            $tooshort = empty($data[time_settings::CONF_CUSTOMDATES]);
            $tooshort = $tooshort || empty($values);
            foreach ($values as $value) {
                $february = strpos($value, '29.02') !== false;
                $daymonth = explode('.', $value);
                $tooshort = $tooshort || empty($daymonth);
                $malformed = $malformed || count($daymonth) != 2;
                if (!$malformed) {
                    $invalid = !checkdate((int) $daymonth[1], (int) $daymonth[0], (int) $currentyear);
                }
            }
            if ($february || $tooshort || $malformed || $invalid) {
                $errors['customdates'] = get_string('customdates_error', 'local_oer')
                        . get_string('customdates_help', 'local_oer');
            }
        }

        return $errors;
    }
}
