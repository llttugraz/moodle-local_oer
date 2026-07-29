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
 * Multilang helper.
 *
 * @package    local_oer
 * @copyright  2026 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer\helper;

/**
 * Helper for formatting text in a forced language.
 */
class multilang {
    /**
     * Temporarily forces the session language while executing the callback.
     *
     * @param string $lang User language
     * @param array $mlanglangs Languages available in multilang tags
     * @param callable $callback Callback to execute while language is forced
     * @return string
     */
    public static function with_forced_language(string $lang, array $mlanglangs, callable $callback): string {
        global $SESSION;

        $tmp = '';
        if (property_exists($SESSION, 'forcelang')) {
            $tmp = $SESSION->forcelang;
        }

        if (!in_array($lang, $mlanglangs) && $lang !== 'de') {
            $lang = 'en';
        }

        $SESSION->forcelang = $lang;

        $result = $callback();

        if (!empty($tmp)) {
            $SESSION->forcelang = $tmp;
        } else {
            unset($SESSION->forcelang);
        }

        return $result;
    }

    /**
     * Returns the provided language blocks via filter_mlang2 in the given text.
     *
     * @param string $text Text to inspect
     * @return array
     */
    public static function extract_provided_languages_in_text_via_filter_mlang2(string $text): array {
        preg_match_all('/\{mlang\s+([^}]+)\}/i', $text, $m);
        $langs = [];

        foreach ($m[1] as $tag) {
            foreach (preg_split('/\s*,\s*/', trim($tag)) as $code) {
                if ($code !== '') {
                    $code = strtolower($code) == 'other' ? 'en' : $code;
                    $langs[strtolower($code)] = true;
                }
            }
        }

        return array_keys($langs);
    }

    /**
     * Adds additional info based on availability of multilang2 filter.
     *
     * @param string $basestring Base description
     * @return string
     * @throws \coding_exception
     */
    public static function apply_additional_info_mlang2(string $basestring): string {
        return self::is_filter_multilang2_enabled() ?
            $basestring . PHP_EOL . PHP_EOL . get_string('multilang_info', 'local_oer') : $basestring;
    }

    /**
     * Returns if filter multilang2 is installed and enabled.
     *
     * @return bool
     */
    private static function is_filter_multilang2_enabled(): bool {
        static $result = null;
        if ($result === null) {
            $result = in_array('multilang2', \core_plugin_manager::instance()->get_enabled_plugins('filter'));
        }
        return $result;
    }
}
