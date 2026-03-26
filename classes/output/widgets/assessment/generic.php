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
 * Generic assessment handler for UCL Course Format.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
class generic extends assess_base {
    /**
     * Generic helper to fetch the due date for a specific user.
     * @return int Unix timestamp or 0.
     */
    public function get_user_duedate(): int {
        // Inherits the customdata check from assess_base.
        return $this->get_activity_duedate();
    }

    /**
     * Generic helper to fetch a mark from the Gradebook.
     * @return \stdClass Object with mark (string|null) and submitted (bool)
     */
    public function get_learner_mark(): \stdClass {
        // Automatically uses the Gradebook API logic from assess_base.
        return parent::get_learner_mark();
    }

    /**
     * Generic helper for staff marking data.
     * @return \stdClass Object with marking data.
     */
    public function get_staff_marking(): \stdClass {
        // Generic activities don't usually have a standard 'needs marking' SQL,
        // so we return the empty object defined in the base.
        return parent::get_staff_marking();
    }
}
