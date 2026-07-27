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

namespace format_ucl\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use format_ucl\output\widgets\toc;

/**
 * Gets the users progress for a section
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class get_section_progress extends external_api {
    /**
     * Parameters for this webservice function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sectionid' => new external_value(PARAM_INT, 'the section'),
        ]);
    }

    /**
     * Get the users progress for the section
     *
     * @param int $sectionid
     * @return array
     */
    public static function execute(int $sectionid): array {
        global $DB;
        // Clean params.
        ['sectionid' => $sectionid] =
            self::validate_parameters(
                self::execute_parameters(),
                ['sectionid' => $sectionid]
            );

        $courseid = $DB->get_field('course_sections', 'course', ['id' => $sectionid]);
        $course = get_course($courseid);
        $context = \context_course::instance($courseid);
        self::validate_context($context);

        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info_by_id($sectionid);

        $total = 0;
        $completed = 0;
        if ($progress = toc::format_ucl_section_progress($section, $course)) {
            $total = $progress->total;
            $completed = $progress->complete;
        }

        return ['total' => $total, 'completed' => $completed];
    }

    /**
     * Return structure for this webservice function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Number of activities with completion'),
            'completed' => new external_value(PARAM_INT, 'Number of completed activities'),
        ]);
    }
}
