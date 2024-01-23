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
 * @copyright  2017-2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\release;

use local_oer\modules\element;
use local_oer\identifier;

/**
 * Metadata definition for moodle file elements.
 *
 * This class also adds some fields for backwards compatibility of older versions from this plugin.
 */
class filedata extends releasedata {
    /**
     *  Constructor, overwrites and extend the fields from the parent definition.
     *
     * @param int $courseid
     * @param element $element
     * @param \stdClass $elementinfo
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(int $courseid, element $element, \stdClass $elementinfo) {
        parent::__construct($courseid, $element, $elementinfo);
        global $CFG;

        $decomposed = identifier::decompose($element->get_identifier());
        $contenthash = $decomposed->value;
        $publicurl = $CFG->wwwroot . '/pluginfile.php/' .
                $this->context->id . '/local_oer/public/' .
                $elementinfo->id . '/' . $contenthash;
        $this->metadata['contenthash'] = $contenthash; // Field for backwards compatibility.
        $this->metadata['fileurl'] = $publicurl; // Field for backwards compatibility.
        $this->metadata['source'] = $publicurl; // Overwrite parent field source.
        $this->metadata['mimetype'] = $element->get_mimetype();
        $this->metadata['filesize'] = $element->get_filesize();
        $this->metadata['filecreationtime'] = $elementinfo->timecreated;
    }
}
