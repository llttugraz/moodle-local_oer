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

/**
 * Metadata definition for elements with external origin.
 *
 * The metadata is defined as general as possible to be usable for many different sources.
 */
class externaldata extends releasedata {
    /**
     * Constructor, overwrites and extend the fields from the parent definition.
     *
     * @param \stdClass $elementinfo
     * @throws \coding_exception
     */
    public function __construct(\stdClass $elementinfo) {
        parent::__construct($elementinfo);
        if (is_null($elementinfo->typedata)) {
            return;
        }

        $typedata = json_decode($elementinfo->typedata);
        foreach ($typedata as $key => $value) {
            $this->metadata[$key] = $value;
        }
    }
}
