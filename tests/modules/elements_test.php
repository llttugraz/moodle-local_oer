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
 * @copyright  2024 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

use local_oer\modules\element;
use local_oer\modules\elements;

/**
 * Test elements class
 *
 * @coversDefaultClass \local_oer\modules\elements
 */
final class elements_test extends \advanced_testcase {
    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Create an element with some random parameter.
     *
     * @param bool $setlicense
     * @return element
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function create_random_element(bool $setlicense = true): element {
        $element = new element('oermod_resource\module', element::OERTYPE_MOODLEFILE);
        $identifier = identifier::compose('phpunit', 'localhost',
                'test', 'hash',
                hash('sha256', microtime() . rand(10000, 10000000)));
        $element->set_identifier($identifier);
        $element->set_title($this->getDataGenerator()->firstnames[rand(0, count($this->getDataGenerator()->firstnames) - 1)]);
        if ($setlicense) {
            $element->set_license(['unknown', 'cc-4.0', 'cc-nc-4.0', 'cc-nd-4.0', 'allrightsreserved'][rand(0, 4)]);
        }
        $element->set_source('http://localhost/');
        $element->set_mimetype(array_values(get_mimetypes_array())[rand(0, count(get_mimetypes_array()) - 1)]['type']);
        $element->set_filesize(rand(1, 1000000));
        $origin = ['mod_folder', 'mod_resource'][rand(0, 1)];
        $element->set_origin($origin, 'pluginname', $origin);
        return $element;
    }

    /**
     * Test adding of elements.
     *
     * @covers ::add_element
     * @covers ::validate_required_fields
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_add_element(): void {
        $elements = new elements();
        $this->assertCount(0, $elements);
        $elements->add_element($this->create_random_element());
        $this->assertCount(1, $elements);
        $elements->add_element($this->create_random_element());
        $this->assertCount(2, $elements);
        $elements->add_element($this->create_random_element());
        $this->assertCount(3, $elements);
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Field(s) -license- is/are required and cannot be empty');
        $element = $this->create_random_element(false);
        $elements->add_element($element);
    }

    /**
     * Test remove element from list.
     *
     * @covers ::remove_element
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_remove_element(): void {
        $elements = new elements();
        $element1 = $this->create_random_element();
        $elements->add_element($element1);
        $this->assertCount(1, $elements);
        $element2 = $this->create_random_element();
        $elements->add_element($element2);
        $this->assertCount(2, $elements);
        $element3 = $this->create_random_element();
        $elements->add_element($element3);
        $this->assertCount(3, $elements);
        $elements->remove_element(1);
        $this->assertCount(2, $elements);
        foreach ($elements as $element) {
            $this->assertFalse($element2->get_identifier() === $element->get_identifier());
        }
        $elements->remove_element(0);
        $this->assertCount(1, $elements);
        foreach ($elements as $element) {
            $this->assertFalse($element1->get_identifier() === $element->get_identifier());
        }
        $elements->remove_element(0);
        $this->assertEmpty($elements);
    }

    /**
     * Test merging of two element lists.
     *
     * @covers ::merge_elements
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_merge_elements(): void {
        $elements1 = new elements();
        $elements2 = new elements();
        $limit1 = rand(100, 1000);
        $limit2 = rand(100, 1000);
        for ($i = 0; $i < $limit1; $i++) {
            $elements1->add_element($this->create_random_element());
        }
        for ($i = 0; $i < $limit2; $i++) {
            $elements2->add_element($this->create_random_element());
        }
        $this->assertCount($limit1, $elements1);
        $this->assertCount($limit2, $elements2);
        $elements1->merge_elements($elements2);
        $this->assertCount($limit1 + $limit2, $elements1);
    }

    /**
     * Create a list of random elements and insert one element that is also returned.
     *
     * @return array
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    private function create_list_of_elements_with_one_known_element(): array {
        $element = $this->create_random_element();
        $limit = rand(101, 1000);
        $insert = rand(100, $limit - 1);
        $elements = new elements();
        for ($i = 0; $i < $limit; $i++) {
            if ($i == $insert) {
                $elements->add_element($element);
            } else {
                $elements->add_element($this->create_random_element());
            }
        }
        return [$elements, $element, $insert];
    }

    /**
     * Test finding an element.
     *
     * @covers ::find_element
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_find_element(): void {
        [$elements, $element] = $this->create_list_of_elements_with_one_known_element();
        $notfound = $elements->find_element('identifier', 'abc');
        $this->assertNull($notfound);
        $found = $elements->find_element('identifier', $element->get_identifier());
        $this->assertNotNull($found);
        $this->assertEquals($element->get_title(), $found->get_title());
    }

    /**
     * Test find element unknown property exception.
     *
     * @covers ::find_element
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_find_element_property_exists_exception(): void {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Unknown property unittest');
        $elements = new elements();
        $elements->find_element('unittest', 'phpunit');
    }

    /**
     * Test getting an element by key.
     *
     * @covers ::get_element_by_key
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_get_element_by_key(): void {
        [$elements, $element, $position] = $this->create_list_of_elements_with_one_known_element();
        $found = $elements->get_element_by_key($position);
        $this->assertEquals($element->get_identifier(), $found->get_identifier());
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Out of range! Position: 1000000 Elements " . $elements->count());
        $elements->get_element_by_key(1000000);
    }

    /**
     * Test current method.
     *
     * @covers ::current
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_current(): void {
        [$elements, $element, $position] = $this->create_list_of_elements_with_one_known_element();
        while ($elements->key() != $position) {
            $elements->next();
        }
        $current = $elements->current();
        $this->assertEquals($element->get_identifier(), $current->get_identifier());
    }

    /**
     * Test next and key method.
     *
     * @covers ::next
     * @covers ::key
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_next_and_key(): void {
        [$elements] = $this->create_list_of_elements_with_one_known_element();
        $this->assertEquals(0, $elements->key());
        $elements->next();
        $this->assertEquals(1, $elements->key());
        $elements->next();
        $this->assertEquals(2, $elements->key());
    }

    /**
     * Test valid method.
     *
     * @covers ::valid
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_valid(): void {
        $elements = new elements();
        $elements->add_element($this->create_random_element());
        $this->assertTrue($elements->valid());
        $elements->next();
        $this->assertFalse($elements->valid());
    }

    /**
     * Test rewind method.
     *
     * @covers ::rewind
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_rewind(): void {
        [$elements] = $this->create_list_of_elements_with_one_known_element();
        $this->assertEquals(0, $elements->key());
        for ($i = 0; $i < round($elements->count() / 2); $i++) {
            $elements->next();
        }
        $this->assertEquals(round($elements->count() / 2), $elements->key());
        $elements->rewind();
        $this->assertEquals(0, $elements->key());
    }

    /**
     * Test count method.
     *
     * @covers ::count
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_count(): void {
        $items = rand(100, 10000);
        $elements = new elements();
        for ($i = 0; $i < $items; $i++) {
            $elements->add_element($this->create_random_element());
        }
        $this->assertCount($items, $elements, 'Calls counting interface');
        $this->assertEquals($items, $elements->count());
    }
}
