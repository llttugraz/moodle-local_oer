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
use local_oer\modules\element;
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
                    'metadata' => $this->get_file_release_metadata_json($element, $metadata[$element->get_identifier()]),
                    'storedfile' => $element,
            ];
        }
        return $release;
    }

    /**
     * Prepare the stored metadata of snapshot table for output.
     *
     * TODO: there is an inconsistency between $element and $elementinfo regarding the license.
     * $elementinfo is a record from the snapshot table with the released license in it. It is possible that $element has a
     * different license set at this point.
     *
     * @param element $element
     * @param \stdClass $elementinfo
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function get_file_release_metadata_json(element $element, \stdClass $elementinfo): array {
        global $CFG;
        $contexts = formhelper::lom_context_list(false);
        $resourcetypes = formhelper::lom_resource_types(false);
        $classification = self::prepare_classification_fields($elementinfo->classification);
        $licenseobject = license::get_license_by_shortname($elementinfo->license);
        $license = $elementinfo->license;
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
            if (isset($list[$elementinfo->license])) {
                $license = $list[$elementinfo->license];
            }
        }

        $fulllicense = [
                'shortname' => $license,
                'fullname' => $licenseobject->fullname,
                'source' => $licenseobject->source,
        ];

        $coursecontext = \context_course::instance($this->courseid);
        $decomposed = identifier::decompose($element->get_identifier());
        $contenthash = '';
        if ($element->get_type() == element::OERTYPE_MOODLEFILE && $decomposed->valuetype == 'contenthash') {
            $contenthash = $decomposed->value;
        }
        $metadata = [
                'title' => $elementinfo->title,
                'identifier' => $element->get_identifier(),
                'contenthash' => $contenthash, // Deprecated, only for backwards compatibility.
                'fileurl' => $CFG->wwwroot . '/pluginfile.php/' .
                        $coursecontext->id . '/local_oer/public/' .
                        $elementinfo->id . '/' . $contenthash,
                'abstract' => $elementinfo->description ?? '',
                'license' => $fulllicense,
                'context' => $contexts[$elementinfo->context],
                'resourcetype' => $resourcetypes[$elementinfo->resourcetype],
                'language' => $elementinfo->language,
                'persons' => json_decode($elementinfo->persons)->persons,
                'tags' => empty($elementinfo->tags) ? [] : explode(',', $elementinfo->tags),
                'mimetype' => $element->get_mimetype(),
                'filesize' => $element->get_filesize(),
            // TODO: Same as timereleased? Where did the timestamp before was read?
                'filecreationtime' => $elementinfo->timecreated,
                'timereleased' => $elementinfo->timecreated,
                'classification' => $classification,
                'courses' => json_decode($elementinfo->coursemetadata),
        ];

        if ($elementinfo->additionaldata) {
            $additionaldata = json_decode($elementinfo->additionaldata);
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
