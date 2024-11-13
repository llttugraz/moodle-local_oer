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
 * @copyright  2024 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../helper/testcourse.php');

use local_oer\modules\module;
use local_oer\plugininfo\oermod;

/**
 * Test oermod class
 *
 * This class also tests the abstract class plugininfo.
 * The other plugininfo classes do not contain any code beside different names, so there will be no test for them.
 * All of these classes share the same methods from the abstract class.
 * Also, two of the oermod sub plugins are always present when installing the local_oer plugin, so these can be used
 * in the tests.
 *
 * Requires oermod_resource to be installed.
 *
 * @coversDefaultClass \local_oer\plugininfo\oermod
 */
final class oermod_test extends \advanced_testcase {
    /**
     * Set up the unit tests.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test get enabled plugins function.
     *
     * @covers \local_oer\plugininfo\plugininfo::get_enabled_plugins
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_get_enabled_plugins(): void {
        $plugins = oermod::get_enabled_plugins();
        // Resource and folder are enabled by default.
        $plugins = oermod::get_enabled_plugins();
        $this->assertCount(2, $plugins);
        $this->assertArrayHasKey('folder', $plugins);
        $this->assertArrayHasKey('resource', $plugins);
    }

    /**
     * Test plugin is enabled method.
     *
     * @covers \local_oer\plugininfo\plugininfo::plugin_is_enabled
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_plugin_is_enabled(): void {
        set_config('enabledmodplugins', 'resource', 'local_oer');
        $enabled = oermod::plugin_is_enabled('resource');
        $this->assertTrue($enabled);
        $enabled = oermod::plugin_is_enabled('folder');
        $this->assertFalse($enabled);
    }

    /**
     * Test get all plugins method.
     *
     * @covers \local_oer\plugininfo\plugininfo::get_all_plugins
     *
     * @return void
     */
    public function test_get_all_plugins(): void {
        $plugins = oermod::get_all_plugins();
        $this->assertGreaterThanOrEqual(2, $plugins, 'There could be more installed, but at least 2 have to.');
        $this->assertArrayHasKey('folder', $plugins);
        $this->assertArrayHasKey('resource', $plugins);
    }

    /**
     * Test is uninstall allowed method.
     *
     * @covers \local_oer\plugininfo\plugininfo::is_uninstall_allowed
     *
     * @return void
     */
    public function test_is_uninstall_allowed(): void {
        $plugininfo = new oermod();
        $this->assertTrue($plugininfo->is_uninstall_allowed());
    }

    /**
     * Test get manage url.
     *
     * @covers \local_oer\plugininfo\plugininfo::get_manage_url
     *
     * @return void
     * @throws \moodle_exception
     */
    public function test_get_manage_url(): void {
        $url = oermod::get_manage_url();
        $compare = '/admin/settings.php?section=localpluginsoermod';
        $this->assertIsObject($url);
        $this->assertInstanceOf('moodle_url', $url);
        $this->assertStringContainsString($compare, $url->out());
    }

    /**
     * Test get settings section name method.
     *
     * @covers \local_oer\plugininfo\plugininfo::get_settings_section_name
     *
     * @return void
     */
    public function test_get_settings_section_name(): void {
        $plugininfo = new oermod();
        $name = $plugininfo->get_settings_section_name();
        $this->assertEquals('oermodsettings', $name);
    }

    /**
     * Test load settings.
     *
     * As the sub plugins do not have a settings.php yet the method will just return. So the result is null.
     *
     * @covers \local_oer\plugininfo\plugininfo::load_settings
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_load_settings(): void {
        global $CFG, $ADMIN;
        require_once($CFG->libdir . '/adminlib.php');
        $this->setAdminUser();
        $ADMIN = new \admin_root(true);
        $ADMIN->add('root', new \admin_category('oersettings',
                new \lang_string('oer_link', 'local_oer'), false));
        $ADMIN->add('root', new \admin_category('localoersubpluginssettings',
                new \lang_string('pluginname', 'local_oer'), true));
        $settings = 'not Null, trying to load the (non-existent) settings will set this variable to null.';
        foreach (\core_plugin_manager::instance()->get_plugins_of_type('oermod') as $plugin) {
            if ($plugin->name == 'resource') {
                $settings = $plugin->load_settings($ADMIN, 'localoersubpluginssettings', true);
            }
        }
        $this->assertNull($settings, 'As the submodules here do not have a settings.php file, the result should be null');
        $this->assertIsObject($ADMIN);
    }

    /**
     * Prepare testdata.
     *
     * @return array
     * @throws \coding_exception
     */
    private function prepare_testcourse_and_elements(): array {
        $this->setAdminUser();
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $elements = oermod::load_elements('resource', $course->id);
        return [$course, $elements];
    }

    /**
     * Test load elements function.
     *
     * @covers ::load_elements
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_load_elements(): void {
        [$course, $elements] = $this->prepare_testcourse_and_elements();
        $this->assertCount(5, $elements, 'For the testcourse 5 resource elements are generated.');
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Convention: Subplugin has to implement module class.');
        oermod::load_elements('noplugin', $course->id);
    }

    /**
     * Test write external metadata.
     *
     * @covers ::write_external_metadata
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_write_external_metadata(): void {
        global $DB;
        [, $elements] = $this->prepare_testcourse_and_elements();
        $element = $elements->get_element_by_key(0);
        $decompose = identifier::decompose($element->get_identifier());
        $files = $DB->get_records('files', ['contenthash' => $decompose->value, 'component' => 'mod_resource']);
        $this->assertCount(1, $files);
        foreach ($files as $file) {
            if ($file->filepath == '.') {
                continue;
            }
            $this->assertEquals('allrightsreserved', $file->license);
        }
        $element->set_license('cc-4.0');
        oermod::write_external_metadata($element);
        $files = $DB->get_records('files', ['contenthash' => $decompose->value, 'component' => 'mod_resource']);
        $this->assertCount(1, $files);
        foreach ($files as $file) {
            if ($file->filepath == '.') {
                continue;
            }
            $this->assertEquals('cc-4.0', $file->license);
        }
    }

    /**
     * Test set element to release.
     *
     * @covers ::set_element_to_release
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_set_element_to_release(): void {
        global $DB;
        [$course, $elements] = $this->prepare_testcourse_and_elements();
        $element = $elements->get_element_by_key(0);
        $this->assertCount(1, $DB->get_records('local_oer_log'));
        oermod::set_element_to_release($course->id, $element);
        $this->assertCount(1, $DB->get_records('local_oer_log'), 'No log entry should be created');
    }

    /**
     * Test get writable fields method.
     *
     * @covers ::get_writable_fields
     * @covers ::get_module
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_get_writable_fields(): void {
        [, $elements] = $this->prepare_testcourse_and_elements();
        $element = $elements->get_element_by_key(3);
        $result = oermod::get_writable_fields($element);
        $this->assertEquals('The metadata for the fields: "Licence" will be stored back to the original source.', $result);
    }

    /**
     * Test get supported licences.
     *
     * @covers ::get_supported_licences
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_get_supported_licenses(): void {
        global $DB;
        [, $elements] = $this->prepare_testcourse_and_elements();
        $element = $elements->get_element_by_key(3);
        $result = oermod::get_supported_licences($element);
        $licences = $DB->get_records('license');
        $this->assertNotEmpty($licences);
        foreach ($licences as $licence) {
            if ($licence->enabled == 0) {
                continue;
            }
            $found = false;
            foreach ($result as $lic) {
                if ($licence->shortname == $lic) {
                    $found = true;
                }
            }
            $this->assertTrue($found);
        }
    }

    /**
     * Test get supported roles.
     *
     * @covers ::get_supported_roles
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_get_supported_roles(): void {
        $compare = [
                ['Author', 'author', 'local_oer', module::ROLE_REQUIRED],
                ['Publisher', 'publisher', 'local_oer'],
        ];
        $roles = oermod::get_supported_roles('oermod_resource\module');
        $this->assertCount(2, $roles);
        $this->assertCount(4, $roles[0]);
        $this->assertCount(3, $roles[1]);
        $this->assertEquals($compare, $roles);
    }
}
