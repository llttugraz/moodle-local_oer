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

use local_oer\metadata\courseinfo;
use local_oer\metadata\coursetofile;
use local_oer\modules\element;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helper/testcourse.php');

/**
 * Class coursetofile_test
 *
 * @coversDefaultClass \local_oer\metadata\coursetofile
 */
final class coursetofile_test extends \advanced_testcase {
    /**
     * Test if exception is thrown as expected.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     * @covers \local_oer\metadata\coursetofile::get_courses_metadata_for_file
     */
    public function test_get_courses_metadata_for_file_exception(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $helper = new testcourse();
        [$draftid, $unusedfile] = $helper->generate_file('unused', null, 'This file is not used anywhere');
        $identifier = $helper->generate_identifier($unusedfile->get_contenthash());
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Something really unexpected happened, ' .
                'a file contenthash (' . $unusedfile->get_contenthash() .
                ') has been searched that is not used anywhere');
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_identifier($identifier);
        coursetofile::get_courses_metadata_for_file($element, 1);
    }

    /**
     * Test if courseinfo gathered from a file used in multiple courses work.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     * @covers \local_oer\metadata\coursetofile::get_courses_metadata_for_file
     */
    public function test_get_courses_metadata_for_file(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $helper = new testcourse();
        [$draftid, $sharedfile] = $helper->generate_file('shared', null, 'Shared file in multiple courses');
        $identifier = $helper->generate_identifier($sharedfile->get_contenthash());
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $element->set_identifier($identifier);

        // Create a course and add the file to it (with a resource module).
        $course1 = $this->getDataGenerator()->create_course();

        $record = new \stdClass();
        $record->course = $course1;
        $record->files = $draftid;
        $resource1 = $this->getDataGenerator()->create_module('resource', $record);

        // Courseinfo does not exist yet, so result should be empty.

        $courses = coursetofile::get_courses_metadata_for_file($element, 1);
        $this->assertTrue(isset($courses[$course1->id]), 'File should be found in course 1.');
        $this->assertEmpty($courses[$course1->id]['courseinfo'], 'The courseinfo sync has not been executed yet.');

        // Run courseinfo sync.

        $helper->sync_course_info($course1->id);
        $courses = coursetofile::get_courses_metadata_for_file($element, $course1->id);
        $this->assertTrue(isset($courses[$course1->id]), 'File should be found in course 1.');
        $this->assertCount(1, $courses);
        $this->assertIsArray($courses[$course1->id]['courseinfo'], 'The courseinfo sync has not been executed yet.');
        $this->assertCount(1, $courses[$course1->id]['courseinfo']);
        $this->assertEquals('moodlecourse-' . $course1->id, reset($courses[$course1->id]['courseinfo'])['coursecode']);
        $this->assertEquals($course1->fullname, reset($courses[$course1->id]['courseinfo'])['metadata']['coursename']);

        // Add file to second course.

        $course2 = $this->getDataGenerator()->create_course();
        $record->course = $course2;
        $resource2 = $this->getDataGenerator()->create_module('resource', $record);

        // Course is added, but with no courseinfo metadata, as sync has not executed yet.
        $courses = coursetofile::get_courses_metadata_for_file($element, $course1->id);
        $this->assertTrue(isset($courses[$course1->id]));
        $this->assertTrue(isset($courses[$course2->id]));
        $this->assertCount(2, $courses);
        $this->assertCount(1, $courses[$course1->id]['courseinfo']);
        $this->assertEmpty($courses[$course2->id]['courseinfo']);

        $helper->sync_course_info($course2->id);
        $courses = coursetofile::get_courses_metadata_for_file($element, $course1->id);
        $this->assertTrue(isset($courses[$course1->id]));
        $this->assertTrue(isset($courses[$course2->id]));
        $this->assertCount(2, $courses);
        $this->assertCount(1, $courses[$course1->id]['courseinfo']);
        $this->assertCount(1, $courses[$course2->id]['courseinfo']);

        // Courses have additional courseinfo mappings.
        global $DB;
        $info = courseinfo::get_default_metadata_object($course1->id);
        $info->coursecode = 'external1';
        $info->type = 'unittest';
        $DB->insert_record('local_oer_courseinfo', $info);
        $info->courseid = $course2->id;
        $DB->insert_record('local_oer_courseinfo', $info);
        $info->courseid = $course1->id;
        $info->coursecode = 'external2';
        $DB->insert_record('local_oer_courseinfo', $info);

        $courses = coursetofile::get_courses_metadata_for_file($element, $course1->id);
        $this->assertTrue(isset($courses[$course1->id]));
        $this->assertTrue(isset($courses[$course2->id]));
        $this->assertCount(2, $courses);
        $this->assertCount(3, $courses[$course1->id]['courseinfo']);
        $this->assertCount(2, $courses[$course2->id]['courseinfo']);
        $this->assertEquals('moodlecourse-' . $course1->id, $courses[$course1->id]['courseinfo'][0]['coursecode']);
        $this->assertEquals('external1', $courses[$course1->id]['courseinfo'][1]['coursecode']);
        $this->assertEquals('external2', $courses[$course1->id]['courseinfo'][2]['coursecode']);
        $this->assertEquals('moodlecourse-' . $course2->id, $courses[$course2->id]['courseinfo'][0]['coursecode']);
        $this->assertEquals('external1', $courses[$course2->id]['courseinfo'][1]['coursecode']);
    }
}
