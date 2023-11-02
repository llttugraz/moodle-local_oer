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
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\metadata;

/**
 * Interface external_metadata
 *
 * Every subplugin for course metadata aggregation should implement this interface.
 */
interface external_metadata {
    /**
     * Loads the course metadata defined by a sub plugin.
     *
     * @param int $courseid Moodle courseid
     * @param array $infos Array defined in metadata/courseinfo.php
     * @return void
     */
    public function load_data(int $courseid, array &$infos): void;

    /**
     * Enrich the file metadata with sub plugin / organisation specific metadata fields.
     * This method is called in release.php
     * Return an associative array, the fields will be added as additional data.
     * Existing fields will not be overwritten.
     *
     * @param int $courseid Moodle courseid
     * @return array
     */
    public static function add_metadata_fields(int $courseid): array;
}
