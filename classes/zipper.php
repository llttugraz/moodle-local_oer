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
 * @copyright  2019-2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Class zipper
 */
class zipper {
    /**
     * @var null
     */
    private $tempfolder = null;

    /**
     * Separate files to package size
     *
     * @param int $courseid Moodle courseid
     * @param bool $onlyonepackage bool to bypass the maxpackagesize setting
     * @return array[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function separate_files_to_packages(int $courseid, bool $onlyonepackage = false): array {
        $maxpackagesize = $onlyonepackage ? 0 : get_config('local_oer', 'zipperfilesize');
        $onlyonepackage = $maxpackagesize == 0 || $onlyonepackage;

        $packages = [];
        $info = [];
        $info['general']['packages'] = 0;
        $info['general']['fullsize'] = 0;
        $size = 0;
        $package = 0;

        $files = release::get_released_files_for_course($courseid, 'v2.0.0');
        foreach ($files as $filearray) {
            $metadata = $filearray['metadata'];
            if (!$metadata) {
                continue;
            }
            $file = filelist::get_single_file($courseid, $metadata['identifier']);
            $filesize = $file->get_filesize();
            $filetozip = ['metadata' => $metadata, 'file' => $file];
            if ((($filesize + $size) <= $maxpackagesize) || $onlyonepackage) {
                $size += $filesize;
                $packages[$package][$file->get_identifier()] = $filetozip;
            } else {
                $size = $filesize;
                $package += 1;
                $packages[$package][$file->get_identifier()] = $filetozip;
            }

            // Write information for logging.
            $info[$package]['files'] = isset($info[$package]['files']) ? $info[$package]['files'] + 1 : 1;
            $info[$package]['filesize'] = isset($info[$package]['filesize']) ?
                    $info[$package]['filesize'] + $filesize : $filesize;
            $info[$package]['number'] = $package;
            $info['general']['packages'] = $package;
            $info['general']['fullsize'] += $filesize;
        }

        return [$packages, $info];
    }

    /**
     * ZIP the files
     *
     * @param int $courseid
     * @param array $package
     * @return string|null
     * @throws \coding_exception
     * @throws \file_exception
     */
    public function compress_file_package(int $courseid, array $package): ?string {
        global $CFG;
        if (empty($package)) {
            return null;
        }
        $this->tempfolder = 'oer_' . $courseid . '_' . time();
        $files = $this->prepare_files_to_zip($package);
        $zipper = get_file_packer('application/zip');
        $zipfile = $CFG->tempdir . '/' . $this->tempfolder . '/course' . $courseid . '.zip';
        $success = $zipper->archive_to_pathname($files, $zipfile);
        foreach ($files as $file) {
            unlink($file);
        }

        return $success ? $zipfile : null;
    }

    /**
     * Prepare files
     *
     * @param array $package
     * @return array
     * @throws \file_exception
     */
    private function prepare_files_to_zip(array $package): array {
        $ziplist = [];
        foreach ($package as $key => $item) {
            $hash = hash('sha1', $key); // Slashes are not allowed in filenames, that leads to unexpected folders.
            $metafile = $this->create_metadata_json_temp($key, $item['metadata']);
            $element = $item['file'];
            $file = $element->get_storedfiles()[0]; // Only one file is needed here.
            $tempfile = $file->copy_content_to_temp($this->tempfolder);
            $filearray = explode('/', $tempfile);
            $filearray[count($filearray) - 1] = $hash;
            $renamedfile = implode('/', $filearray);
            rename($tempfile, $renamedfile);
            $ziplist[$hash . '.json'] = $metafile;
            $ziplist[$hash] = $renamedfile;
        }
        return $ziplist;
    }

    /**
     * Create metadata json
     *
     * @param string $identifier
     * @param array $metadata
     * @return string
     * @throws \file_exception
     */
    private function create_metadata_json_temp(string $identifier, array $metadata): string {
        $dir = $this->tempfolder;
        if (!$dir = make_temp_directory($dir)) {
            throw new \file_exception("Could not create directory in tempfolder");
        }
        $hashed = hash('sha1', $identifier);
        $filename = $dir . '/' . $hashed . '.json';
        if ($tempfile = fopen($filename, 'w')) {
            fputs($tempfile, json_encode($metadata, JSON_UNESCAPED_UNICODE));
            fclose($tempfile);
            return $filename;
        }
        throw new \file_exception("Something went wrong, could not create json temp file.");
    }

    /**
     * Delete temp folder
     *
     * @param string $zipfile
     * @return void
     */
    public function delete_temp_folder(string $zipfile): void {
        global $CFG;
        unlink($zipfile);
        rmdir($CFG->tempdir . '/' . $this->tempfolder);
    }

    /**
     * Download file(s) in GUI
     *
     * @param int $courseid
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function download_zip_file(int $courseid): void {
        [$packages] = $this->separate_files_to_packages($courseid, true);
        $file = $this->compress_file_package($courseid, $packages[0]);
        if (is_null($file)) {
            throw new \Exception("Failed to zip elements for course $courseid");
        }
        $fileary = explode('/', $file);
        $filename = end($fileary);
        send_file($file, $filename, 0, false,
                false, 'application/zip', true, true);
        $this->delete_temp_folder($file);
    }
}
