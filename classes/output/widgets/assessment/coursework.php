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

use mod_coursework\services\submission_figures;
use mod_coursework\models\coursework as coursework_model;

/**
 * Coursework assessment type for UCL Course Format.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
class coursework extends assess_base {
    /**
     * Get marking data for staff view using coursework services.
     * @return \stdClass
     */
    public function get_staff_marking(): \stdClass {
        $stats = new \stdClass();
        $instanceid = $this->cm->instance;

        // TODO - check with David.
        try {
            // Use the coursework service to get submissions for this user.
            $submissions = submission_figures::get_submissions_for_assessor($instanceid);
            $stats->submitted = count($submissions);
            $stats->hasstats = ($stats->submitted > 0);
            if ($stats->hasstats) {
                $stats->requiremarking = submission_figures::calculate_needsgrading_for_assessor($instanceid);
            }
        } catch (\Exception $e) {
            // Fallback if services fail or classes are missing.
            $stats->hasstats = false;
            $stats->submitted = 0;
            $stats->requiremarking = 0;
        }

        return $stats;
    }

    /**
     * Get the student's mark using the Gradebook (shared logic).
     * @return \stdClass
     */
    public function get_learner_mark(): \stdClass {
        global $DB, $USER;

        // First, do the standard Gradebook check from the parent.
        $result = parent::get_learner_mark();

        // If we already have a mark or a 'submitted' status from the gradebook, stop.
        if ($result->mark || $result->submitted) {
            return $result;
        }

        // 2. Fallback: Check coursework submissions directly.
        $sql = "SELECT id FROM {coursework_submissions} 
                WHERE courseworkid = ? AND userid = ? 
                AND (finalisedstatus = 1 OR timesubmitted > 0)";

        if ($DB->record_exists_sql($sql, [$this->cm->instance, $USER->id])) {
            $result->submitted = true;
        }

        return $result;
    }

    /**
     * Override to fetch from coursework table.
     * @return int
     */
    public function get_activity_duedate(): int {
        global $DB;

        $deadline = $DB->get_field('coursework', 'deadline', ['id' => $this->cm->instance]);
        return $deadline ? (int) $deadline : parent::get_activity_duedate();
    }
}
