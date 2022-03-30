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
     * Constructor.
     *
     * Load settings for ZIP functionality.
     *
     * @throws \dml_exception
     */
    public function __construct() {
        $this->maxpackagesize = get_config('local_oer', 'zipperfilesize');
        $this->extendedfiles  = get_config('local_oer', 'extendedpullservice');
    }

    /**
     * Separate files to package size
     *
     * @param int  $courseid          Moodle courseid
     * @param bool $onlyonepackage    bool to bypass the maxpackagesize setting
     * @param bool $overwriteextended bool to bypass the extended files setting
     * @return array[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function separate_files_to_packages($courseid, $onlyonepackage = false, $overwriteextended = false) {
        $maxpackagesize = $onlyonepackage ? 0 : $this->maxpackagesize;
        $onlyonepackage = $maxpackagesize == 0 ? true : $onlyonepackage;
        $extendedfiles  = $overwriteextended ? 1 : $this->extendedfiles;

        $packages                    = [];
        $info                        = [];
        $info['general']['packages'] = 0;
        $info['general']['fullsize'] = 0;
        $size                        = 0;
        $package                     = 0;

        $release = new release($courseid, $extendedfiles);
        $files   = $release->get_released_files();
        foreach ($files as $filearray) {
            $file     = $filearray['storedfile'];
            $metadata = $filearray['metadata'];
            if (!$metadata) {
                continue;
            }
            $filesize  = $file->get_filesize();
            $filetozip = ['metadata' => $metadata, 'file' => $file];
            if ((($filesize + $size) <= $maxpackagesize) || $onlyonepackage) {
                $size                                         += $filesize;
                $packages[$package][$file->get_contenthash()] = $filetozip;
            } else {
                $size                                         = $filesize;
                $package                                      += 1;
                $packages[$package][$file->get_contenthash()] = $filetozip;
            }

            // Write informations for logging.
            $info[$package]['files']     = isset($info[$package]['files']) ? $info[$package]['files'] + 1 : 1;
            $info[$package]['filesize']  = isset($info[$package]['filesize']) ?
                    $info[$package]['filesize'] + $filesize : $filesize;
            $info[$package]['number']    = $package;
            $info['general']['packages'] = $package;
            $info['general']['fullsize'] += $filesize;
        }

        return [$packages, $info];
    }

    /**
     * ZIP the files
     *
     * @param int   $courseid
     * @param array $package
     * @return false|string
     * @throws \coding_exception
     */
    public function compress_file_package($courseid, $package) {
        global $CFG;
        if (empty($package)) {
            return false;
        }
        $this->tempfolder = 'oer_' . $courseid . '_' . time();
        $files            = $this->prepare_files_to_zip($package);
        $zipper           = get_file_packer('application/zip');
        $zipfile          = $CFG->tempdir . '/' . $this->tempfolder . '/course' . $courseid . '.zip';
        $success          = $zipper->archive_to_pathname($files, $zipfile);
        foreach ($files as $file) {
            unlink($file);
        }

        return $success ? $zipfile : false;
    }

    /**
     * Prepare files
     *
     * @param array $package
     * @return array
     */
    private function prepare_files_to_zip($package) {
        $ziplist = [];
        foreach ($package as $key => $item) {
            $metafile                         = $this->create_metadata_json_temp($key, $item['metadata']);
            $file                             = $item['file'];
            $tempfile                         = $file->copy_content_to_temp($this->tempfolder);
            $filearray                        = explode('/', $tempfile);
            $filearray[count($filearray) - 1] = $key;
            $renamedfile                      = implode('/', $filearray);
            rename($tempfile, $renamedfile);
            $ziplist[$key . '.json'] = $metafile;
            $ziplist[$key]           = $renamedfile;
        }
        return $ziplist;
    }

    /**
     * Create metadata json
     *
     * @param string $contenthash
     * @param array  $metadata
     * @return false|string
     */
    private function create_metadata_json_temp($contenthash, $metadata) {
        $dir = $this->tempfolder;
        if (!$dir = make_temp_directory($dir)) {
            return false;
        }
        $filename = $dir . '/' . $contenthash . '.json';
        if ($tempfile = fopen($filename, 'w')) {
            fputs($tempfile, json_encode($metadata, JSON_UNESCAPED_UNICODE));
            fclose($tempfile);
            return $filename;
        }
        return false;
    }

    /**
     * Delete temp folder
     *
     * @param string $zipfile
     */
    public function delete_temp_folder($zipfile) {
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
    public function download_zip_file(int $courseid) {
        list($packages, $info) = $this->separate_files_to_packages($courseid, true);
        if (empty($packages)) {
            return;
        }
        $file     = $this->compress_file_package($courseid, $packages[0]);
        $fileary  = explode('/', $file);
        $filename = end($fileary);
        send_file($file, $filename, 0, false,
                  false, 'application/zip', true, true);
        $this->delete_temp_folder($file);
    }
}
