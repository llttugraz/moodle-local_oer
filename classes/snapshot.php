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
 * @copyright  2019 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oer;

use local_oer\helper\license;
use local_oer\plugininfo\oercourseinfo;

/**
 * Class snapshot
 *
 * When files are released, a snapshot of the metadata is stored in an additional table.
 * Multiple entries for the same file are possible. But for the release only the last is relevant.
 * The other entries are a file release history and can be used to compare the differences in metadata fields
 * made on one file.
 */
class snapshot {
    /**
     * @var int Moodle courseid
     */
    private $courseid = null;

    /**
     * Constructor.
     *
     * @param int $courseid Moodle courseid
     */
    public function __construct(int $courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Load the latest file snapshots of a course.
     *
     * @return array
     * @throws \dml_exception
     */
    public function get_latest_course_snapshot() {
        global $DB;
        $files  = $DB->get_records('local_oer_snapshot', ['courseid' => $this->courseid]);
        $result = [];
        foreach ($files as $file) {
            if (isset($result[$file->contenthash])) {
                if ($result[$file->contenthash]->timecreated < $file->timecreated) {
                    $result[$file->contenthash] = $file;
                }
            } else {
                $result[$file->contenthash] = $file;
            }
        }
        return $result;
    }

    /**
     * Load all snapshots of a single file.
     *
     * @param string $contenthash File contenthash
     * @return void
     * @throws \dml_exception
     */
    public function get_file_history($contenthash) {
        global $DB;
        $files = $DB->get_records('local_oer_snapshot', ['courseid' => $this->courseid, 'contenthash' => $contenthash],
                                  'timecreated DESC');
        // TODO.
    }

    /**
     * Create snapshots of all course files that are marked as ready for release.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function create_snapshot_of_course_files() {
        $files      = filelist::get_course_files($this->courseid);
        $courseinfo = $this->get_active_courseinfo_metadata();
        if (!$courseinfo) {
            logger::add($this->courseid, logger::LOGERROR, 'Course does not has courseinfo, maybe all entries are ignored');
            return;
        }

        foreach ($files as $filearray) {
            $file = $filearray[0]['file'];
            $this->create_file_snapshot($file, $courseinfo);
        }
    }

    /**
     * Create a snapshot of the current file metadata.
     *
     * A hash of the metadata is generated and stored in the table. The hash is used to compare
     * the metadata to older versions. When the hash is already stored, nothing has changed and it does not
     * need to be stored.
     *
     * @param \stored_file $file       Moodle stored file object
     * @param array        $courseinfo List of all courses (internal and external) linked to this file
     * @return void
     * @throws \dml_exception
     */
    private function create_file_snapshot(\stored_file $file, array $courseinfo) {
        global $DB, $USER;
        $fileinfo = $DB->get_record('local_oer_files', [
                'courseid'    => $this->courseid,
                'contenthash' => $file->get_contenthash()
        ]);
        if (!$fileinfo) {
            // File metadata has not been stored yet, so this file can be skipped.
            return;
        }
        if (!$this->file_ready_for_release($fileinfo->state, $fileinfo->license, $fileinfo->persons, $fileinfo->context)) {
            // At least one criteria is not fulfilled, file cannot be released.
            if ($fileinfo->state == 1) {
                // So the file cannot be released, but the state is release? There has to be an error somewhere - add to log.
                logger::add($this->courseid, logger::LOGERROR, 'File with hash ' . $fileinfo->contenthash .
                                                               ' is set to release, but does not fulfill all requirements.');
            }
            return;
        }

        $snapshot                 = new \stdClass();
        $snapshot->courseid       = $this->courseid;
        $snapshot->contenthash    = $fileinfo->contenthash;
        $snapshot->title          = $fileinfo->title;
        $snapshot->description    = $fileinfo->description;
        $snapshot->context        = $fileinfo->context;
        $snapshot->license        = $fileinfo->license;
        $snapshot->persons        = $fileinfo->persons;
        $snapshot->tags           = $fileinfo->tags;
        $snapshot->language       = $fileinfo->language;
        $snapshot->resourcetype   = $fileinfo->resourcetype;
        $snapshot->classification = $fileinfo->classification;
        $snapshot->coursemetadata = json_encode($courseinfo);
        $snapshot->additionaldata = $this->add_external_metadata();
        $hash                     = hash('sha256', json_encode($snapshot));
        $snapshot->releasehash    = $hash;
        $snapshot->usermodified   = $USER->id;
        $snapshot->timemodified   = time();
        $snapshot->timecreated    = time();
        if (!$DB->record_exists('local_oer_snapshot', ['releasehash' => $hash])) {
            $DB->insert_record('local_oer_snapshot', $snapshot);
        }
    }

    /**
     * Metadata subplugins can add additional fields to the file metadata.
     *
     * @return array|null
     * @throws \dml_exception
     */
    private function add_external_metadata(): ?string {
        $activeaggregator = get_config('local_oer', 'metadataaggregator');
        $plugins          = oercourseinfo::get_enabled_plugins();
        foreach ($plugins as $key => $name) {
            if ($key == $activeaggregator) {
                $frankenstyle = 'oercourseinfo_' . $key;
                $plugin       = '\\' . $frankenstyle . '\info';
                return json_encode($plugin::add_metadata_fields());
            }
        }
        return null;
    }

    /**
     * Test the release criteria of the file.
     *
     * @param int         $release Release state of file
     * @param string      $license Shortname of license
     * @param string|null $persons List of persons added to file
     * @param int         $context Educational context
     * @return bool
     */
    public static function file_ready_for_release(int $release, string $license, ?string $persons, int $context): bool {
        $licenseobject = license::get_license_by_shortname($license);
        $reqlicense    = license::test_license_correct_for_upload($license) || is_null($licenseobject);
        $reqcontext    = $context > 0;
        $people        = json_decode($persons);
        $reqpersons    = !empty($people->persons);
        $reqrelease    = $release == 1;
        return $reqrelease && $reqpersons && $reqcontext && $reqlicense;
    }

    /**
     * Prepares the course metadata.
     *
     * The course metadata of a course is added to the file metadata.
     *
     * @return array|false
     * @throws \dml_exception
     */
    private function get_active_courseinfo_metadata() {
        global $DB;
        $courses    = $DB->get_records('local_oer_courseinfo', ['courseid' => $this->courseid, 'ignored' => 0, 'deleted' => 0]);
        $courseinfo = [];
        foreach ($courses as $course) {
            $courseinfo[] = [
                    'identifier'     => $course->coursecode,
                    'courseid'       => $course->external_courseid,
                    'sourceid'       => $course->external_sourceid,
                    'coursename'     => $course->coursename,
                    'structure'      => $course->structure ?? '',
                    'description'    => $course->description ?? '',
                    'objective'      => $course->objectives ?? '',
                    'organisation'   => $course->organisation ?? '',
                    'courselanguage' => $course->language ?? '',
                    'lecturer'       => $course->lecturer ?? '',
            ];
        }
        if (empty($courseinfo)) {
            return false;
        }
        return $courseinfo;
    }
}
