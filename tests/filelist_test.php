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

/**
 * Class filelist_test
 *
 * @coversDefaultClass \local_oer\filelist
 */
final class filelist_test extends \advanced_testcase {
    /**
     * Testdata.
     *
     * @var array
     */
    private $data = [];

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once(__DIR__ . '/helper/testcourse.php');
        $helper = new testcourse();
        $course1 = $helper->generate_testcourse($this->getDataGenerator());
        $course2 = $helper->generate_testcourse($this->getDataGenerator());
        $helper->set_files_to($course1->id, 5);
        $helper->set_files_to($course2->id, 5);
        $this->data = [
                'helper' => $helper,
                'course1' => $course1,
                'course2' => $course2,
        ];
    }

    /**
     * Test get_course_files.
     *
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     * @covers ::get_course_files
     */
    public function test_get_course_files(): void {
        $files = filelist::get_course_files($this->data['course1']->id);
        $this->assertCount(5, $files);
        $files = filelist::get_course_files($this->data['course2']->id);
        $this->assertCount(5, $files);
        $course = $this->getDataGenerator()->create_course();
        $files = filelist::get_course_files($course->id);
        $this->assertCount(0, $files);
    }

    /**
     * Test get_single_file.
     *
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     * @covers ::get_single_file
     */
    public function test_get_single_file(): void {
        $identifier = $this->data['helper']->get_identifier_of_first_found_file($this->data['course2']);
        $file = filelist::get_single_file($this->data['course2']->id, $identifier);
        $this->assertIsObject($file);
        $this->assertInstanceOf('\local_oer\modules\element', $file);
        $this->assertEquals($identifier, $file->get_identifier());
    }

    /**
     * Test get_simple_filelist
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::get_simple_filelist
     */
    public function test_get_simple_filelist(): void {
        [$files, $sections] = filelist::get_simple_filelist($this->data['course1']->id);
        $this->assertIsArray($files);
        $this->assertIsArray($sections);
        $this->assertCount(5, $files);
        $this->assertCount(1, $sections);
        [$files, $sections] = filelist::get_simple_filelist($this->data['course2']->id);
        $this->assertIsArray($files);
        $this->assertIsArray($sections);
        $this->assertCount(5, $files);
        $this->assertCount(1, $sections);
        $course = $this->getDataGenerator()->create_course();
        [$files, $sections] = filelist::get_simple_filelist($course->id);
        $this->assertIsArray($files);
        $this->assertIsArray($sections);
        $this->assertCount(0, $files);
        $this->assertCount(0, $sections);

        // MDL-0 TODO: this method should be tested much more...
    }

    /**
     * Test get_simple_file
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::get_simple_file
     * @covers ::get_simple_filelist
     */
    public function test_get_simple_file(): void {
        $identifier = $this->data['helper']->get_identifier_of_first_found_file($this->data['course1']);
        $file = filelist::get_simple_file($this->data['course1']->id, $identifier);
        $this->assertIsArray($file);
        $this->assertEquals($identifier, $file['identifier']);
    }
}
