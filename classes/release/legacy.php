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
 * @copyright  2017-2024 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\release;

use local_oer\helper\formhelper;
use local_oer\identifier;

/**
 * Metadata definition for moodle files used before oermod subplugins.
 *
 * Used for applicationprofile:v1.0.0 calls.
 */
class legacy extends releasedata {
    /**
     *  Constructor, overwrites and extend the fields from the parent definition.
     *
     * @param \stdClass $elementinfo
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(\stdClass $elementinfo) {
        global $CFG;

        // No parent constructor.
        $decomposed = identifier::decompose($elementinfo->identifier);
        $typedata = empty($elementinfo->typedata) ? [] : json_decode($elementinfo->typedata);
        $this->context = \context_course::instance($elementinfo->courseid);
        $this->contexts = formhelper::lom_context_list(false);
        $this->resourcetypes = formhelper::lom_resource_types(false);

        $urlparts = [
                $CFG->wwwroot,
                'pluginfile.php',
                $this->context->id,
                'local_oer',
                'public',
                $elementinfo->id,
                $decomposed->value,
        ];

        $publicurl = implode('/', $urlparts);

        $this->metadata = [
                'title' => $elementinfo->title,
                'contenthash' => $decomposed->value,
                'fileurl' => $publicurl,
                'abstract' => $elementinfo->description ?? '',
                'license' => $this->prepare_license($elementinfo),
                'context' => $this->contexts[$elementinfo->context],
                'resourcetype' => $this->resourcetypes[$elementinfo->resourcetype],
                'language' => $elementinfo->language,
                'persons' => json_decode($elementinfo->persons)->persons,
                'tags' => empty($elementinfo->tags) ? [] : explode(',', $elementinfo->tags),
                'mimetype' => $typedata->mimetype ?? '',
                'filesize' => $typedata->filesize ?? 0,
                'filecreationtime' => $elementinfo->timecreated, // MDL-0 TODO: here should be the timestamp of the file.
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
}
