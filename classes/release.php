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
use local_oer\plugininfo\oermod;
use local_oer\release\externaldata;
use local_oer\release\filedata;
use local_oer\release\legacy;

/**
 * Class release
 *
 * Load metadata of released files.
 */
class release {
    /**
     * Load the latest release of all released files.
     *
     * @param string $version Application profile version
     * @return array Metadata of all released files.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_latest_releases(string $version): array {
        $result = [];
        $courses = \local_oer\helper\activecourse::get_list_of_courses(true);
        $i = $version == 'v1.0.0' ? 1 : 0;
        $name = $version == 'v1.0.0' ? 'files' : 'elements';
        foreach ($courses as $course) {
            $data = static::get_released_files_for_course($course->courseid, $version);
            if (!empty($data)) {
                $metadata = [];
                foreach ($data as $entry) {
                    $metadata[] = $entry['metadata'];
                }
                $result['moodlecourses'][$i][$name] = $metadata;
            }
            $i++;
        }
        return $result;
    }

    /**
     * Get the release history of an identifier.
     *
     * @param string $identifier
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_release_history_of_identifier(string $identifier): array {
        global $DB;
        if (!identifier::validate($identifier)) {
            return ['error' => 'Identifier has wrong format.'];
        }
        $history = $DB->get_records('local_oer_snapshot', ['identifier' => $identifier], 'timecreated DESC');
        $result = static::metadata_by_type($history);
        return ['elements' => $result];
    }

    /**
     * Get the releases of a given release.
     *
     * @param int $releasenumber
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_releases_with_number(int $releasenumber): array {
        global $DB;
        $releases = $DB->get_records('local_oer_snapshot', ['releasenumber' => $releasenumber]);
        $result = static::metadata_by_type($releases);
        return ['release' => $releasenumber, 'elements' => $result];
    }

    /**
     * Calculate the release date for each release number.
     *
     * There can only be one release per day per configuration.
     * So this function calculates the date for each releasenumber,
     * based on the timestamps of the releases.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_releasenumber_and_date_of_releases(): array {
        global $DB;
        $records = $DB->get_records('local_oer_snapshot');
        $releases = [];
        foreach ($records as $record) {
            $date = new \DateTime();
            $date->setTimestamp($record->timecreated);
            $date->setTime(0, 0);
            $releases[$record->releasenumber] = [
                    'release' => (int) $record->releasenumber,
                    'date' => $date->format('Y-m-d'),
                    'midnight' => $date->getTimestamp(),
            ];
        }
        return ['releasedates' => array_values($releases)];
    }

    /**
     * Get prepared metadata filtered by type.
     *
     * @param array $data
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function metadata_by_type(array $data): array {
        $result = [];
        foreach ($data as $entry) {
            switch ($entry->type) {
                case element::OERTYPE_MOODLEFILE:
                    $metadata = new filedata($entry);
                    $result[] = $metadata->get_array();
                    break;
                case element::OERTYPE_EXTERNAL:
                    $metadata = new externaldata($entry);
                    $result[] = $metadata->get_array();
                    break;
            }
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
     * @param int $courseid
     * @param string $version Application profile
     * @return array Metadata of releases of one course.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_released_files_for_course(int $courseid, string $version): array {
        $snapshot = new snapshot($courseid);
        $metadata = $snapshot->get_latest_course_snapshot();
        $release = [];
        foreach ($metadata as $element) {
            if ($version == 'v1.0.0' && $element->type == element::OERTYPE_EXTERNAL) {
                continue; // Application profile v1.0.0 only supports Moodle files.
            }
            $release[] = [
                    'metadata' => static::get_file_release_metadata_json($element, $version),
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
     * @param string $version
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function get_file_release_metadata_json(\stdClass $elementinfo, string $version): array {
        if ($version == 'v1.0.0') {
            $metadata = new legacy($elementinfo);
            return $metadata->get_array();
        }
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
