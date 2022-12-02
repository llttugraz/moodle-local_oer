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
use local_oer\helper\requirements;
use local_oer\metadata\coursetofile;
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
        $files = filelist::get_course_files($this->courseid);
        list($courses, $courseinfo) = $this->get_active_courseinfo_metadata();
        if (!$courseinfo) {
            logger::add($this->courseid, logger::LOGERROR, 'Course does not has courseinfo, maybe all entries are ignored');
            return;
        }

        foreach ($files as $filearray) {
            $file = $filearray[0]['file'];
            $this->create_file_snapshot($file, $courseinfo, $courses);
        }
    }

    /**
     * Create a snapshot of the current file metadata.
     *
     * A hash of the metadata is generated and stored in the table. The hash is used to compare
     * the metadata to older versions. When the hash is already stored, nothing has changed, and it does not
     * need to be stored.
     *
     * @param \stored_file $file       Moodle stored file object
     * @param array        $courseinfo List of all courses (internal and external) linked to this file
     * @param array        $courses    Courses from local_oer_courseinfo table for this course
     * @return void
     * @throws \dml_exception
     */
    private function create_file_snapshot(\stored_file $file, array $courseinfo, array $courses) {
        global $DB, $USER;
        $fileinfo = $DB->get_record('local_oer_files', [
                'courseid'    => $this->courseid,
                'contenthash' => $file->get_contenthash()
        ]);
        if (!$fileinfo) {
            // File metadata has not been stored yet, so this file can be skipped.
            return;
        }
        list($reqarray, $releasable, $release) = requirements::metadata_fulfills_all_requirements($fileinfo);
        if (!$release) {
            // At least one criterion is not fulfilled, file cannot be released.
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
        $snapshot->coursemetadata = json_encode($this->get_overwritten_courseinfo_metadata($courseinfo, $fileinfo->contenthash,
                                                                                           $courses));
        $snapshot->additionaldata = $this->add_external_metadata();
        $hash                     = hash('sha256', json_encode($snapshot));
        $snapshot->releasehash    = $hash;
        $snapshot->usermodified   = $USER->id;
        $snapshot->timemodified   = time();
        $snapshot->timecreated    = time();
        $latestrelease            = $DB->get_records('local_oer_snapshot',
                                                     ['courseid' => $this->courseid, 'contenthash' => $fileinfo->contenthash],
                                                     'timecreated DESC', '*', 0, 1);
        if (empty($latestrelease) || reset($latestrelease)->releasehash != $snapshot->releasehash) {
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
                return json_encode($plugin::add_metadata_fields($this->courseid));
            }
        }
        return null;
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
            $courseinfo[] = $this->extract_courseinfo_metadata($course);
        }
        if (empty($courseinfo)) {
            return [$courses, false];
        }
        // If courseinfo overwrite is disabled, we just need the courseinfo array. But when it is enabled.
        // It is easier to build a new courseinfo array with the DB records.
        return [$courses, $courseinfo];
    }

    /**
     * When the overwritten state for course metadata is active load the changes and update the courseinfo array.
     *
     * @param array  $courseinfo  Courseinfo array as created for the whole course
     * @param string $contenthash Moodle file contenthash
     * @param array  $courses     Courses from local_oer_courseinfo table for this course
     * @return array
     * @throws \dml_exception
     */
    private function get_overwritten_courseinfo_metadata(array $courseinfo, string $contenthash, array $courses) {
        if (!get_config('local_oer', 'coursetofile')) {
            return $courseinfo;
        }

        global $DB;
        $courseinfo = [];

        $remove     = $DB->get_records('local_oer_coursetofile', ['contenthash' => $contenthash,
                                                                  'state'       => coursetofile::COURSETOFILE_DISABLED]);
        $sql        = "SELECT * FROM {local_oer_courseinfo} ci " .
                      "JOIN {local_oer_coursetofile} ctf ON ci.courseid = ctf.courseid AND ci.coursecode = ctf.coursecode " .
                      "WHERE ctf.contenthash = :contenthash AND ctf.state = :state";
        $addcourses = $DB->get_records_sql($sql, ['contenthash' => $contenthash, 'state' => coursetofile::COURSETOFILE_ENABLED]);

        foreach ($courses as $key => $course) {
            foreach ($remove as $rm) {
                if ($course->courseid == $rm->courseid &&
                    $course->coursecode == $rm->coursecode &&
                    $rm->state == coursetofile::COURSETOFILE_DISABLED
                ) {
                    unset($courses[$key]);
                }
            }
        }
        foreach ($courses as $course) {
            $courseinfo[] = $this->extract_courseinfo_metadata($course);
        }
        foreach ($addcourses as $course) {
            $courseinfo[] = $this->extract_courseinfo_metadata($course);
        }
        return $courseinfo;
    }

    /**
     * Extract the necessary metadata for the release.
     *
     * @param \stdClass $course Course record from local_oer_courseinfo table
     * @return array
     */
    private function extract_courseinfo_metadata(\stdClass $course) {
        // TODO: this part will cause a merge conflict with customfields issue.
        return [
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
}
