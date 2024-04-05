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

namespace local_oer\modules;

use core_calendar\event_mapper_test_proxy;

/**
 * Class information
 *
 * Data-structure for additional information to show in the frontend
 */
class information {
    /**
     * A unique identifier to compare this information to find duplicates.
     *
     * Sha1 hash of parent and name.
     *
     * @var string|null
     */
    private ?string $id = null;

    /**
     * Information with the same area will be joined for the view.
     *
     * @var string
     */
    private string $area = '';

    /**
     * Shown name.
     *
     * @var string
     */
    private string $name = '';

    /**
     * Url to the information.
     *
     * @var string
     */
    private string $url = '';

    /**
     * Bool if this information has an url.
     *
     * @var bool
     */
    private bool $hasurl = false;

    /**
     * Name of the metadata field for release. Only PARAM_ALPHA characters are allowed.
     *
     * Set to null if this field should be ignored.
     *
     * @var string|null
     */
    private ?string $metadatafield = '';

    /**
     * Raw data to be added to the release.
     *
     * @var string
     */
    private string $rawdata = '';

    /**
     * Set the area. Use language strings defined in subplugin, local_oer or moodle core.
     *
     * @param string $identifier Identifier of the language string
     * @param string $component Component where to find that string
     * @return void
     * @throws \coding_exception
     */
    public function set_area(string $identifier, string $component): void {
        $this->area = get_string($identifier, $component);
        $this->set_id();
    }

    /**
     * Set the name of an element.
     *
     * The name is shown to the users in frontend, it will also be added as name/title to the release data.
     *
     * @param string $name Name of the element.
     * @return void
     */
    public function set_name(string $name): void {
        $this->name = $name;
        $this->set_id();
    }

    /**
     * Set url. Boolean hasurl will also be set. Use null to reset url.
     *
     * @param string|null $url
     * @return void
     * @throws \invalid_parameter_exception
     */
    public function set_url(?string $url): void {
        if (is_null($url)) {
            $this->url = '';
            $this->hasurl = false;
            return;
        }
        validate_param($url, PARAM_URL);
        $this->url = $url;
        $this->hasurl = true;
    }

    /**
     * Set id. Private function, will be set as soon as both fields area and name are set.
     *
     * @return void
     */
    private function set_id(): void {
        if (empty($this->area) || empty($this->name)) {
            return;
        }
        $this->id = hash('sha1', $this->area . $this->name);
    }

    /**
     * Getter for id.
     *
     * @return string|null
     */
    public function get_id(): ?string {
        return $this->id;
    }

    /**
     * Getter for area.
     *
     * @return string
     */
    public function get_area(): string {
        return $this->area;
    }

    /**
     * Getter for name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Getter for url.
     *
     * @return string|null
     */
    public function get_url(): ?string {
        return $this->url;
    }

    /**
     * Getter for hasurl.
     *
     * @return bool
     */
    public function get_hasurl(): bool {
        return $this->hasurl;
    }

    /**
     * Set the metadata field name.
     *
     * @param string|null $fieldname
     * @return void
     * @throws \invalid_parameter_exception
     */
    public function set_metadatafield(?string $fieldname): void {
        if (is_null($fieldname)) {
            $this->metadatafield = null;
            return;
        }
        validate_param($fieldname, PARAM_ALPHA);
        $this->metadatafield = $fieldname;
    }

    /**
     * Return the metadata field name.
     *
     * @return ?string
     */
    public function get_metadatafield(): ?string {
        return $this->metadatafield;
    }

    /**
     * Set the raw data.
     *
     * @param string $data
     * @return void
     */
    public function set_raw_data(string $data): void {
        $this->rawdata = $data;
    }

    /**
     * Get the raw data.
     *
     * @return string
     */
    public function get_raw_data(): string {
        return $this->rawdata;
    }

    /**
     * Test if the information is set up correctly.
     *
     * All necessary fields need to be set.
     *
     * @return bool
     */
    public function validate_info(): bool {
        return !(empty($this->id) || empty($this->name) || empty($this->area) ||
                (!is_null($this->metadatafield) && empty($this->rawdata)));
    }
}
