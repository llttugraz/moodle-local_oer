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
 * oer:moodle[at]my.example.at/main/:file:contenthash:abc123cde456
 * oer:opencast[at]example.platform.at:video:id:456
 * oer:moodle[at]example.at/mooc/:course:id:27
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
        if (strlen($identifier) > 255) {
            return false; // Maximum length for database field.
        }
        $params = explode(':', $identifier);
        if (count($params) != 5 || $params[0] != 'oer') {
            return false; // There has to be exactly 5 elements starting with oer.
        }
        $combined = explode('@', $params[1]);
        if (count($combined) != 2) {
            return false;
        }
        $combined[0] = clean_param($combined[0], PARAM_ALPHANUMEXT);
        $domain = explode('/', $combined[1]);
        // When a slash is at the end of the domain name, explode will lead to an empty array entry.
        // This is fine, as the slash will be at the end again on implode and lead to a valid result.
        $domain[0] = preg_replace('/[^A-Za-z0-9\/.-]/i', '', $domain[0]);
        foreach ($domain as $key => $part) {
            if ($key == 0) {
                continue;
            }
            $domain[$key] = preg_replace('/[^A-Za-z0-9\/_-]/i', '', $domain[$key]);
        }
        $combined[1] = implode('/', $domain);

        $params[1] = implode('@', $combined);
        $params[2] = clean_param($params[2], PARAM_ALPHANUMEXT);
        $params[3] = clean_param($params[3], PARAM_ALPHANUMEXT);
        $params[4] = clean_param($params[4], PARAM_ALPHANUMEXT);
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
     * @param string $platform The platform where the resource comes from (e.g. moodle, opencast ...)
     * @param string $instance The domain of the hosted instance of that system (http(s):// is removed)
     * @param string $type The type of the resource (e.g. file, video, course, activity ... )
     * @param string $valuetype The type of the value (id, contenthash ...)
     * @param string $value The value itself
     * @return string
     * @throws \coding_exception
     */
    public static function compose(string $platform, string $instance, string $type, string $valuetype, string $value): string {
        $instance = str_replace(['https://', 'http://'], '', $instance);
        $combined = $platform . '@' . $instance;
        $list = ['oer', $combined, $type, $valuetype, $value];
        $identifier = implode(':', $list);
        self::strict_validate($identifier);
        return $identifier;
    }

    /**
     * Decompose all elements of the identifier to an array. The first part (oer) is not added to the array.
     *
     * @param string $identifier
     * @return \stdClass
     * @throws \coding_exception
     */
    public static function decompose(string $identifier): \stdClass {
        self::strict_validate($identifier);
        $params = explode(':', $identifier);
        $combined = explode('@', $params[1]);
        $decomposed = new \stdClass();
        $decomposed->platform = $combined[0];
        $decomposed->instance = $combined[1];
        $decomposed->type = $params[2];
        $decomposed->valuetype = $params[3];
        $decomposed->value = $params[4];
        return $decomposed;
    }
}
