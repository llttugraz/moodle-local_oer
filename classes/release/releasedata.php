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

use local_oer\helper\license;
use local_oer\helper\formhelper;
use local_oer\plugininfo\oerclassification;

/**
 * Datastructures for release metadata.
 *
 * Different sub-plugins will behave a little bit different in the release metadata. Also, the backwards compatibility to older
 * local_oer versions is taken into account.
 */
abstract class releasedata {
    /**
     * Metadata array.
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * Array of the context metadata types.
     *
     * @var array
     */
    protected array $contexts;

    /**
     * Array of the resource metadata types.
     *
     * @var array
     */
    protected array $resourcetypes;

    /**
     * Course context of the current element.
     *
     * @var \context_course
     */
    protected \context_course $context;

    /**
     * Constructor. Prepares the default release data. Fields can be overwritten or extended by derived classes.
     *
     * @param \stdClass $elementinfo Record of local_oer_snapshot table
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(\stdClass $elementinfo) {
        $this->contexts = formhelper::lom_context_list(false);
        $this->resourcetypes = formhelper::lom_resource_types(false);
        $this->context = \context_course::instance($elementinfo->courseid);

        $this->metadata = [
                'title' => $elementinfo->title,
                'identifier' => $elementinfo->identifier,
                'abstract' => $elementinfo->description ?? '',
                'license' => $this->prepare_license($elementinfo),
                'context' => $this->contexts[$elementinfo->context],
                'resourcetype' => $this->resourcetypes[$elementinfo->resourcetype],
                'language' => $elementinfo->language,
                'persons' => json_decode($elementinfo->persons)->persons,
                'tags' => empty($elementinfo->tags) ? [] : explode(',', $elementinfo->tags),
                'timereleased' => $elementinfo->timecreated,
                'classification' => self::prepare_classification_fields($elementinfo->classification),
                'courses' => empty($elementinfo->coursemetadata) ? [] : json_decode($elementinfo->coursemetadata),
        ];

        if ($elementinfo->additionaldata) {
            $additionaldata = json_decode($elementinfo->additionaldata);
            foreach ($additionaldata as $key => $value) {
                // Do not overwrite existing data.
                if (!isset($this->metadata[$key])) {
                    $this->metadata[$key] = $value;
                }
            }
        }
    }

    /**
     * Get the metadata as array.
     *
     * @return array
     */
    public function get_array(): array {
        return $this->metadata;
    }

    /**
     * Prepare the license for the release.
     *
     * @param \stdClass $elementinfo
     * @return array
     * @throws \dml_exception
     */
    protected function prepare_license(\stdClass $elementinfo): array {
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
                $list[trim($entry[0])] = trim($entry[1]);
            }
            if (isset($list[$elementinfo->license])) {
                $license = $list[$elementinfo->license];
            }
        }

        return [
                'shortname' => $license,
                'fullname' => $licenseobject->fullname,
                'source' => $licenseobject->source,
        ];
    }

    /**
     * Prepare the json_encoded classification field.
     *
     * @param string|null $fileinfo
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function prepare_classification_fields(?string $fileinfo): array {
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

        return array_values($result);
        // @codeCoverageIgnoreEnd
    }
}
