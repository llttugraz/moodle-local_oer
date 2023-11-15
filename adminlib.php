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
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class courseinfo_subplugins_settings
 *
 * Extends Moodle admin_settings. Used for subplugin specific setting stuff.
 */
class oersubplugins_settings extends admin_setting {
    /**
     * courseinfo_subplugins_settings constructor.
     *
     * @throws coding_exception
     */
    public function __construct() {
        $this->nosave = true;
        parent::__construct('oersubplugins', get_string('subpluginsheading', 'local_oer'), '', '');
    }

    /**
     * Returns an array of all subplugins.
     *
     * @return array
     */
    private function get_plugin_list() {
        return [
                'mod' => core_component::get_plugin_list('oermod'),
                'courseinfo' => core_component::get_plugin_list('oercourseinfo'),
                'classification' => core_component::get_plugin_list('oerclassification'),
                'uploader' => core_component::get_plugin_list('oeruploader'),
        ];
    }

    /**
     * Always returns true, does nothing.
     *
     * @return true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true, does nothing.
     *
     * @return true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Always returns '', does not write anything.
     *
     * @param string $data
     * @return string Always returns ''
     */
    public function write_setting($data) {
        // Do not write any setting.
        return '';
    }

    /**
     * Checks if $query is one of the available subplugins.
     *
     * @param string $query The string to search for
     * @return bool Returns true if found, false if not
     * @throws coding_exception
     */
    public function is_related($query) {
        if (parent::is_related($query)) {
            return true;
        }

        $plugins = $this->get_plugin_list();
        foreach ($plugins as $key => $subplugins) {
            foreach ($subplugins as $name => $dir) {
                if (stripos($name, $query) !== false) {
                    return true;
                }

                $namestr = get_string('pluginname', 'oer' . $key . '_' . $name);
                if (strpos(core_text::strtolower($namestr), core_text::strtolower($query)) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Builds the XHTML to display the control.
     *
     * @param string $data Unused
     * @param string $query
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function output_html($data, $query = '') {
        global $OUTPUT, $PAGE;
        $pluginmanager = core_plugin_manager::instance();

        $strdisable = get_string('disable');
        $strenable = get_string('enable');
        $strname = get_string('name');
        $strsettings = get_string('settings');
        $struninstall = get_string('uninstallplugin', 'core_admin');
        $strversion = get_string('version');
        $strplugin = get_string('plugin');

        $plugins = $this->get_plugin_list();

        $return = $OUTPUT->heading(get_string('subpluginsheading', 'local_oer'), 3, 'main', true);
        $return .= $OUTPUT->box_start('generalbox oerinstalledsubplugins');

        $table = new html_table();
        $table->head = [$strplugin, $strname, $strversion, $strenable, $strsettings, $struninstall];
        $table->align = ['left', 'left', 'center', 'center', 'center', 'center'];
        $table->data = [];
        $table->attributes['class'] = 'admintable generaltable';

        foreach ($plugins as $key => $subplugins) {
            foreach ($subplugins as $name => $dir) {
                $type = 'oer' . $key . '_';
                $namestr = get_string('pluginname', $type . $name);
                $version = get_config($type . $name, 'version');
                if ($version === false) {
                    $version = '';
                }

                $plugininfo = $pluginmanager->get_plugin_info($type . $name);

                // Add hide/show link.
                $class = '';
                if (!$version) {
                    $hideshow = '';
                    $displayname = html_writer::tag('span', $name, ['class' => 'error']);
                } else if ($plugininfo->is_enabled()) {
                    $url = new moodle_url('/local/oer/subplugins.php',
                            [
                                    'sesskey' => sesskey(), 'return' => 'settings',
                                    'disable' => $name,
                                    'type' => $key,
                            ]);
                    $hideshow = $OUTPUT->pix_icon('t/hide', $strdisable);
                    $hideshow = html_writer::link($url, $hideshow);
                    $displayname = $namestr;
                } else {
                    $url = new moodle_url('/local/oer/subplugins.php',
                            [
                                    'sesskey' => sesskey(), 'return' => 'settings',
                                    'enable' => $name,
                                    'type' => $key,
                            ]);
                    $hideshow = $OUTPUT->pix_icon('t/show', $strenable);
                    $hideshow = html_writer::link($url, $hideshow);
                    $displayname = $namestr;
                    $class = 'dimmed_text';
                }

                if ($PAGE->theme->resolve_image_location('icon', $type . $name, false)) {
                    $icon = $OUTPUT->pix_icon('icon', '', $type . $name, ['class' => 'icon pluginicon']);
                } else {
                    $icon = $OUTPUT->pix_icon('spacer', '', 'moodle', ['class' => 'icon pluginicon noicon']);
                }
                $displayname = $icon . ' ' . $displayname;

                // Add settings link.
                if (!$version) {
                    $settings = '';
                } else if ($url = $plugininfo->get_settings_url()) {
                    $settings = html_writer::link($url, $strsettings);
                } else {
                    $settings = '';
                }

                // Add uninstall info.
                $uninstall = '';
                if ($uninstallurl = core_plugin_manager::instance()->get_uninstall_url($type . $name, 'manage')) {
                    $uninstall = html_writer::link($uninstallurl, $struninstall);
                }

                // Add a row to the table.
                $row = new html_table_row([ucwords($key), $displayname, $version, $hideshow, $settings, $uninstall]);
                if ($class) {
                    $row->attributes['class'] = $class;
                }
                $table->data[] = $row;
            }
        }
        $return .= html_writer::table($table);
        $return .= html_writer::tag('p', get_string('tablenosave', 'admin'));
        $return .= $OUTPUT->box_end();
        return highlight($query, $return);
    }
}

/**
 * Class local_oer_json_setting_textarea
 *
 * Helper class for submodule settings.
 */
class local_oer_json_setting_textarea extends admin_setting_configtextarea {
    /**
     * Returns an XHTML string for the editor
     *
     * @param string $data
     * @param string $query
     * @return string XHTML string for the editor
     */
    public function output_html($data, $query = '') {
        $result = parent::output_html($data, $query);

        $data = trim($data);
        if ($data) {
            $decoded = json_decode($data, true);
            // Note: it is not very nice to abuse these file classes, but anyway...
            if (is_array($decoded)) {
                $valid = '<span class="pathok">&#x2714;</span>';
            } else {
                $valid = '<span class="patherror">&#x2718;</span>';
            }
            $result = str_replace('</textarea>', '</textarea>' . $valid, $result);
        }

        return $result;
    }
}
