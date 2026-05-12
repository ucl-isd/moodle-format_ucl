<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace format_ucl\output\widgets\assessment;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_module;
use moodle_url;

/**
 * Turnitin assignment handler for UCL Course Format.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class turnitintooltwo extends assess_base {
    /** @var array The different parts of the Turnitin assignment. */
    protected array $parts = [];

    /** @var int|null The specific part number we are looking at. */
    protected ?int $partno = null;

    /**
     * Constructor override to preload Turnitin parts.
     * @param \cm_info $cm
     * @param int|null $partno Optional specific part number to filter by.
     */
    public function __construct(\cm_info $cm, ?int $partno = null) {
        global $DB;
        parent::__construct($cm);

        $this->partno = $partno;

        // A static variable persists for the entire life of the page request.
        static $partscache = [];

        // Only hit the database if we haven't seen this specific Turnitin ID yet.
        if (!isset($partscache[$this->cm->instance])) {
            $partscache[$this->cm->instance] = $DB->get_records(
                'turnitintooltwo_parts',
                ['turnitintooltwoid' => $this->cm->instance],
                'id ASC'
            );
        }

        // Grab the data from the cache (memory) instead of the database.
        $this->parts = $partscache[$this->cm->instance];
    }

    /**
     * Get the due date. Defaults to the first part's date.
     * @return int
     */
    public function get_activity_duedate(): int {
        // If no parts exist, we have no date.
        if (!$this->parts) {
            return 0;
        }

        // Convert to a 0-indexed array to make the partno math easy.
        $parts = array_values($this->parts);

        // If a specific part is requested, use it; otherwise, default to the first part.
        $index = ($this->partno !== null) ? ($this->partno - 1) : 0;

        return (int) ($parts[$index]->dtdue ?? 0);
    }

    /**
     * Get marking data for staff view.
     * @return stdClass
     */
    public function get_staff_marking(): stdClass {
        global $DB;

        $result = new stdClass();
        $result->submitted = 0;
        $result->marked = 0;
        $result->requiremarking = 0;
        $result->hasstats = false;

        if (!$this->parts) {
            return $result;
        }

        $context = context_module::instance($this->cm->id);
        $enrolledjoin = get_enrolled_join($context, 'tts.userid', true);

        $params = array_merge(['instanceid' => (int) $this->cm->instance], $enrolledjoin->params);
        $partsql = "";

        if ($this->partno !== null) {
            $partsql = " AND tts.submission_part = :partno";
            $params['partno'] = $this->partno;
        }

        $submissionsql = "SELECT COUNT(DISTINCT tts.userid)
                            FROM {turnitintooltwo_submissions} tts
                            {$enrolledjoin->joins}
                           WHERE tts.turnitintooltwoid = :instanceid
                             AND tts.submission_hash IS NOT NULL
                             {$partsql}
                             AND {$enrolledjoin->wheres}";

        $result->submitted = $DB->count_records_sql($submissionsql, $params);

        if ($result->submitted > 0) {
            $result->hasstats = true;

            $gradesql = "SELECT COUNT(DISTINCT tts.userid)
                           FROM {turnitintooltwo_submissions} tts
                           {$enrolledjoin->joins}
                          WHERE tts.turnitintooltwoid = :instanceid
                            AND tts.submission_grade IS NOT NULL
                            {$partsql}
                            AND {$enrolledjoin->wheres}";

            $result->marked = $DB->count_records_sql($gradesql, $params);
            $result->requiremarking = max(0, $result->submitted - $result->marked);
        }

        return $result;
    }

    /**
     * Get learner status.
     * @return stdClass
     */
    public function get_learner_mark(): stdClass {
        global $USER, $DB;

        $result = parent::get_learner_mark();

        if (!$this->parts) {
            return $result;
        }

        if (empty($result->mark)) {
            $params = [
                'turnitintooltwoid' => $this->cm->instance,
                'userid' => $USER->id,
            ];

            if ($this->partno !== null) {
                $params['submission_part'] = $this->partno;
            }

            $submission = $DB->get_record('turnitintooltwo_submissions', $params, 'submission_grade DESC', IGNORE_MULTIPLE);

            if ($submission) {
                $result->submitted = true;
                if ($submission->submission_grade !== null) {
                    $result->mark = $submission->submission_grade;
                }
            }
        }

        return $result;
    }
}
