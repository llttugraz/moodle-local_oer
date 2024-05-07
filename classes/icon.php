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

namespace local_oer;

/**
 * Class icon
 *
 * Loads icon for a mimetype.
 */
class icon {
    /**
     * Find the correct file icon.
     *
     * This method uses the icons the moodle filetype page has defined.
     * Needs to be called before select_file_icon_or_thumbnail as the return values
     * are used there. The reason to not include the code is to make it separatable for
     * performance. E.g. call this function outside for loops... as this information
     * does not need to be called for every element.
     *
     * @param int $courseid Moodle courseid
     * @return array
     */
    public static function prepare_file_icon_renderer(int $courseid): array {
        global $CFG, $PAGE;
        require_once($CFG->libdir . '/filelib.php');
        $context = \context_course::instance($courseid);
        $PAGE->set_context($context);
        $types = \get_mimetypes_array();
        $icons = [];
        foreach ($types as $type) {
            if (isset($type['icon'])) {
                $icons[$type['type']] = $type['icon'];
            }
        }
        $renderer = new \core_renderer($PAGE, 'course');
        return [$icons, $renderer];
    }

    /**
     * Return the icon based on its mimetype.
     *
     * 2023-11-20 Update:
     * Remove thumbnail option and rename function.
     * Add fallback to text/plain if no mimetype is given.
     *
     * @param string $mimetype
     * @param \core_renderer $renderer
     * @param array $icons
     * @return mixed|string
     */
    public static function select_file_icon(string $mimetype, \core_renderer $renderer, array $icons) {
        $mimetype = empty($mimetype) ? 'text/plain' : $mimetype;
        $fullicon = $renderer->pix_icon('f/' . $icons[$mimetype], '');
        $iconpart = explode('src="', $fullicon);
        $iconurl = explode('"', $iconpart[1]);
        $icon = $iconurl[0];
        return $icon;
    }
}
