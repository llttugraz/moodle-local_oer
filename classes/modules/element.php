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

use local_oer\helper\requirements;
use local_oer\requirement_test;

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
     * Unique identifier for this element. Use the class /local/oer/identifier to create identifiers.
     *
     * @var string
     */
    private string $identifier = '';

    /**
     * Shortname of the used license.
     *
     * @var string
     */
    private string $license = '';

    /**
     * Where does the element come from?
     *
     * For Moodle plugins just type in the frankenstyle plugin name (e.g. mod_resource). For external sources take the name of the
     * source (e.g. opencast).
     *
     * @var string
     */
    private string $origin = '';

    /**
     * Source url to the element. Direct link to file or external source.
     *
     * @var string
     */
    private string $source = '';

    /**
     * Filesize in bytes.
     *
     * Only necessary if type OERTYPE_MOODLEFILE is used. Optional for other types.
     * Readable formats will be created later.
     *
     * @var int
     */
    private int $filesize = 0;

    /**
     * Mimetype.
     *
     *  Only necessary if type OERTYPE_MOODLEFILE is used. Optional for other types.
     *  Readable formats will be created later.
     *
     * @var string
     */
    private string $mimetype = '';

    /**
     * Elementstate will be filled by the oer plugin and keeps track of different states an element can have
     * in the system.
     *
     * @var \stdClass
     */
    private \stdClass $elementstate;

    /**
     * Data added by the user in GUI or added by local_oer plugin will be stored here.
     *
     * @var \stdClass|null
     */
    private ?\stdClass $storedmetadata = null;

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
        $this->wrong_type($type);
        $this->type = $type;
    }

    /**
     * Get type of element.
     *
     * @return int
     * @throws \coding_exception
     */
    public function get_type(): int {
        $this->wrong_type($this->type);
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
        $this->not_empty('title', $title);
        $this->title = $title;
    }

    /**
     * Get title of element.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_title(): string {
        $this->not_empty('title', $this->title);
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
        \local_oer\identifier::strict_validate($identifier);
        $this->identifier = $identifier;
    }

    /**
     * Get the unique identifier of this element.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_identifier(): string {
        $this->not_empty('identifier', $this->identifier);
        return $this->identifier;
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
        require_once($CFG->libdir . '/licenselib.php');
        if (!\license_manager::get_license_by_shortname($license)) {
            $license = 'unknown';
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

    /**
     * Set the origin of this element.
     *
     * @param string $value
     * @return void
     * @throws \coding_exception
     */
    public function set_origin(string $value): void {
        $this->not_empty('origin', $value);
        $this->origin = $value;
    }

    /**
     * Get the identifier type of this element.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_source(): string {
        $this->not_empty('source', $this->source);
        return $this->source;
    }

    /**
     * Set the origin of this element.
     *
     * @param string $value
     * @return void
     * @throws \coding_exception
     */
    public function set_source(string $value): void {
        $this->not_empty('source', $value);
        $value = clean_param($value, PARAM_URL);
        $this->source = $value;
    }

    /**
     * Get the identifier type of this element.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_origin(): string {
        $this->not_empty('origin', $this->origin);
        return $this->origin;
    }

    /**
     * Set filesize.
     *
     * @param int $filesize
     * @return void
     * @throws \coding_exception
     */
    public function set_filesize(int $filesize): void {
        if ($this->type == self::OERTYPE_MOODLEFILE) {
            $this->not_empty('filesize', $filesize);
        }
        $this->filesize = $filesize;
    }

    /**
     * Get filesize.
     *
     * @return int
     */
    public function get_filesize(): int {
        return $this->filesize;
    }

    /**
     * Set mimetype.
     *
     * @param string $mimetype
     * @return void
     * @throws \coding_exception
     */
    public function set_mimetype(string $mimetype): void {
        if ($this->type == self::OERTYPE_MOODLEFILE) {
            $this->not_empty('mimetype', $mimetype);
        }
        $this->mimetype = $mimetype;
    }

    /**
     * Get mimetype.
     *
     * @return string
     */
    public function get_mimetype(): string {
        return $this->mimetype;
    }

    /**
     * Set the element state for the element.
     *
     * The val
     *
     * @param \stdClass $elementstate
     * @return void
     */
    public function set_elementstate(\stdClass $elementstate): void {
        $this->elementstate = $elementstate;
    }

    /**
     * Get elementstate.
     *
     * @return \stdClass
     */
    public function get_elementstate(): \stdClass {
        return $this->elementstate;
    }

    /**
     * A record from the local_oer_elements table is added to the element.
     *
     * Fields that are already stored will be overwritten.
     *
     * @param \stdClass $metadata Metadata from local_oer_elements table.
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function set_stored_metadata(\stdClass $metadata) {
        global $DB;
        [, $releasable,] = requirements::metadata_fulfills_all_requirements($metadata);
        $this->set_title($metadata->title);
        $this->set_license($metadata->license);

        // There could be more than one snapshot, so only get the newest.
        $timestamps = $DB->get_records('local_oer_snapshot', ['identifier' => $this->get_identifier()],
                'timecreated DESC', 'id,timecreated', 0, 1);
        $snapshot = new \stdClass();
        $snapshot->release = empty($timestamps) ? 0 : reset($timestamps)->timecreated;

        $metadata->timemodified = $metadata->timemodified > 0 ? userdate($metadata->timemodified) : '-';
        $metadata->timereleased = (!is_null($snapshot->release) && $snapshot->release > 0)
                ? userdate($snapshot->release) : '-';
        $metadata->timereleasedts = $snapshot->release;
        $metadata->upload = $metadata->releasestate == 1 ? 1 : 0;
        $metadata->ignore = $metadata->releasestate == 2 ? 1 : 0;
        $metadata->requirementsmet = $releasable;
        unset($metadata->identifier);
        unset($metadata->title);
        unset($metadata->license);
        $this->storedmetadata = $metadata;
    }

    /**
     * Get the stored metadata if already stored in local_oer_elements. Empty array else.
     *
     * @return \stdClass|null
     */
    public function get_stored_metadata(): ?\stdClass {
        return $this->storedmetadata;
    }

    /**
     * Validate if element is not empty.
     *
     * @param string $name
     * @param string $value
     * @return void
     * @throws \coding_exception
     */
    private function not_empty(string $name, string $value) {
        if (empty($value)) {
            throw new \coding_exception("$name has not been set.");
        }
    }

    /**
     * Validate if only defined types are used.
     *
     * @param int $type
     * @return void
     * @throws \coding_exception
     */
    private function wrong_type(int $type) {
        if (!in_array($type, [self::OERTYPE_MOODLEFILE, self::OERTYPE_EXTERNAL])) {
            throw new \coding_exception('Wrong type defined for element, use either OERTYPE_MOODLEFILE or OERTYPE_EXTERNAL.');
        }
    }
}
