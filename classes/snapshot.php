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

use local_oer\helper\requirements;
use local_oer\metadata\coursecustomfield;
use local_oer\metadata\courseinfo;
use local_oer\metadata\coursetofile;
use local_oer\modules\element;
use local_oer\plugininfo\oercourseinfo;
use local_oer\plugininfo\oermod;

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
        $records = $DB->get_records('local_oer_snapshot', ['courseid' => $this->courseid], 'id ASC');
        $result = [];
        foreach ($records as $record) {
            if (isset($result[$record->identifier])) {
                if ($result[$record->identifier]->timecreated <= $record->timecreated) {
                    $result[$record->identifier] = $record;
                }
            } else {
                $result[$record->identifier] = $record;
            }
        }
        return $result;
    }

    /**
     * Create snapshots of all course files that are marked as ready for release.
     *
     * @param int $releasenumber Number of this release.
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function create_snapshot_of_course_files(int $releasenumber) {
        $elements = filelist::get_course_files($this->courseid);
        [$courses, $courseinfo] = $this->get_active_courseinfo_metadata();
        if (!$courseinfo) {
            logger::add($this->courseid, logger::LOGERROR, 'Course does not have courseinfo, maybe all entries are ignored');
            return;
        }

        foreach ($elements as $element) {
            $this->create_file_snapshot($element, $courseinfo, $courses, $releasenumber);
        }
    }

    /**
     * Create a snapshot of the current file metadata.
     *
     * A hash of the metadata is generated and stored in the table. The hash is used to compare
     * the metadata to older versions. When the hash is already stored, nothing has changed, and it does not
     * need to be stored.
     *
     * @param element $element Datastructure to store all relevant information
     * @param array $courseinfo List of all courses (internal and external) linked to this file
     * @param array $courses Courses from local_oer_courseinfo table for this course
     * @param int $releasenumber Number of this release.
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function create_file_snapshot(element $element, array $courseinfo, array $courses, int $releasenumber) {
        global $DB, $USER;
        if (!$element->already_stored()) {
            // File metadata has not been stored yet or course is not editor, so this element can be skipped.
            return;
        }

        [$reqarray, $releasable, $release] = requirements::metadata_fulfills_all_requirements($element);
        $metadata = $element->get_stored_metadata();
        if (!$release) {
            // At least one criterion is not fulfilled, file cannot be released.
            if ($metadata->releasestate == 1) {
                // So the file cannot be released, but the state is release? There has to be an error somewhere - add to log.
                logger::add($this->courseid, logger::LOGERROR, 'Element with identifier ' . $metadata->identifier .
                        ' is set to release, but does not fulfill all requirements.');
            }
            return;
        }

        $decomposed = identifier::decompose($element->get_identifier());
        if ($element->get_type() == element::OERTYPE_MOODLEFILE && $decomposed->valuetype == 'contenthash') {
            $coursemetadata = json_encode($this->get_overwritten_courseinfo_metadata($courseinfo, $decomposed->value, $courses));
        } else {
            $coursemetadata = json_encode($courseinfo);
        }

        $snapshot = new \stdClass();
        $snapshot->courseid = $this->courseid;
        $snapshot->identifier = $element->get_identifier();
        $snapshot->title = $element->get_title();
        $snapshot->description = $metadata->description;
        $snapshot->context = $metadata->context;
        $snapshot->license = $element->get_license();
        $snapshot->persons = $metadata->persons;
        $snapshot->tags = $metadata->tags;
        $snapshot->language = $metadata->language;
        $snapshot->resourcetype = $metadata->resourcetype;
        $snapshot->classification = $metadata->classification;
        $snapshot->coursemetadata = $coursemetadata;
        $snapshot->additionaldata = $this->add_external_metadata();
        $snapshot->type = $element->get_type();
        $snapshot->typedata = $this->add_type_data($element);
        $hash = hash('sha256', json_encode($snapshot));
        $snapshot->releasehash = $hash;
        $snapshot->releasenumber = $releasenumber;
        $snapshot->usermodified = $USER->id;
        $snapshot->timemodified = time();
        $snapshot->timecreated = time();
        $latestrelease = $DB->get_records('local_oer_snapshot',
                ['courseid' => $this->courseid, 'identifier' => $element->get_identifier()],
                'timecreated DESC', '*', 0, 1);
        if (empty($latestrelease) || reset($latestrelease)->releasehash != $snapshot->releasehash) {
            $DB->insert_record('local_oer_snapshot', $snapshot);
            // When a snapshot is created, this element is released and available through webservice.
            // So at this point, it is also necessary to call the set_to_release method in sub-plugins.
            oermod::set_element_to_release($snapshot->courseid, $element);
        }
    }

    /**
     * Prepare the data to be stored in the table field 'typedata'.
     *
     * The different subplugin types can have different data added to the metadata.
     *
     * @param element $element
     * @return string
     * @throws \coding_exception
     */
    private function add_type_data(element $element): string {
        $typedata = [];
        $typedata['source'] = $element->get_source();
        switch ($element->get_type()) {
            case element::OERTYPE_MOODLEFILE:
                $file = $element->get_storedfiles()[0];
                $typedata['mimetype'] = $file->get_mimetype();
                $typedata['filesize'] = $file->get_filesize();
                $typedata['filecreationtime'] = $file->get_timecreated();
                break;
            case element::OERTYPE_EXTERNAL:
                $info = $element->get_information();
                foreach ($info as $value) {
                    if (!is_null($value->get_metadatafield())) {
                        $typedata[$value->get_metadatafield()] = $value->get_raw_data();
                    }
                }
                break;
        }
        return json_encode($typedata);
    }

    /**
     * Metadata subplugins can add additional fields to the file metadata.
     *
     * @return array|null
     * @throws \dml_exception
     */
    private function add_external_metadata(): ?string {
        $activeaggregator = get_config('local_oer', 'metadataaggregator');
        $plugins = oercourseinfo::get_enabled_plugins();
        // @codeCoverageIgnoreStart
        // This code is not reachable without subplugins installed.
        foreach ($plugins as $key => $name) {
            if ($key == $activeaggregator) {
                $frankenstyle = 'oercourseinfo_' . $key;
                $plugin = '\\' . $frankenstyle . '\info';
                return json_encode($plugin::add_metadata_fields($this->courseid));
            }
        }
        // @codeCoverageIgnoreEnd
        return null;
    }

    /**
     * Prepares the course metadata.
     *
     * The course metadata of a course is added to the file metadata.
     *
     * @return array
     * @throws \dml_exception
     */
    private function get_active_courseinfo_metadata(): array {
        global $DB;
        $courses = $DB->get_records('local_oer_courseinfo', ['courseid' => $this->courseid, 'ignored' => 0, 'deleted' => 0]);
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
     * @param array $courseinfo Courseinfo array as created for the whole course
     * @param string $contenthash Moodle file contenthash
     * @param array $courses Courses from local_oer_courseinfo table for this course
     * @return array
     * @throws \dml_exception
     */
    private function get_overwritten_courseinfo_metadata(array $courseinfo, string $contenthash, array $courses) {
        if (!get_config('local_oer', 'coursetofile')) {
            return $courseinfo;
        }

        global $DB;
        $courseinfo = [];

        $remove = $DB->get_records('local_oer_coursetofile', [
                'contenthash' => $contenthash,
                'state' => coursetofile::COURSETOFILE_DISABLED,
        ]);
        $sql = "SELECT * FROM {local_oer_courseinfo} ci " .
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
     * @throws \dml_exception
     */
    private function extract_courseinfo_metadata(\stdClass $course) {
        $info = [
                'identifier' => $course->coursecode,
                'courseid' => $course->external_courseid,
                'sourceid' => $course->external_sourceid,
                'coursename' => $course->coursename,
                'structure' => $course->structure ?? '',
                'description' => $course->description ?? '',
                'objective' => $course->objectives ?? '',
                'organisation' => $course->organisation ?? '',
                'courselanguage' => $course->language ?? '',
                'lecturer' => $course->lecturer ?? '',
        ];

        return $this->add_customfields_to_snapshot($course, $info);
    }

    /**
     * Add customfields to snapshot if enabled and moodlecourse.
     *
     * @param \stdClass $course Course object
     * @param array $info Metadata array to extend
     * @return array
     * @throws \dml_exception
     */
    private function add_customfields_to_snapshot(\stdClass $course, array $info) {
        if ($course->subplugin == courseinfo::BASETYPE
                && strpos($course->coursecode, 'moodlecourse') !== false
                && get_config('local_oer', 'coursecustomfields') == 1) {
            $customfields = coursecustomfield::get_customfields_for_snapshot($course->courseid);
            if (!empty($customfields)) {
                $info['customfields'] = $customfields;
            }
        }
        return $info;
    }
}
