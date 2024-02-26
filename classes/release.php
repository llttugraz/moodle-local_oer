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
 * @copyright  2017-2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

use local_oer\modules\element;
use local_oer\release\externaldata;
use local_oer\release\filedata;

/**
 * Class release
 */
class release {
    /**
     * @var int Moodle courseid
     */
    private $courseid = null;

    /**
     * Constructor.
     *
     * @param int $courseid Moodle courseid
     */
    public function __construct(int $courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Prepare a filelist that contains all information about the metadata of released files.
     * Only files that exist and are released in snapshot table will be considered.
     *
     * Returns array of:
     * [
     *   [
     *     'metadata' => [
     *        ... file metadata ...
     *        'courses' => [course metadata] there can be more than one course attached (external course informations of mapped
     *        courses)
     *        ... additional metadata defined in subplugin ...
     *      ],
     *     'storedfile => Moodle stored_file object
     *   ]
     * ]
     *
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_released_files(): array {
        $elements = filelist::get_course_files($this->courseid);
        $snapshot = new snapshot($this->courseid);
        $metadata = $snapshot->get_latest_course_snapshot();
        $release = [];
        foreach ($elements as $element) {
            if (!isset($metadata[$element->get_identifier()])) {
                continue;
            }
            $release[] = [
                    'metadata' => $this->get_file_release_metadata_json($metadata[$element->get_identifier()]),
                    'storedfile' => $element,
            ];
        }
        return $release;
    }

    /**
     * Prepare the stored metadata of snapshot table for output.
     *
     * $elementinfo is a record from the snapshot table with the released license in it.
     *
     * @param \stdClass $elementinfo
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function get_file_release_metadata_json(\stdClass $elementinfo): array {
        switch ($elementinfo->type) {
            case element::OERTYPE_MOODLEFILE:
                $metadata = new filedata($elementinfo);
                break;
            case element::OERTYPE_EXTERNAL:
                $metadata = new externaldata($elementinfo);
                break;
            default:
                throw new \coding_exception('Element type not set');
        }

        return $metadata->get_array();
    }
}
