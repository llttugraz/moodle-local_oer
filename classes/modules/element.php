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
 * Class element
 *
 * Data-structure for a single element of any type. Every module sub-plugin has to create objects of this class for every element
 * returned.
 */
class element {
    /**
     * Elements with this type are files that are stored inside Moodle where the sha1 contenthash can be used as main identifier.
     */
    public const OERTYPE_MOODLEFILE = 1;

    /**
     * This type is for plugins that deliver external sources as element.
     */
    public const OERTYPE_EXTERNAL = 2;

    /**
     * OERTYPE of this element. Use a defined type above.
     * Set by module subplugin when creating elements.
     *
     * @var int
     */
    private int $type = 0;

    /**
     * Title of the element.
     * Set by module subplugin when creating elements.
     *
     * @var string
     */
    private string $title = '';

    /**
     * Unique identifier for this element.
     *
     * @var string
     */
    private string $identifier = '';

    /**
     * Description for the identifier.
     *
     * As the elements also can be defined in external systems, different identifiers may be provided.
     *
     * @var string
     */
    private string $iddescription = '';

    /**
     * If the identifier can be reproduced directly from the element itself, add the used algorithm here.
     *
     * Example: Moodle file contenthashes are generated with SHA1, so write contenthash:SHA1 to this field.
     *
     * @var string
     */
    private string $idtype = '';

    /**
     * Shortname of the used license.
     *
     * @var string
     */
    private string $license = '';

    /**
     * Set the type for the element.
     *
     * Only defined types can be used.
     *
     * @param int $type
     * @return void
     * @throws \coding_exception
     */
    public function set_type(int $type): void {
        if (!in_array($type, [self::OERTYPE_MOODLEFILE, self::OERTYPE_EXTERNAL])) {
            throw new \coding_exception('Wrong type defined for element, use either OERTYPE_MOODLEFILE or OERTYPE_EXTERNAL.');
        }

        $this->type = $type;
    }

    /**
     * Get type of element.
     *
     * @return int
     * @throws \coding_exception
     */
    public function get_type(): int {
        if ($this->type == 0) {
            throw new \coding_exception('Type has not been set.');
        }
        return $this->type;
    }

    /**
     * Set the title of element. Cannot be empty.
     *
     * @param string $title
     * @return void
     * @throws \coding_exception
     */
    public function set_title(string $title): void {
        if (empty($title)) {
            throw new \coding_exception('Title cannot be empty.');
        }

        $this->title = $title;
    }

    /**
     * Get title of element.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_title(): string {
        if (empty($this->title)) {
            throw new \coding_exception('Title has not been set.');
        }
        return $this->title;
    }

    /**
     * Set the identifier of this element.
     *
     * @param string $identifier
     * @return void
     * @throws \coding_exception
     */
    public function set_identifier(string $identifier): void {
        if (empty($identifier)) {
            throw new \coding_exception('Identifier cannot be empty.');
        }
        $this->identifier = $identifier;
    }

    /**
     * Get the unique identifier of this element.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_identifier(): string {
        if (empty($this->identifier)) {
            throw new \coding_exception('Identifier has not been set.');
        }
        return $this->identifier;
    }

    /**
     * Set the identifier description for this element.
     *
     * @param string $description
     * @return void
     */
    public function set_iddescription(string $description): void {
        $this->iddescription = $description;
    }

    /**
     * Get the identifier description of this element.
     *
     * @return string
     */
    public function get_iddescription(): string {
        return $this->iddescription;
    }

    /**
     * Set the identifier type for this element.
     *
     * @param string $type
     * @return void
     */
    public function set_idtype(string $type): void {
        $this->idtype = $type;
    }

    /**
     * Get the identifier type of this element.
     *
     * @return string
     */
    public function get_idtype(): string {
        return $this->idtype;
    }

    /**
     * Set the license shortname, needs to be mapped to moodle shortnames.
     *
     * @param string $license
     * @return void
     * @throws \coding_exception
     */
    public function set_license(string $license): void {
        global $CFG;
        require_once ($CFG->libdir . '/licenselib.php');
        if (!\license_manager::get_license_by_shortname($license)) {
            throw new \coding_exception('Licenses needs to be mapped to Moodle license shortnames. ' .
                    'If the license is not available in Moodle set the license to unknown.');
        }
        $this->license = $license;
    }

    /**
     * Get the license of the element.
     *
     * @return void
     */
    public function get_license(): string {
        return $this->license;
    }
}
