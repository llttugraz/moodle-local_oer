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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) { // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('local_oer', get_string('oer_link', 'local_oer'));
    $settings->add(new admin_setting_heading('oermetadata',
                                             get_string('oermetadataheading', 'local_oer'),
                                             get_string('oermetadataheading_desc', 'local_oer')));

    $plugins = \local_oer\plugininfo\oercourseinfo::get_enabled_plugins();
    $select  = array_merge(['no_value' => get_string('no_value', 'local_oer')], $plugins);
    $settings->add(new admin_setting_configselect('local_oer/metadataaggregator',
                                                  get_string('metadataaggregator', 'local_oer'),
                                                  get_string('metadataaggregator_description', 'local_oer'),
                                                  'no_value', $select));

    $settings->add(new admin_setting_configcheckbox('local_oer/uselicensereplacement',
                                                    get_string('uselicensereplacement', 'local_oer'),
                                                    get_string('uselicensereplacement_description', 'local_oer'),
                                                    '0'));
    if (get_config('local_oer', 'uselicensereplacement') == 1) {
        $licensereplacedefault = "cc=>CC BY 3.0\r\n" .
                                 "cc-nd=>CC BY-ND 3.0\r\n" .
                                 "cc-nc-nd=>CC BY-NC-ND 3.0\r\n" .
                                 "cc-nc=>CC BY-NC 3.0\r\n" .
                                 "cc-nc-sa=>CC BY-NC-SA 3.0\r\n" .
                                 "cc-sa=>CC BY-SA 3.0\r\n";
        $settings->add(new admin_setting_configtextarea('local_oer/licensereplacement',
                                                        get_string('licensereplacement', 'local_oer'),
                                                        get_string('licensereplacement_description', 'local_oer'),
                                                        $licensereplacedefault));
    }

    $settings->add(new admin_setting_heading('oerrelease',
                                             get_string('oerreleaseheading', 'local_oer'),
                                             get_string('oerreleaseheading_desc', 'local_oer')));

    $zipperselect = [
            0          => get_string('zipnorestriction', 'local_oer'),
            1048576    => \local_oer\helper\filehelper::get_readable_filesize(1048576),
            10485760   => \local_oer\helper\filehelper::get_readable_filesize(10485760),
            104857600  => \local_oer\helper\filehelper::get_readable_filesize(104857600),
            1048576000 => \local_oer\helper\filehelper::get_readable_filesize(1048576000),
    ];
    $settings->add(new admin_setting_configselect('local_oer/zipperfilesize',
                                                  get_string('zipperfilesize', 'local_oer'),
                                                  get_string('zipperfilesize_description', 'local_oer'),
                                                  104857600, $zipperselect));
    $settings->add(new admin_setting_configcheckbox('local_oer/allowedlist',
                                                    get_string('cb_allowedlist', 'local_oer'),
                                                    get_string('cb_allowedlist_desc', 'local_oer'),
                                                    '1'));
    $notalloweddefault = '<h3>Access not permitted</h3>' .
                         '<p>Please contact your moodle administrator if you think this is an error.</p>';
    $settings->add(new admin_setting_confightmleditor('local_oer/notallowedtext',
                                                      get_string('notallowedtext', 'local_oer'),
                                                      get_string('notallowedtext_desc', 'local_oer'),
                                                      $notalloweddefault));
    $settings->add(new admin_setting_configcheckbox('local_oer/pullservice',
                                                    get_string('pullservice', 'local_oer'),
                                                    get_string('pullservice_desc', 'local_oer'),
                                                    '0'));
    $settings->add(new \local_oer\time\oer_config_link('local_oer/configtime',
                                                       get_string('configtime', 'local_oer'),
                                                       new moodle_url('/local/oer/views/time_config.php')));

    $plugins = \local_oer\plugininfo\oeruploader::get_enabled_plugins();
    $select  = array_merge(['pullservice' => get_string('pullservice', 'local_oer')], $plugins);
    $settings->add(new admin_setting_configselect('local_oer/releaseplugin',
                                                  get_string('releaseplugin', 'local_oer'),
                                                  get_string('releaseplugin_description', 'local_oer'),
                                                  'pullservice', $select));

    if ($ADMIN->fulltree) {
        require_once(__DIR__ . '/adminlib.php');
        $settings->add(new oersubplugins_settings());
    }

    $ADMIN->add('localplugins', $settings);
    unset($settings);
    $ADMIN->add('localplugins',
                new admin_category('localoersubpluginssettings', new lang_string('pluginname', 'local_oer'), true));
    foreach (core_plugin_manager::instance()->get_plugins_of_type('oercourseinfo') as $plugin) {
        $plugin->load_settings($ADMIN, 'localoersubpluginssettings', $hassiteconfig);
    }
    foreach (core_plugin_manager::instance()->get_plugins_of_type('oerclassification') as $plugin) {
        $plugin->load_settings($ADMIN, 'localoersubpluginssettings', $hassiteconfig);
    }
    foreach (core_plugin_manager::instance()->get_plugins_of_type('oeruploader') as $plugin) {
        $plugin->load_settings($ADMIN, 'localoersubpluginssettings', $hassiteconfig);
    }
    $settings = null;
}

$ADMIN->add('root', new admin_externalpage('oer_allowedlist',
                                           get_string('manage_oer', 'local_oer'),
                                           new moodle_url('/local/oer/views/manage.php'),
                                           'local/oer:manage'));
$ADMIN->add('root', new admin_externalpage('oer_logs',
                                           get_string('log_oer', 'local_oer'),
                                           new moodle_url('/local/oer/views/log.php'),
                                           'local/oer:manage'));
$ADMIN->add('root', new admin_externalpage('oer_releasehistory',
                                           get_string('releasehistory', 'local_oer'),
                                           new moodle_url('/local/oer/views/releasehistory.php'),
                                           'local/oer:manage'));
