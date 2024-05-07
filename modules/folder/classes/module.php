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
 * OER subplugin for loading mod_folder files
 *
 * @package    oermod_folder
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace oermod_folder;

use local_oer\helper\filehelper;
use local_oer\modules\elements;
use local_oer\modules\element;

/**
 * Class module
 *
 * Implements the interface required to be used in local_oer plugin.
 */
class module implements \local_oer\modules\module {
    /**
     * Load all files from a given course.
     *
     * @param int $courseid Moodle courseid
     * @return elements
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function load_elements(int $courseid): \local_oer\modules\elements {
        global $CFG;
        $elements = new elements();
        $fs = get_file_storage();
        $cms = get_fast_modinfo($courseid);
        $creator = "oermod_folder\module";

        foreach ($cms->cms as $cm) {
            $files = $fs->get_area_files($cm->context->id, 'mod_folder', 'content', false, 'id ASC', false);
            foreach ($files as $file) {
                $element = new element($creator, element::OERTYPE_MOODLEFILE);
                $element->set_origin('mod_folder', 'pluginname', 'mod_folder');
                $element->set_title($file->get_filename());
                $identifier = \local_oer\identifier::compose(
                        'moodle', $CFG->wwwroot, 'file',
                        'contenthash', $file->get_contenthash()
                );
                $element->set_identifier($identifier);
                $element->set_source(filehelper::get_file_url($file, true));
                $element->set_license($file->get_license() ?? 'unknown');
                $element->set_filesize($file->get_filesize());
                $element->set_mimetype($file->get_mimetype());
                $element->set_storedfile($file);

                $element->add_information('type', 'local_oer', $file->get_mimetype(), 'mimetype', $file->get_mimetype());
                $sectionname = get_section_name($courseid, $cm->sectionnum);
                $sectionurl = new \moodle_url('/course/view.php', ['id' => $courseid], "section-$cm->sectionnum");
                $element->add_information('section', 'moodle', $sectionname, null, '', $sectionurl);
                $element->add_information('pluginname', 'mod_folder', $cm->name, null, '', $cm->url);

                $elements->add_element($element);
            }
        }

        return $elements;
    }

    /**
     * Fields that can be written back from local_oer to the source.
     *
     * @return array[]
     */
    public function writable_fields(): array {
        return [
                ['license', 'moodle'],
        ];
    }

    /**
     * Write back the fields that are allowed to overwrite in the source.
     *
     * @param element $element
     * @return void
     */
    public function write_to_source(\local_oer\modules\element $element): void {
        foreach ($element->get_storedfiles() as $file) {
            $file->set_license($element->get_license());
        }
    }

    /**
     * Return the list of active licences in Moodle.
     *
     * @return array
     */
    public function supported_licences(): array {
        $licences = \license_manager::get_active_licenses_as_array();
        $result = [];
        foreach ($licences as $key => $licence) {
            $result[] = $key;
        }
        return $result;
    }

    /**
     * Return supported roles.
     *
     * @return array[]
     */
    public function supported_roles(): array {
        return [
                ['Author', 'author', 'local_oer', self::ROLE_REQUIRED],
                ['Publisher', 'publisher', 'local_oer'],
        ];
    }

    /**
     * Set element to release.
     *
     * @param element $element
     * @return bool
     */
    public function set_element_to_release(\local_oer\modules\element $element): bool {
        return true; // Nothing special to do here.
    }
}
