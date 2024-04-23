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
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

/**
 * Test identifier class.
 *
 * @coversDefaultClass \local_oer\identifier
 */
final class identifier_test extends \advanced_testcase {
    /**
     * Test validate.
     *
     * @covers ::validate
     * @return void
     * @throws \coding_exception
     */
    public function test_validate(): void {
        $this->resetAfterTest();

        $success = 'oer:moodle@localhost:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->assertTrue(identifier::validate($success));
        $success = 'oer:moodle@localhost/:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->assertTrue(identifier::validate($success), 'Slash at the end of domain.');
        $success = 'oer:opencast@my-domain.example.local:series:id:6a346175-a3df-448d-8200-81a9299ee9f3';
        $this->assertTrue(identifier::validate($success));
        $fail = 'oer:moodle@http://localhost:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->assertFalse(identifier::validate($fail), 'Protocoll is removed in compose function.');
        $fail = 'moodle@localhost:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->assertFalse(identifier::validate($fail), 'Does not start with oer.');
        $fail = 'oer:moodle@localhost:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def' .
                'd7ed44309ee5a94692bbf5c029f96553fc359defd7ed44309ee5a94692bbf5c029f96553fc359def' .
                'd7ed44309ee5a94692bbf5c029f96553fc359defd7ed44309ee5a94692bbf5c029f96553fc359def' .
                'd7ed44309ee5a94692bbf5c029f96553fc359defd7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->assertFalse(identifier::validate($fail), 'Too long, maximum is 255 characters.');
        $fail = 'oer:opencast@my-domain.example.local:ser.ies:id:6a346175-a3df-448d-8200-81a9299ee9f3';
        $this->assertFalse(identifier::validate($fail), 'Point in series, unsupported character at this position.');
        $success = 'oer:moodle@local-host.root/moodle_instance/:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->assertTrue(identifier::validate($success));
        $fail = 'oer:moodle@local_host.root/moodle_instance/:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->assertFalse(identifier::validate($fail));
        $fail = 'oer:moodle@local-host.root/moodle.instance/:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->assertFalse(identifier::validate($fail));
    }

    /**
     * Test strict_validate.
     *
     * @covers ::strict_validate
     * @return void
     * @throws \coding_exception
     */
    public function test_strict_validate(): void {
        $this->resetAfterTest();
        $success = 'oer:moodle@localhost:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        identifier::strict_validate($success);
        $exception = 'oer:moodle@http://localhost:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Identifier contains not allowed characters: ' . $exception);
        identifier::strict_validate($exception);
    }

    /**
     * Test compose.
     *
     * @covers ::compose
     * @return void
     * @throws \coding_exception
     */
    public function test_compose(): void {
        $this->resetAfterTest();
        $platform = 'moodle';
        $instance = 'localhost.root/moodle_instance/';
        $type = 'file';
        $valuetype = 'contenthash';
        $value = 'd7ed44309ee5a94692bbf5c029f96553fc359def';
        $identifier = identifier::compose($platform, $instance, $type, $valuetype, $value);
        $expected = 'oer:moodle@localhost.root/moodle_instance/:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->assertEquals($expected, $identifier);
        $instance = 'local_host.root/moodle_instance/';
        $exception = 'oer:moodle@local_host.root/moodle_instance/:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Identifier contains not allowed characters: ' . $exception);
        identifier::compose($platform, $instance, $type, $valuetype, $value);
    }

    /**
     * Test decompose
     *
     * @covers ::decompose
     * @return void
     * @throws \coding_exception
     */
    public function test_decompose(): void {
        $this->resetAfterTest();
        $identifier = 'oer:moodle@localhost.root/moodle_instance/:file:contenthash:d7ed44309ee5a94692bbf5c029f96553fc359def';
        $decomposed = identifier::decompose($identifier);
        $this->assertCount(5, (array) $decomposed);
        $this->assertEquals('moodle', $decomposed->platform);
        $this->assertEquals('localhost.root/moodle_instance/', $decomposed->instance);
        $this->assertEquals('file', $decomposed->type);
        $this->assertEquals('contenthash', $decomposed->valuetype);
        $this->assertEquals('d7ed44309ee5a94692bbf5c029f96553fc359def', $decomposed->value);
        $exception = 'oer:moodle@localhost.root/moodle_instance/:file:contenthash:d7ed44309ee5a94692.bbf5c029f96553fc359def';
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Identifier contains not allowed characters: ' . $exception);
        identifier::decompose($exception);
    }
}
