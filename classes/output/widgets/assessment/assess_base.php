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
 * Abstract class for UCL Course Format assessment types.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
abstract class assess_base {
    /** @var \cm_info The course module info object */
    protected $cm;

    /**
     * Constructor
     * @param \cm_info $cm
     */
    public function __construct(\cm_info $cm) {
        $this->cm = $cm;
    }

    /**
     * Helper to get the final duedate for the current user, including overrides.
     * @return int Unix timestamp or 0 if no date is found.
     */
    public function get_activity_duedate(): int {
        global $USER;

        // Fetch dates from the Moodle 4.x API (handles overrides and extensions).
        $dates = \core\activity_dates::get_dates_for_module($this->cm, $USER->id);

        if (empty($dates)) {
            return 0;
        }

        // List of labels Moodle uses for the due date of an activity.
        $dueevents = ['duedate', 'timeclose', 'deadline'];

        foreach ($dates as $date) {
            if (in_array($date['dataid'], $dueevents)) {
                return (int) $date['timestamp'];
            }
        }

        return 0;
    }

    /**
     * Get the number of participants expected to submit/attempt the activity.
     * @return int The participant count.
     */
    public function get_participant_count(): int {
        $capabilities = [
            'assign'          => 'mod/assign:submit',
            'quiz'            => 'mod/quiz:attempt',
            'workshop'        => 'mod/workshop:submit',
            'coursework'      => 'mod/coursework:submit',
            'turnitintooltwo' => 'mod/turnitintooltwo:submit',
        ];
        $capability = $capabilities[$this->cm->modname] ?? 'mod/assign:submit';

        $context = \context_course::instance($this->cm->course);
        $users = get_enrolled_users(
            context: $context,
            withcapability: $capability,
            onlyactive: true
        );

        if (empty($users)) {
            return 0;
        }

        // Filter users based on moodle availability restrictions.
        $info = new \core_availability\info_module($this->cm);
        return count($info->filter_user_list($users));
    }

    /**
     * Get marking data for staff view.
     * @return \stdClass Object with: submitted, marked, requiremarking, hasstats (bool).
     */
    abstract public function get_staff_marking(): \stdClass;

    /**
     * Global helper to fetch a mark from the Gradebook.
     * * @return \stdClass Object with mark (string|null) and submitted (bool)
     */
    public function get_learner_mark(): \stdClass {
        global $USER;

        $result = new \stdClass();
        $result->mark = null;
        $result->max = null;
        $result->submitted = false;

        $gradeitems = grade_get_grade_items_for_activity($this->cm, true);
        if (empty($gradeitems)) {
            return $result;
        }

        $gradeitem = reset($gradeitems);

        // Store the max grade, cleaned up.
        if (isset($gradeitem->grademax)) {
            $result->max = floatval(round($gradeitem->grademax, 2));
        }

        $grade = \grade_grade::fetch([
            'itemid' => $gradeitem->id,
            'userid' => $USER->id,
        ]);

        if ($grade) {
            if (!is_null($grade->finalgrade) && !$grade->is_hidden()) {
                $result->mark = floatval(round($grade->finalgrade, 2));
                return $result;
            }
            if (!is_null($grade->rawgrade)) {
                $result->submitted = true;
            }
        }

        return $result;
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
