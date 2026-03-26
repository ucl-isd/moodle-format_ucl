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

/**
 * Assignment assessment type for UCL Course Format.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
class assign extends assess_base {
    /**
     * Get marking statistics for the assignment.
     * @return \stdClass
     */
    public function get_staff_marking(): \stdClass {
        global $DB;

        $stats = new \stdClass();
        $stats->hasstats = true;

        // Count total submissions that aren't drafts.
        $stats->submitted = $DB->count_records('assign_submission', [
            'assignment' => $this->cm->instance,
            'status' => 'submitted',
        ]);

        // Count how many have been graded.
        $sql = "SELECT COUNT(g.id) 
                FROM {assign_grades} g
                WHERE g.assignment = :assignid AND g.grade IS NOT NULL AND g.grade >= 0";

        $stats->marked = $DB->count_records_sql($sql, ['assignid' => $this->cm->instance]);
        $stats->requiremarking = max(0, $stats->submitted - $stats->marked);

        return $stats;
    }

    /**
     * Get the specific user's mark and submission status.
     *
     * @return \stdClass Object with: mark (string|null), submitted (bool).
     */
    public function get_learner_mark(): \stdClass {
        global $DB, $USER;

        $result = parent::get_learner_mark();
        if ($result->mark || $result->submitted) {
            return $result;
        }

        // Check assign specific tables for submissions not yet in gradebook.
        $submission = $DB->get_record('assign_submission', [
            'assignment' => $this->cm->instance,
            'userid' => $USER->id,
        ], 'status');

        if ($submission && $submission->status === 'submitted') {
            $result->submitted = true;
        }

        return $result;
    }
}
