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
 * @copyright  2021-2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\plugininfo;

// Although this is stated as unexpected check from codechecker, removing this line causes an error.
// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die();

use core\plugininfo\base, moodle_url, part_of_admin_tree, admin_settingpage, core_component;

/**
 * Class plugininfo
 *
 * Contains all necessary functions for the subplugin api. As there were added more subplugin types
 * to the plugin, an abstract class has been added with all common functions.
 * New subplugin types have to extend this class and overwrite what works different.
 */
abstract class plugininfo extends base {
    /**
     * Name of the subplugintype, overwrite in derived class.
     *
     * @var string
     */
    protected static $subplugin = '';

    /**
     * Name of the config value for enabled plugins, overwrite in derived class.
     *
     * @var string
     */
    protected static $enabledplugins = '';

    /**
     * Finds all enabled plugins, the result may include missing plugins.
     *
     * @return array|null of enabled plugins $pluginname=>$pluginname, null means unknown
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_enabled_plugins() {
        $enabledplugins = [];
        $config = get_config('local_oer', static::$enabledplugins);
        if ($config) {
            $config = explode(',', $config);
            foreach ($config as $sp) {
                $sp = trim($sp);
                if ($sp !== '') {
                    $enabledplugins[$sp] = $sp;
                }
            }
        }

        $enabled = [];
        $installed = core_component::get_plugin_list(static::$subplugin);
        foreach ($installed as $plugin => $fulldir) {
            if (isset($enabledplugins[$plugin])) {
                $enabled[$plugin] = get_string('pluginname', static::$subplugin . '_' . $plugin);
            }
        }

        return $enabled;
    }

    /**
     * Test if a given plugin is enabled
     *
     * @param string $plugin shortname of subplugin
     * @return bool
     * @throws \dml_exception
     */
    public static function plugin_is_enabled(string $plugin) {
        $config = get_config('local_oer', static::$enabledplugins);
        if (strpos($config, $plugin) !== false) {
            return true;
        }
        return false;
    }

    /**
     * Get all subplugins, including missing? and deactivated.
     *
     * @return array
     */
    public static function get_all_plugins() {
        return core_component::get_plugin_list(static::$subplugin);
    }

    /**
     * Bool if subplugin can be uninstalled.
     *
     * @return bool
     */
    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Return URL used for management of plugins of this type.
     *
     * @return moodle_url
     * @throws \moodle_exception
     */
    public static function get_manage_url() {
        return new moodle_url('/admin/settings.php', ['section' => 'localplugins' . static::$subplugin]);
    }

    /**
     * Settings name for a certain submodule.
     *
     * @return string
     */
    public function get_settings_section_name() {
        return static::$subplugin . $this->name . 'settings';
    }

    /**
     * Load the settings for a specific submodule.
     *
     * @param part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig
     */
    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $plugininfo = $this; // Also can be used inside settings.php.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig || !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $settings = new admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php')); // This may also set $settings to null.

        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }
}
