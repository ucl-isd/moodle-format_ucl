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
     * Get the user due date for assignment.
     * @return int Unix timestamp or 0 if no duedate exists.
     */
    public function get_user_duedate(): int {
        global $DB, $USER;

        $userid = $USER->id;
        $instanceid = $this->cm->instance;
        $courseid = $this->cm->course;
        $overridedate = 0;

        // 1. Check for individual user override.
        $overridedate = (int) $DB->get_field('assign_overrides', 'duedate', [
            'assignid' => $instanceid,
            'userid' => $userid,
        ]);

        // 2. Check for group overrides if no user override exists.
        if (!$overridedate) {
            $usergroups = groups_get_user_groups($courseid, $userid);
            if (!empty($usergroups[0])) {
                [$insql, $inparams] = $DB->get_in_or_equal($usergroups[0]);
                $inparams['assignid'] = $instanceid;

                $sql = "SELECT MAX(duedate) FROM {assign_overrides} 
                        WHERE assignid = :assignid AND groupid $insql";

                $groupdate = (int) $DB->get_value_sql($sql, $inparams);
                $overridedate = max($overridedate, $groupdate);
            }
        }

        // 3. Check for individual teacher-granted extension.
        $extension = (int) $DB->get_field('assign_user_flags', 'extensionduedate', [
            'assignment' => $instanceid,
            'userid' => $userid,
        ]);

        $overridedate = max($overridedate, $extension);

        // 4. Fallback to base duedate if no overrides found.
        if (!$overridedate) {
            $overridedate = $this->get_activity_duedate();
        }

        return ($overridedate > 0) ? $overridedate : 0;
    }

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

        // First, do the standard Gradebook check from the parent.
        $result = parent::get_learner_mark();

        // If we already have a mark or a 'submitted' status from the gradebook, stop.
        if ($result->mark || $result->submitted) {
            return $result;
        }

        // Fallback: Check assign-specific tables for recent submissions not yet in gradebook.
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
