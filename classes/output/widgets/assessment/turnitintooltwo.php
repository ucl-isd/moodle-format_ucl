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

/**
 * Turnitin assignment handler for UCL Course Format.
 * Dosn't support multi-part things.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class turnitintooltwo extends assess_base {
    /** @var array The different parts of the Turnitin assignment. */
    protected $parts = [];

    /**
     * Constructor override to preload Turnitin parts.
     * @param \cm_info $cm
     */
    public function __construct(\cm_info $cm) {
        global $DB;
        parent::__construct($cm);

        $this->parts = $DB->get_records(
            'turnitintooltwo_parts',
            ['turnitintooltwoid' => $this->cm->instance],
            'partid ASC'
        );
    }

    /**
     * We don't support multi-part things.
     * @return bool
     */
    public function is_valid(): bool {
        return count($this->parts) === 1;
    }

    /**
     * Get the due date.
     * @return int
     */
    public function get_activity_duedate(): int {
        if (!$this->is_valid()) {
            return 0;
        }
        $firstpart = reset($this->parts);
        return (int) $firstpart->dtdue;
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

        // If not a single-part assignment, we don't provide stats.
        if (!$this->is_valid()) {
            return $result;
        }

        $context = \context_module::instance($this->cm->id);
        $enrolledjoin = get_enrolled_join($context, 'tts.userid', true);
        $params = array_merge(['instanceid' => (int) $this->cm->instance], $enrolledjoin->params);

        $submissionsql = "SELECT COUNT(DISTINCT tts.userid)
                            FROM {turnitintooltwo_submissions} tts
                            {$enrolledjoin->joins}
                           WHERE tts.turnitintooltwoid = :instanceid
                             AND tts.submission_hash IS NOT NULL
                             AND {$enrolledjoin->wheres}";

        $result->submitted = $DB->count_records_sql($submissionsql, $params);

        if ($result->submitted > 0) {
            $result->hasstats = true;

            $gradesql = "SELECT COUNT(DISTINCT tts.userid)
                           FROM {turnitintooltwo_submissions} tts
                           {$enrolledjoin->joins}
                          WHERE tts.turnitintooltwoid = :instanceid
                            AND tts.submission_grade IS NOT NULL
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

        if (!$this->is_valid()) {
            return $result;
        }

        if (!$result->mark) {
            $submitted = $DB->record_exists('turnitintooltwo_submissions', [
                'turnitintooltwoid' => $this->cm->instance,
                'userid' => $USER->id,
            ]);
            if ($submitted) {
                $result->submitted = true;
            }
        }

        return $result;
    }
}
