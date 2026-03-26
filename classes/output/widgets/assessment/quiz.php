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

namespace format_ucl\output\widgets\assessment;

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz assessment type for UCL Course Format.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
class quiz extends assess_base {
    /**
     * Get the student's mark or submission status.
     */
    public function get_learner_mark(): \stdClass {
        global $DB, $USER;

        $result = parent::get_learner_mark();
        if ($result->mark !== null || $result->submitted) {
            return $result;
        }

        // Fallback: Check for a finished attempt.
        $hasattempt = $DB->record_exists('quiz_attempts', [
        'quiz' => $this->cm->instance,
        'userid' => $USER->id,
        'state' => 'finished',
        ]);

        if ($hasattempt) {
            $result->submitted = true;
        }

        return $result;
    }

    /**
     * Get marking stats for staff.
     */
    public function get_staff_marking(): \stdClass {
        global $DB;
        $stats = new \stdClass();
        $instanceid = $this->cm->instance;

        // A. Total submissions (Finished attempts).
        $stats->submitted = $DB->count_records('quiz_attempts', [
            'quiz' => $instanceid,
            'state' => 'finished',
        ]);

        // B. Items requiring manual grading (e.g., Essay questions).
        // We look for questions inside finished attempts that are in a 'needsgrading' state.
        $sql = "SELECT COUNT(DISTINCT quiza.id)
                FROM {quiz_attempts} quiza
                JOIN {question_attempts} qa ON qa.questionusageid = quiza.uniqueid
                JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
                WHERE quiza.quiz = ? 
                  AND quiza.state = 'finished'
                  AND qas.state = 'needsgrading'";

        $stats->requiremarking = $DB->count_records_sql($sql, [$instanceid]);

        $stats->hasstats = ($stats->submitted > 0);

        return $stats;
    }
}
