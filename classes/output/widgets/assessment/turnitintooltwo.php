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
    /** @var array All the sub-parts for this Turnitin instance. */
    protected array $parts = [];

    /** @var int|null The specific part we are focusing on, if any. */
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

        // Persist parts so we don't re-query if the same assignment appears multiple times.
        static $partscache = [];

        if (!isset($partscache[$this->cm->instance])) {
            $partscache[$this->cm->instance] = $DB->get_records(
                'turnitintooltwo_parts',
                ['turnitintooltwoid' => $this->cm->instance],
                'id ASC'
            );
        }

        $this->parts = $partscache[$this->cm->instance];
    }

    /**
     * Get the due date. Defaults to the first part's date.
     * @return int
     */
    public function get_activity_duedate(): int {
        if (!$this->parts) {
            return 0;
        }

        // Re-index to 0..N because DB IDs are unpredictable.
        $parts = array_values($this->parts);
        $index = ($this->partno !== null) ? ($this->partno - 1) : 0;

        return (int) ($parts[$index]->dtdue ?? 0);
    }

    /**
     * Get the number of students eligible to submit to this Turnitin activity.
     * Overridden because Turnitin doesn't have a specific 'submit' capability.
     *
     * @return int
     */
    public function get_participant_count(): int {
        $context = context_module::instance(cmid: $this->cm->id);

        // Start with everyone who can see the activity.
        $users = get_enrolled_users(
            context: $context,
            withcapability: 'mod/turnitintooltwo:view',
            onlyactive: true
        );

        if (empty($users)) {
            return 0;
        }

        // Kick out the Tutors and Course Leads from the student count.
        $students = array_filter($users, function ($user) use ($context) {
            return !has_capability(
                capability: 'mod/turnitintooltwo:grade',
                context: $context,
                userid: $user->id
            );
        });

        if (empty($students)) {
            return 0;
        }

        // Filter students to see if they are hidden by groups or restrictions.
        $info = new \core_availability\info_module(cm: $this->cm);
        return count($info->filter_user_list(users: $students));
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

        $context = context_module::instance(cmid: $this->cm->id);
        $enrolledjoin = get_enrolled_join(
            context: $context,
            useridcolumn: 'tts.userid',
            onlyactive: true
        );

        $params = array_merge(['instanceid' => (int) $this->cm->instance], $enrolledjoin->params);
        $partsql = "";

        if ($this->partno !== null) {
            $partsql = " AND tts.submission_part = :partno";
            $params['partno'] = $this->partno;
        }

        // Count unique users with a non-empty file hash (real papers only).
        $submissionsql = "SELECT COUNT(DISTINCT tts.userid)
                            FROM {turnitintooltwo_submissions} tts
                            {$enrolledjoin->joins}
                           WHERE tts.turnitintooltwoid = :instanceid
                             AND tts.submission_hash IS NOT NULL
                             AND tts.submission_hash <> ''
                             {$partsql}
                             AND {$enrolledjoin->wheres}";

        $result->submitted = $DB->count_records_sql(sql: $submissionsql, params: $params);

        if ($result->submitted > 0) {
            $result->hasstats = true;

            // Count how many of those submissions actually have a grade.
            $gradesql = "SELECT COUNT(DISTINCT tts.userid)
                           FROM {turnitintooltwo_submissions} tts
                           {$enrolledjoin->joins}
                          WHERE tts.turnitintooltwoid = :instanceid
                            AND tts.submission_grade IS NOT NULL
                            {$partsql}
                            AND {$enrolledjoin->wheres}";

            $result->marked = $DB->count_records_sql(sql: $gradesql, params: $params);
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

        // Return early if the base class already found a grade or status.
        if ($result->mark || $result->submitted || !$this->parts) {
            return $result;
        }

        $params = [
            'userid'   => (int) $USER->id,
            'instance' => (int) $this->cm->instance,
        ];

        $partsql = "";
        if ($this->partno !== null) {
            $partsql = " AND submission_part = :part";
            $params['part'] = $this->partno;
        }

        // Look for any record for this user that has a valid file hash.
        $sql = "SELECT id 
                  FROM {turnitintooltwo_submissions} 
                 WHERE userid = :userid 
                   AND turnitintooltwoid = :instance 
                   AND submission_hash IS NOT NULL 
                   AND submission_hash <> ''
                   {$partsql}";

        if ($DB->record_exists_sql(sql: $sql, params: $params)) {
            $result->submitted = true;
        }

        return $result;
    }
}
