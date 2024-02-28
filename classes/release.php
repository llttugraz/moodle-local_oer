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
 *
 * Load metadata of released files.
 */
class release {
    /**
     * Load the latest release of all released files.
     *
     * @return array Metadata of all released files.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_latest_releases(): array {
        $result = [];
        $courses = \local_oer\helper\activecourse::get_list_of_courses(true);
        $i = 0;
        foreach ($courses as $course) {
            $data = static::get_released_files_for_course($course->courseid);
            if (!empty($data)) {
                $metadata = [];
                foreach ($data as $entry) {
                    $metadata[] = $entry['metadata'];
                }
                $result['moodlecourses'][$i]['files'] = $metadata;
            }
            $i++;
        }
        return $result;
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
     * @return array Metadata of releases of one course.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_released_files_for_course(int $courseid): array {
        $elements = filelist::get_course_files($courseid);
        $snapshot = new snapshot($courseid);
        $metadata = $snapshot->get_latest_course_snapshot();
        $release = [];
        foreach ($elements as $element) {
            if (!isset($metadata[$element->get_identifier()])) {
                continue;
            }
            $release[] = [
                    'metadata' => static::get_file_release_metadata_json($metadata[$element->get_identifier()]),
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
    private static function get_file_release_metadata_json(\stdClass $elementinfo): array {
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
