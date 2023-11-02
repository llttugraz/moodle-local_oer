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

use local_oer\helper\formhelper;
use local_oer\helper\license;
use local_oer\plugininfo\oerclassification;

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
    public function get_released_files() {
        $files = filelist::get_course_files($this->courseid);
        $snapshot = new snapshot($this->courseid);
        $metadata = $snapshot->get_latest_course_snapshot();
        $release = [];
        foreach ($files as $filearray) {
            $file = $filearray[0]['file'];
            if (!isset($metadata[$file->get_contenthash()])) {
                continue;
            }
            $release[] = [
                    'metadata' => $this->get_file_release_metadata_json($file, $metadata[$file->get_contenthash()]),
                    'storedfile' => $file,
            ];
        }
        return $release;
    }

    /**
     * Prepare the stored metadata of snapshot table for output.
     *
     * @param \stored_file $file
     * @param \stdClass $fileinfo
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function get_file_release_metadata_json(\stored_file $file, \stdClass $fileinfo): array {
        global $CFG;
        $contexts = formhelper::lom_context_list(false);
        $resourcetypes = formhelper::lom_resource_types(false);
        $classification = self::prepare_classification_fields($fileinfo->classification);
        $licenseobject = license::get_license_by_shortname($fileinfo->license);
        $license = $fileinfo->license;
        if (get_config('local_oer', 'uselicensereplacement') == 1) {
            $replacement = get_config('local_oer', 'licensereplacement');
            $replacement = explode("\r\n", $replacement);
            $list = [];
            foreach ($replacement as $line) {
                $entry = explode('=>', $line);
                if (empty($entry[1])) {
                    continue; // Skip false or empty entries.
                }
                $list[$entry[0]] = $entry[1];
            }
            if (isset($list[$fileinfo->license])) {
                $license = $list[$fileinfo->license];
            }
        }

        $fulllicense = [
                'shortname' => $license,
                'fullname' => $licenseobject->fullname,
                'source' => $licenseobject->source,
        ];

        $coursecontext = \context_course::instance($this->courseid);
        $metadata = [
                'title' => $fileinfo->title,
                'contenthash' => $fileinfo->contenthash,
                'fileurl' => $CFG->wwwroot . '/pluginfile.php/' .
                        $coursecontext->id . '/local_oer/public/' .
                        $fileinfo->id . '/' . $fileinfo->contenthash,
                'abstract' => $fileinfo->description ?? '',
                'license' => $fulllicense,
                'context' => $contexts[$fileinfo->context],
                'resourcetype' => $resourcetypes[$fileinfo->resourcetype],
                'language' => $fileinfo->language,
                'persons' => json_decode($fileinfo->persons)->persons,
                'tags' => is_null($fileinfo->tags) || $fileinfo->tags == '' ? [] : explode(',', $fileinfo->tags),
                'mimetype' => $file->get_mimetype(),
                'filesize' => $file->get_filesize(),
                'filecreationtime' => $file->get_timecreated(),
                'timereleased' => $fileinfo->timecreated,
                'classification' => $classification,
                'courses' => json_decode($fileinfo->coursemetadata),
        ];

        if ($fileinfo->additionaldata) {
            $additionaldata = json_decode($fileinfo->additionaldata);
            foreach ($additionaldata as $key => $value) {
                // Do not overwrite existing data.
                if (!isset($metadata[$key])) {
                    $metadata[$key] = $value;
                }
            }
        }

        return $metadata;
    }

    /**
     * Prepare the json_encoded classification field.
     *
     * @param string|null $fileinfo
     * @return array
     */
    private function prepare_classification_fields(?string $fileinfo): array {
        if (is_null($fileinfo)) {
            return [];
        }
        $classification = oerclassification::get_enabled_plugins();
        $info = ($fileinfo && $fileinfo != '') ? json_decode($fileinfo) : false;
        if (!$fileinfo) {
            return [];
        }
        $result = [];

        // @codeCoverageIgnoreStart
        // This code is not reachable without subplugins installed.
        foreach ($classification as $key => $pluginname) {
            $frankenstyle = 'oerclassification_' . $key;
            $plugin = '\\' . $frankenstyle . '\plugin';
            $url = $plugin::url_to_external_resource();
            $selectdata = $plugin::get_select_data(false);

            if (isset($info->$key)) {
                if (!isset($result[$key])) {
                    $result[$key] = [
                            'type' => $key,
                            'url' => $url,
                            'values' => [],
                    ];
                }
                foreach ($info->$key as $identifier) {
                    if (empty($identifier)) {
                        continue;
                    }
                    $result[$key]['values'][] = [
                            'identifier' => $identifier,
                            'name' => $selectdata[$identifier],
                    ];
                }
            }
        }

        $result = array_values($result);
        return $result;
        // @codeCoverageIgnoreEnd
    }
}
