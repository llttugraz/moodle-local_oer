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

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('oersettings', get_string('oer_link', 'local_oer'), false));
    $settings = new admin_settingpage('local_oer', get_string('oer_settings', 'local_oer'));
    $settings->add(new admin_setting_heading('oermetadata',
                                             get_string('oermetadataheading', 'local_oer'),
                                             get_string('oermetadataheading_desc', 'local_oer')));

    $settings->add(new admin_setting_configcheckbox('local_oer/coursecustomfields',
                                                    get_string('coursecustomfields', 'local_oer'),
                                                    get_string('coursecustomfields_description', 'local_oer'),
                                                    '0'));

    $visibletoall      = get_string('customfield_visibletoall', 'core_course');
    $visibletoteachers = get_string('customfield_visibletoteachers', 'core_course');
    $notvisible        = get_string('customfield_notvisible', 'core_course');
    $visibilityoptions = [
            \core_course\customfield\course_handler::VISIBLETOALL      => $visibletoall,
            \core_course\customfield\course_handler::VISIBLETOTEACHERS => $visibletoteachers,
            \core_course\customfield\course_handler::NOTVISIBLE        => $notvisible
    ];

    $settings->add(new admin_setting_configselect('local_oer/coursecustomfieldsvisibility',
                                                  get_string('coursecustomfieldsvisibility', 'local_oer'),
                                                  get_string('coursecustomfieldsvisibility_description', 'local_oer'),
                                                  \core_course\customfield\course_handler::VISIBLETOALL, $visibilityoptions));
    $settings->hide_if('local_oer/coursecustomfieldsvisibility', 'local_oer/coursecustomfields');

    $customfields = \local_oer\metadata\coursecustomfield::get_course_customfields(-1);
    $customselect = [];
    foreach ($customfields as $category) {
        foreach ($category['fields'] as $field) {
            $visibility = $visibletoall;
            switch ($field['visibility']) {
                case \core_course\customfield\course_handler::VISIBLETOTEACHERS:
                    $visibility = $visibletoteachers;
                    break;
                case  \core_course\customfield\course_handler::NOTVISIBLE:
                    $visibility = $notvisible;
            }
            $customselect[$category['id'] . ':' . $field['id']] = $field['fullname'] . ' (' . $category['name'] . ' ' .
                                                                  $visibility . ')';
        }
    }

    $settings->add(new admin_setting_configmultiselect('local_oer/coursecustomfieldsignored',
                                                       get_string('coursecustomfieldsignored', 'local_oer'),
                                                       get_string('coursecustomfieldsignored_description', 'local_oer'),
                                                       [], $customselect));
    $settings->hide_if('local_oer/coursecustomfieldsignored', 'local_oer/coursecustomfields');

    $plugins = \local_oer\plugininfo\oercourseinfo::get_enabled_plugins();
    $select  = array_merge(['no_value' => get_string('no_value', 'local_oer')], $plugins);
    $settings->add(new admin_setting_configselect('local_oer/metadataaggregator',
                                                  get_string('metadataaggregator', 'local_oer'),
                                                  get_string('metadataaggregator_description', 'local_oer'),
                                                  'no_value', $select));

    $settings->add(new admin_setting_configcheckbox('local_oer/coursetofile',
                                                    get_string('coursetofile', 'local_oer'),
                                                    get_string('coursetofile_description', 'local_oer'),
                                                    '0'));

    $requiredchoices = [
            'description'  => 'abstract',
            'context'      => 'context',
            'tags'         => 'tags',
            'language'     => 'language',
            'resourcetype' => 'resourcetype',
    ];
    $classifications = \local_oer\plugininfo\oerclassification::get_enabled_plugins();
    foreach ($classifications as $key => $classplugin) {
        $requiredchoices['oerclassification_' . $key] = $key;
    }

    $reqsetting = new admin_setting_configmultiselect('local_oer/requiredfields',
                                                      get_string('requiredfields', 'local_oer'),
                                                      get_string('requiredfields_desc', 'local_oer'),
                                                      [], $requiredchoices);
    $reqsetting->set_updatedcallback('local_oer_reset_releasestate_if_necessary');

    $settings->add($reqsetting);

    $settings->add(new admin_setting_configcheckbox('local_oer/uselicensereplacement',
                                                    get_string('uselicensereplacement', 'local_oer'),
                                                    get_string('uselicensereplacement_description', 'local_oer'),
                                                    '0'));
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
    $settings->hide_if('local_oer/licensereplacement', 'local_oer/uselicensereplacement');

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

    $ADMIN->add('oersettings', $settings);
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
} else {
    $ADMIN->add('root', new admin_category('oersettings', get_string('oer_link', 'local_oer'), false));
}

$ADMIN->add('oersettings', new admin_externalpage('oer_allowedlist',
                                                  get_string('manage_oer', 'local_oer'),
                                                  new moodle_url('/local/oer/views/manage.php'),
                                                  'local/oer:manage'));
$ADMIN->add('oersettings', new admin_externalpage('oer_logs',
                                                  get_string('log_oer', 'local_oer'),
                                                  new moodle_url('/local/oer/views/log.php'),
                                                  'local/oer:manage'));
$ADMIN->add('oersettings', new admin_externalpage('oer_releasehistory',
                                                  get_string('releasehistory', 'local_oer'),
                                                  new moodle_url('/local/oer/views/releasehistory.php'),
                                                  'local/oer:manage'));

if (!function_exists('local_oer_reset_releasestate_if_necessary')) {
    /**
     * When the requirements change, the files that already have been set to release have to be tested against the
     * new requirements and the state has to be set to 0 if the file does not meet the new requirements settings.
     * Does not affect already made releases/snapshots as the requirements had other values back then.
     *
     * Also send a notification to affected users that the requirements have changed and some files have to be revisited.
     *
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    function local_oer_reset_releasestate_if_necessary() {
        global $DB, $USER;
        $files   = $DB->get_records('local_oer_files', ['state' => 1], 'id ASC');
        $courses = [];
        foreach ($files as $file) {
            list($reqarray, $releasable, $release) = \local_oer\helper\requirements::metadata_fulfills_all_requirements($file);
            if (!$release) {
                $courses[$file->courseid][] = $file;
                $file->state                = 0;
                $file->usermodified         = $USER->id;
                $file->timemodified         = time();
                $DB->update_record('local_oer_files', $file);
            }
        }
        if (!empty($courses)) {
            foreach ($courses as $course => $files) {
                // Update 19.10.2022 Christian. Check if file exists, do not send message if not.
                $filelist = \local_oer\filelist::get_course_files($course);
                foreach ($files as $key => $file) {
                    if (!isset($filelist[$file->contenthash])) {
                        unset($files[$key]);
                    }
                }
                if (empty($files)) {
                    continue;
                }
                $coursecontext = context_course::instance($course);
                $sql           = "SELECT u.id FROM {user} u " .
                                 "JOIN {local_oer_userlist} ul ON u.id = ul.userid " .
                                 "JOIN {user_enrolments} ue ON u.id = ue.userid " .
                                 "JOIN {enrol} e ON e.id = ue.enrolid " .
                                 "WHERE ul.type ='allow' AND e.courseid = :courseid";
                $users         = $DB->get_records_sql($sql, ['courseid' => $course]);
                foreach ($users as $userid) {
                    if (has_capability('local/oer:edititems', $coursecontext, $userid->id)) {
                        $user = $DB->get_record('user', ['id' => $userid->id]);
                        \local_oer\message::send_requirementschanged($user, $files, $course);
                    }
                }
            }
        }
    }
}
