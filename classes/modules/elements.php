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

namespace local_oer\modules;

/**
 * Class elements
 *
 * Data-structure for a list of \module\element of any type. Every module sub-plugin has to return this class.
 */
class elements implements \Iterator, \Countable {
    /**
     * List of elements.
     *
     * @var element[]
     */
    private array $elements = [];

    /**
     * Current position of the iterator.
     *
     * @var int
     */
    private int $pos = 0;

    /**
     * Add an element to the list.
     *
     * @param element $element
     * @return void
     * @throws \coding_exception
     */
    public function add_element(element $element): void {
        $this->validate_required_fields($element);
        $this->elements[] = $element;
    }

    /**
     * Remove an element from the list with the iterator key.
     *
     * The iterator key will then point automatically to the next item.
     * If it was the last item the key will be invalid.
     *
     * @param int $pos
     * @return void
     */
    public function remove_element(int $pos): void {
        unset($this->elements[$pos]);
        // Rearrange elements so that there are no holes in the array.
        $this->elements = array_values($this->elements);
        if ($this->pos > 0) {
            $this->pos--;
        }
    }

    /**
     * Merge a list of elements into this one.
     *
     * @param elements $elements
     * @return void
     */
    public function merge_elements(elements $elements): void {
        $this->rewind();
        foreach ($elements as $element) {
            $this->elements[] = $element;
        }
    }

    /**
     * Find an element in the list.
     *
     * If a non-unique value is given, it only returns the first found element.
     *
     * @param string $fieldname
     * @param string $fieldvalue
     * @return element|null
     * @throws \coding_exception
     */
    public function find_element(string $fieldname, string $fieldvalue): ?element {
        if (!property_exists('local_oer\modules\element', $fieldname)) {
            throw new \coding_exception("Unknown property $fieldname");
        }
        $function = "get_$fieldname";
        if (!method_exists('local_oer\modules\element', $function)) {
            throw new \coding_exception("Unknown method $function");
        }

        foreach ($this->elements as $element) {
            if ($element->$function() == $fieldvalue) {
                return $element;
            }
        }
        return null;
    }

    /**
     * Load the element on given position.
     *
     * @param int $key Position of element in array.
     * @return element
     * @throws \coding_exception
     */
    public function get_element_by_key(int $key): element {
        if (!isset($this->elements[$key])) {
            throw new \coding_exception("Out of range! Position: $key Elements " . $this->count());
        }
        return $this->elements[$key];
    }

    /**
     * Return current element.
     *
     * PHP iterator interface.
     *
     * @return element
     */
    public function current(): element {
        return $this->elements[$this->pos];
    }

    /**
     * Increase position to next element.
     *
     * PHP iterator interface.
     *
     * @return void
     */
    public function next(): void {
        ++$this->pos;
    }

    /**
     * Return current position.
     *
     * PHP iterator interface.
     *
     * @return int
     */
    public function key(): int {
        return $this->pos;
    }

    /**
     * Test if current position results in a valid object.
     *
     * PHP iterator interface.
     *
     * @return bool
     */
    public function valid(): bool {
        return isset($this->elements[$this->pos]);
    }

    /**
     * Set the array key back to start.
     *
     * PHP iterator interface.
     *
     * @return void
     */
    public function rewind(): void {
        $this->pos = 0;
    }

    /**
     * Count all elements stored in this class.
     *
     * PHP countable interface.
     *
     * @return int
     */
    public function count(): int {
        return count($this->elements);
    }

    /**
     * When a new element is added there are some minimum required fields.
     *
     * Without these fields the local_oer plugin cannot process the elements correctly.
     * It is only tested if the fields are empty, as the element itself tests for the content of the fields.
     *
     * @param element $element
     * @return void
     * @throws \coding_exception
     */
    private function validate_required_fields(element $element): void {
        $required = ['type', 'title', 'identifier', 'license', 'origin', 'source'];
        if ($element->get_type() == element::OERTYPE_MOODLEFILE) {
            $required[] = 'filesize';
            $required[] = 'mimetype';
        }
        $missing = [];
        foreach ($required as $param) {
            $field = "get_$param";
            if (empty($element->$field())) {
                $missing[] = $param;
            }
        }
        if (!empty($missing)) {
            throw new \coding_exception('Field(s) -' . implode(',', $missing) . '- is/are required and cannot be empty');
        }
    }
}
