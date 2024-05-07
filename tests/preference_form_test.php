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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helper/testcourse.php');

use local_oer\forms\courseinfo_form;
use local_oer\forms\preference_form;
use local_oer\metadata\courseinfo;

/**
 * Class preference_form_test
 *
 * @coversDefaultClass \local_oer\forms\preference_form
 */
final class preference_form_test extends \advanced_testcase {
    /**
     * Test validation of formular.
     * Also runs through the formular definition, but there is not tested anything special.
     * At least it throws php or moodle errors when something has changed (php version or moodle api definitions etc.)
     *
     * @return void
     * @throws \coding_exception
     * @covers ::validation
     * @covers ::definition
     */
    public function test_validation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        global $USER, $DB;

        $course = $this->getDataGenerator()->create_course();

        new preference_form(null, ['courseid' => $course->id]);

        $entry = new \stdClass();
        $entry->courseid = $course->id;
        $entry->context = 1;
        $entry->license = 'cc';
        $entry->persons = 'Unit tester';
        $entry->tags = 'Unit test';
        $entry->language = 'en';
        $entry->resourcetype = 7;
        $entry->classification = 'Some value';
        $entry->state = 1;
        $entry->usermodified = $USER->id;
        $entry->timecreated = time();
        $entry->timemodified = time();
        $DB->insert_record('local_oer_preference', $entry);

        $mform = new preference_form(null, ['courseid' => $course->id]);

        $result = $mform->validation([], []);
        $this->assertEmpty($result);
    }

    /**
     * Test update metadata function.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::update_metadata
     * @covers ::add_values_from_form
     */
    public function test_update_metadata(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mform = new preference_form(null, ['courseid' => $course->id]);

        $entry = [];
        $entry['courseid'] = $course->id;
        $entry['context'] = 1;
        $entry['license'] = 'cc';
        $entry['storedperson'] = 'Unit tester';
        $entry['storedtags'] = 'Unit test';
        $entry['language'] = 'en';
        $entry['resourcetype'] = 7;
        $entry['ignore'] = 0;
        $mform->update_metadata($entry);
        $record = $DB->get_record('local_oer_preference', ['courseid' => $course->id]);
        $this->assertEquals('Unit test', $record->tags);
        $entry['storedtags'] = 'Unit test, New tag';
        $mform->update_metadata($entry);
        $record = $DB->get_record('local_oer_preference', ['courseid' => $course->id]);
        $this->assertEquals('Unit test, New tag', $record->tags);
    }
}
