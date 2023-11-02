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
 * @copyright  2017 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\time;

/**
 * Class oer_config_link
 */
class oer_config_link extends \admin_setting_heading {
    /**
     * @var \moodle_url
     */
    public $link;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $visiblename
     * @param \moodle_url $link
     */
    public function __construct($name, $visiblename, \moodle_url $link) {
        $this->nosave = true;
        $this->link = $link;
        parent::__construct($name, $visiblename, '');
    }

    /**
     * Returns an HTML string
     *
     * @param mixed $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        global $PAGE;
        $context = \context_system::instance();
        $PAGE->set_context($context);
        $renderer = new \plugin_renderer_base($PAGE, 'admin');
        $data = [
                'url' => $this->link,
                'name' => $this->visiblename,
        ];
        return $renderer->render_from_template('local_oer/configlink', $data);
    }
}
