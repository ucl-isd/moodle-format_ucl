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

/**
 * Abstract class for UCL Course Format assessment types.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
abstract class assess_base {
    /**
     * Constructor
     * @param \cm_info $cm
     */
    public function __construct(
        /** @var \cm_info The course module info object */
        protected \cm_info $cm,
    ) {
    }


    /**
     * Helper to get the final duedate for the current user, including overrides.
     * @return int Unix timestamp or 0 if no date is found.
     */
    public function get_activity_duedate(): int {
        global $USER;

        // Fetch user specific dates for a mod (handles overrides and extensions).
        $dates = \core\activity_dates::get_dates_for_module($this->cm, $USER->id);

        if (empty($dates)) {
            return 0;
        }

        // Date IDs treated as the assessment due date across supported activities.
        $dueevents = ['duedate', 'timeclose', 'deadline', 'submissionend', 'assessmentend'];

        foreach ($dates as $date) {
            if (in_array($date['dataid'], $dueevents, true)) {
                return (int) $date['timestamp'];
            }
        }

        return 0;
    }

    /**
     * Static factory method to get the correct handler instance.
     *
     * @param \cm_info $cm
     * @return self
     */
    public static function instance(\cm_info $cm): self {
        $modname = $cm->modname;
        $classname = "\\format_ucl\\output\\widgets\\assessment\\{$modname}";

        // Use class_exists() to check if a specific handler file exists.
        if (!class_exists($classname)) {
            $classname = "\\format_ucl\\output\\widgets\\assessment\\generic";
        }

        return new $classname($cm);
    }
}
