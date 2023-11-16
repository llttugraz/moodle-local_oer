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
 * Class identifier
 *
 * Can compose, validate and decompose identifiers.
 *
 * An identifier has been defined as a colon separated tuple consisting of:
 * - oer : identifier name
 * - system@platform : type of system (e.g. moodle), full domain without protocoll
 * - type : type of identifier (e.g. file, video)
 * - valuetype: type of value (e.g. id, contenthash)
 * - value: id, contenthash ... itself
 *
 * The definition is based on urn.
 *
 * Examples:
 * oer:moodle@my.example.at/main/:file:contenthash:ABC123CDE456
 * oer:opencast@example.platform.at/:video:id:456
 * oer:moodle@example.at/mooc/:course:id:27
 */
class identifier {
    /**
     * Validate an identifier. Returns true on success.
     *
     * @param string $identifier
     * @return bool
     * @throws \coding_exception
     */
    public static function validate(string $identifier): bool {
        $params = explode(':', $identifier);
        if (count($params) != 6) {
            return false;
        }
        $params[1] = clean_param($params[1], PARAM_ALPHANUMEXT);
        $params[2] = preg_replace('/[^A-Za-z0-9\/._-]/i', '', (string) $params[2]);
        $params[3] = clean_param($params[3], PARAM_ALPHANUMEXT);
        $params[4] = clean_param($params[4], PARAM_ALPHANUMEXT);
        $params[5] = clean_param($params[5], PARAM_ALPHANUMEXT);
        $cleaned = implode(':', $params);
        return $identifier === $cleaned;
    }

    /**
     * Strict validate throws an exception if the identifier is not valid.
     *
     * @param string $identifier
     * @return void
     * @throws \coding_exception
     */
    public static function strict_validate(string $identifier): void {
        if (!self::validate($identifier)) {
            throw new \coding_exception('Identifier contains not allowed characters: ' . $identifier);
        }
    }

    /**
     * Create an identifier as defined in the class header.
     *
     * @param string $system The system where the resource comes from (e.g. moodle, opencast ...)
     * @param string $platform The domain of the hosted instance of that system (http(s):// is removed)
     * @param string $type The type of the resource (e.g. file, video, course, activity ... )
     * @param string $valuetype The type of the value (id, contenthash ...)
     * @param string $value The value itself
     * @return string
     * @throws \coding_exception
     */
    public static function compose(string $system, string $platform, string $type, string $valuetype, string $value): string {
        $platform = str_replace(['https://', 'http://'], '', $platform);
        $list = ['oer', $system, $platform, $type, $valuetype, $value];
        $identifier = implode(':', $list);
        self::strict_validate($identifier);
        return $identifier;
    }

    /**
     * Decompose all elements of the identifier to an array. The first part (oer) is not added to the array.
     *
     * @param string $identifier
     * @return array
     * @throws \coding_exception
     */
    public static function decompose(string $identifier): array {
        self::strict_validate($identifier);
        $params = explode(':', $identifier);
        return [
                'system' => $params[1],
                'platform' => $params[2],
                'type' => $params[3],
                'valuetype' => $params[4],
                'value' => $params[5],
        ];
    }
}
