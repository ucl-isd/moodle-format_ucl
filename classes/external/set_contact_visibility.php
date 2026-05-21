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

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use format_ucl\course_contacts;

require_once($CFG->dirroot . '/group/lib.php');

/**
 * Adds/removes a contact from a group that controls which course contacts are visible
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class set_contact_visibility extends external_api {
    /**
     * Parameters for this webservice function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'the course the contact is enrolled on'),
            'userid' => new external_value(PARAM_INT, 'the user to update'),
            'action' => new external_value(PARAM_TEXT, 'the visibility - show or hide'),
        ]);
    }

    /**
     * Toggle visibility of course contact
     *
     * @param int $courseid
     * @param int $userid
     * @param string $action
     * @return bool
     */
    public static function execute(int $courseid, int $userid, string $action): bool {
        // Clean params.
        ['courseid' => $courseid, 'userid' => $userid, 'action' => $action] =
            self::validate_parameters(
                self::execute_parameters(),
                ['courseid' => $courseid, 'userid' => $userid, 'action' => $action]
            );

        $context = \context_course::instance($courseid);
        self::validate_context($context);

        // Verify course contacts can be edited.
        require_capability('format/ucl:editcoursecontacts', $context);

        // Update the course contact.
        $coursecontacts = new course_contacts($courseid, $userid, $action);
        return $coursecontacts->set_contact_visibility();
    }

    /**
     * Return structure for this webservice function.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success');
    }
}
