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
use workshop as workshop_instance;

/**
 * Workshop assessment handler for UCL Course Format.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workshop extends assess_base {
    /**
     * Get marking data for staff view.
     * * @return stdClass Object with: submitted, marked, requiremarking, hasstats.
     */
    public function get_staff_marking(): stdClass {
        global $DB;

        $result = new stdClass();
        $result->submitted = 0;
        $result->marked = 0;
        $result->requiremarking = 0;
        $result->hasstats = false;

        // Workshops don't have a "marking" count like assignments.
        // We look for the number of submissions vs the number of assessments done.
        $submissions = $DB->count_records('workshop_submissions', [
            'workshopid' => $this->cm->instance,
            'example' => 0,
        ]);

        if ($submissions > 0) {
            $result->submitted = $submissions;
            $result->hasstats = true;

            // Count how many assessments have been graded by peers or teachers.
            $sql = "SELECT COUNT(a.id)
                      FROM {workshop_assessments} a
                      JOIN {workshop_submissions} s ON s.id = a.submissionid
                     WHERE s.workshopid = ? AND a.grade IS NOT NULL";

            $result->marked = $DB->count_records_sql($sql, [$this->cm->instance]);
            $result->requiremarking = max(0, $result->submitted - $result->marked);
        }

        return $result;
    }
}
