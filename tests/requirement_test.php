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

use local_oer\helper\requirements;
use local_oer\modules\element;
use local_oer\userlist\userlist;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helper/testcourse.php');

/**
 * Class requirement_test
 *
 * @coversDefaultClass \local_oer\helper\requirements
 */
final class requirement_test extends \advanced_testcase {
    /**
     * Test different combinations on the method which decides if a file is ready for release.
     *
     * @covers ::metadata_fulfills_all_requirements
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_metadata_fulfills_all_requirements(): void {
        $this->resetAfterTest();
        static::set_config('');

        // No additional fields are added. Initially all values except title and license have a 'false' state.
        $element = self::get_element(
                'test1',
                '',
                'cc-4.0',
                '',
                0,
                '',
                '0',
                0,
                '',
                0
        );
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertArrayHasKey('title', $reqarray);
        $this->assertArrayHasKey('license', $reqarray);
        $this->assertArrayHasKey('persons', $reqarray);
        $this->assertArrayNotHasKey('context', $reqarray);
        $this->assertCount(3, $reqarray);
        $this->assertFalse($releasable);
        $this->assertFalse($release);
        $element->set_stored_metadata_field('releasestate', 1);
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertFalse($releasable);
        $this->assertFalse($release);
        $element->set_stored_metadata_field('persons',
                '{"persons":[{"role":"Author","lastname":"Ortner","firstname":"Christian"}]}');
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertTrue($releasable);
        $this->assertTrue($release);

        static::set_config('description,context');
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertCount(5, $reqarray);
        $this->assertArrayHasKey('context', $reqarray);
        $this->assertArrayHasKey('description', $reqarray);
        $this->assertArrayNotHasKey('tags', $reqarray);
        $this->assertFalse($releasable);
        $this->assertFalse($release);
        $element->set_stored_metadata_field('description', 'abc');
        $element->set_stored_metadata_field('context', 1);
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertTrue($releasable);
        $this->assertTrue($release);

        static::set_config('description,context,tags,language,resourcetype');
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertArrayHasKey('tags', $reqarray);
        $this->assertArrayHasKey('language', $reqarray);
        $this->assertCount(8, $reqarray);
        $this->assertFalse($releasable);
        $this->assertFalse($release);

        $element->set_stored_metadata_field('tags', 'abc,cde');
        $element->set_stored_metadata_field('language', 'de');
        $element->set_stored_metadata_field('resourcetype', 5);
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertTrue($releasable);
        $this->assertTrue($release);

        static::set_config('description,context,tags,language,resourcetype,oerclassification_oefos');
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertArrayHasKey('oerclassification_oefos', $reqarray);
        $this->assertCount(9, $reqarray);
        $this->assertFalse($releasable);
        $this->assertFalse($release);
        $element->set_stored_metadata_field('classification', '{"oefos":["101001","101027"]}');
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertTrue($releasable);
        $this->assertTrue($release);

        $element->set_license('unknown');
        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $this->assertFalse($releasable);
        $this->assertFalse($release);
    }

    /**
     * Set the requirements config.
     *
     * @param string $config
     * @return void
     */
    private static function set_config(string $config): void {
        set_config('requiredfields', $config, 'local_oer');
    }

    /**
     * Create a default stdClass object similar to db record.
     *
     * @param string $title
     * @param string $description
     * @param string $license
     * @param string $persons
     * @param int $context
     * @param string $tags
     * @param string $language
     * @param int $resourcetype
     * @param string $oefos
     * @param int $state
     * @return element
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function get_element(string $title,
            string $description,
            string $license,
            string $persons,
            int $context,
            string $tags,
            string $language,
            int $resourcetype,
            string $oefos,
            int $state
    ): element {
        global $CFG;
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $identifer = identifier::compose('moodle', $CFG->wwwroot, 'file', 'contenthash', 'abcdefgh123456789');
        $element->set_identifier($identifer);
        $metadata = new \stdClass();
        $metadata->title = $title;
        $metadata->description = $description;
        $metadata->license = $license;
        $metadata->persons = $persons;
        $metadata->context = $context;
        $metadata->tags = $tags;
        $metadata->language = $language;
        $metadata->resourcetype = $resourcetype;
        $metadata->classification = $oefos;
        $metadata->releasestate = $state;
        $metadata->timemodified = time();
        $element->set_stored_metadata($metadata);
        return $element;
    }

    /**
     * As the file release reset function has something to do with the requirements, the test is added to this file.
     *
     * When the requirements are changed in the settings, a callback is called to check for all the file metadata that is set to
     * release. The requirements are tested again for this file. When the requirements are not met anymore, the release state of
     * the file is set to 0 (do not release). Also a notification is send to the user when files have to be changed.
     *
     * So this test should use at least two courses with similar setup. Different users in different roles to test if the
     * notifications are only send to the affected users.
     *
     * There are two courses with four teachers and one student each.
     * First course has one teacher allowed to use oer.
     * Second course has two teachers allowed to use oer.
     *
     * As startup the reset function is called and nothing should happen in the courses, so no messages are sent.
     *
     * First test is to reset files in the first course and see if the state of the files
     * is resetted correctly and an email is send to the one teacher of course1 (no other
     * user should get an email in this case).
     *
     * Second test is to reset files in second course and see if the two teachers with access
     * to oer get the emails.
     *
     * Third test is to test both courses at the same time.
     *
     * This function also implicitly tests the message class, so no extra test for this class
     * is added.
     *
     * This test covers the helper function reset_releasestate_if_necessary but calls the wrapper
     * from the settings.php file.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::reset_releasestate_if_necessary
     */
    public function test_local_oer_reset_releasestate_if_necessary(): void {
        $this->resetAfterTest();
        global $DB, $CFG;
        $this->setAdminUser();

        // To load settings.php some admin values have to be prepared.
        require_once($CFG->libdir . '/adminlib.php');
        $ADMIN = \admin_get_root();
        $hassiteconfig = false;
        require_once(__DIR__ . '/../settings.php');
        // The generated files does not have all fields set, so the reset function has to reset them.
        static::set_config('description,context,tags,language,resourcetype,oerclassification_oefos');

        $testcourse = new testcourse();
        $course1 = $testcourse->generate_testcourse($this->getDataGenerator());
        $course2 = $testcourse->generate_testcourse($this->getDataGenerator());
        $teacher11 = $this->getDataGenerator()->create_user();
        $teacher21 = $this->getDataGenerator()->create_user();
        $teacher31 = $this->getDataGenerator()->create_user();
        $teacher41 = $this->getDataGenerator()->create_user();
        $teacher12 = $this->getDataGenerator()->create_user();
        $teacher22 = $this->getDataGenerator()->create_user();
        $teacher32 = $this->getDataGenerator()->create_user();
        $teacher42 = $this->getDataGenerator()->create_user();
        $student11 = $this->getDataGenerator()->create_user();
        $student12 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher11->id, $course1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher21->id, $course1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher31->id, $course1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher41->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($teacher12->id, $course2->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher22->id, $course2->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher32->id, $course2->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher42->id, $course2->id, 'teacher');
        $this->getDataGenerator()->enrol_user($student11->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student12->id, $course2->id, 'student');
        $allowed = new \stdClass();
        $allowed->userid = $teacher11->id;
        $allowed->type = userlist::TYPE_A;
        $allowed->usermodified = 2;
        $allowed->timecreated = time();
        $allowed->timemodified = time();
        $DB->insert_record('local_oer_userlist', $allowed);
        $allowed->userid = $teacher22->id;
        $DB->insert_record('local_oer_userlist', $allowed);
        $allowed->userid = $teacher32->id;
        $DB->insert_record('local_oer_userlist', $allowed);

        $this->preventResetByRollback();
        $sink = $this->redirectMessages();
        // Case 0: Reset function is called without any files released.
        local_oer_reset_releasestate_if_necessary();
        $messages = $sink->get_messages();
        $this->assertEquals(0, count($messages));

        // Case 1: Test release in first course.
        $testcourse->set_files_to($course1->id, 6, true);
        $this->assertCount(5, $DB->get_records('local_oer_elements', ['releasestate' => 1]));
        $sink = $this->redirectMessages();
        local_oer_reset_releasestate_if_necessary();
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages), 'Only one teacher has the allowance to use oer.');
        $this->assertEquals($teacher11->id, reset($messages)->useridto);
        $this->assertCount(0, $DB->get_records('local_oer_elements', ['releasestate' => 1]));

        // Case 2: Test release in second course, first course does not have any files left for release.
        $testcourse->set_files_to($course2->id, 4, true);
        $this->assertCount(4, $DB->get_records('local_oer_elements', ['releasestate' => 1]));
        $sink = $this->redirectMessages();
        local_oer_reset_releasestate_if_necessary();
        $messages = $sink->get_messages();
        $this->assertEquals(2, count($messages), 'Two teachers have the allowance to use oer in course2.');
        $this->assertEquals($teacher22->id, reset($messages)->useridto);
        $this->assertEquals($teacher32->id, end($messages)->useridto);
        $this->assertCount(0, $DB->get_records('local_oer_elements', ['releasestate' => 1]));

        // Case 3: Files in both courses are released.
        $testcourse->set_files_to($course1->id, 5, true);
        $testcourse->set_files_to($course2->id, 5, true);
        $this->assertCount(10, $DB->get_records('local_oer_elements', ['releasestate' => 1], 'id ASC'));
        $sink = $this->redirectMessages();
        local_oer_reset_releasestate_if_necessary();
        $messages = $sink->get_messages();
        $this->assertEquals(3, count($messages), 'Two teachers have the allowance to use oer in course2.');
        $this->assertEquals($teacher11->id, $messages[0]->useridto);
        $this->assertEquals($teacher22->id, $messages[1]->useridto);
        $this->assertEquals($teacher32->id, $messages[2]->useridto);
        $this->assertCount(0, $DB->get_records('local_oer_elements', ['releasestate' => 1]));

        // Case 4: File has been deleted, but metadata still exists, no notification should be sent.
        $testcourse->set_files_to($course1->id, 5, true);
        $testcourse->set_files_to($course2->id, 5, true);
        $elements = filelist::get_course_files($course1->id);
        $this->assertNotEmpty($elements);
        foreach ($elements as $element) {
            foreach ($element->get_storedfiles() as $file) {
                $file->delete();
            }
        }
        $elements = filelist::get_course_files($course2->id);
        $this->assertNotEmpty($elements);
        foreach ($elements as $element) {
            foreach ($element->get_storedfiles() as $file) {
                $file->delete();
            }
        }
        $coursefiles = filelist::get_course_files($course1->id);
        $this->assertEmpty($coursefiles);
        $coursefiles = filelist::get_course_files($course2->id);
        $this->assertEmpty($coursefiles);
        $this->assertCount(10, $DB->get_records('local_oer_elements', ['releasestate' => 1]));
        $sink = $this->redirectMessages();
        local_oer_reset_releasestate_if_necessary();
        $messages = $sink->get_messages();
        $this->assertEquals(0, count($messages), 'As the files have been deleted, no notifications should be sent.');
        $this->assertCount(0, $DB->get_records('local_oer_elements', ['releasestate' => 1]));
    }
}
