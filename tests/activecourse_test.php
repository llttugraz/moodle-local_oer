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

use local_oer\helper\activecourse;

/**
 * Class activecoruse_test
 *
 * @coversDefaultClass \local_oer\helper\activecourse
 */
final class activecourse_test extends \advanced_testcase {
    /**
     * Data that is generated in setup method.
     *
     * @var null
     */
    private $data = [];

    /**
     * The amount of courses with OER material that will be chosen to be linked in test.
     *
     * @var int
     */
    private $amountcourses = 7;

    /**
     * Prepare two courses and a set of customfields with some values for those courses to use in the tests.
     *
     * @return void
     * @throws \coding_exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $number = 30;
        $courses = [];
        for ($count = 0; $count < $number; $count++) {
            $courses[] = $this->getDataGenerator()->create_course();
        }

        $filecourses = $this->select_courses($courses);
        foreach ($filecourses as $courseid) {
            $this->file_entry($courseid, false);
        }

        $snapshotcourses = $this->select_courses($courses);
        foreach ($snapshotcourses as $courseid) {
            $this->file_entry($courseid, true);
        }

        $this->data = [
                'files' => $filecourses,
                'snapshots' => $snapshotcourses,
        ];
    }

    /**
     * Create some entries in file or snapshot table for the test.
     *
     * @param int $courseid
     * @param bool $snapshot True when this test is for the snapshot table.
     * @return void
     * @throws \dml_exception
     */
    private function file_entry(int $courseid, bool $snapshot): void {
        global $DB, $USER, $CFG;
        $fileamount = rand(1, 10);
        $transaction = $DB->start_delegated_transaction();
        for ($i = 1; $i <= $fileamount; $i++) {
            $entry = new \stdClass();
            $entry->courseid = $courseid;
            $contenthash = hash('sha1', $courseid . $i . rand(1, 100000)); // String concatenation intended.
            $entry->identifier = identifier::compose('moodle',
                    $CFG->wwwroot, 'file', 'contenthash', $contenthash);
            $entry->title = "File $i for $courseid";
            $entry->description = "Unit test file $i in course $courseid";
            $entry->context = 1;
            $entry->license = "cc";
            $entry->persons = "Christian Ortner";
            $entry->tags = "Unit test";
            $entry->language = "en";
            $entry->resourcetype = 2;
            $entry->classification = null;
            $entry->releasestate = 0;
            $entry->usermodified = $USER->id;
            $entry->timecreated = time();
            $entry->timemodified = time();
            if ($snapshot) {
                $entry->coursemetadata = '{"course": "json data of coursemetadata"}';
                $entry->additionaldata = '{"data": "some fields defined in customfields or from subplugins"}';
                $entry->releasehash = hash('sha256', $courseid + $i + time());
                $DB->insert_record('local_oer_snapshot', $entry);
            } else {
                $DB->insert_record('local_oer_elements', $entry);
            }
        }
        $transaction->allow_commit();
    }

    /**
     * Select some courses from the generated ones. For these course file or snapshot entries will be generated.
     *
     * @param array $courses
     * @return array
     */
    private function select_courses(array $courses): array {
        $amount = count($courses) - 1;
        $selected = [];
        $abort = 0;
        while (count($selected) < $this->amountcourses) {
            if ($abort >= 1000) {
                break;
            }
            $selectedcourse = $courses[rand(0, $amount)];
            if (!in_array($selectedcourse->id, $selected)) {
                $selected[] = $selectedcourse->id;
            }
            $abort++;
        }
        return $selected;
    }

    /**
     * Test if the helper delivers the correct list of courses.
     *
     * @return void
     * @throws \dml_exception
     * @covers \local_oer\helper\activecourse::get_list_of_courses
     */
    public function test_get_list_of_courses(): void {
        $filecourselist = activecourse::get_list_of_courses();
        $this->assertCount(count($this->data['files']), $filecourselist);
        foreach ($filecourselist as $key => $entry) {
            $this->assertTrue(in_array($key, $this->data['files']));
        }

        $snapshotcourselist = activecourse::get_list_of_courses(true);
        $this->assertCount(count($this->data['snapshots']), $snapshotcourselist);
        foreach ($snapshotcourselist as $key => $entry) {
            $this->assertTrue(in_array($key, $this->data['snapshots']));
        }
    }
}
