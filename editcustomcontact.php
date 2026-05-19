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

/**
 * Custom contact form submission page
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */

use format_ucl\local\data\custom_contact;

require_once("../../../config.php");

global $PAGE, $DB;

$courseid = required_param('courseid', PARAM_INT);
$contactid = optional_param('contactid', 0, PARAM_INT);

$course = get_course($courseid);
$params = ['courseid' => $course->id, 'contactid' => $contactid];
$PAGE->set_url('/course/format/ucl/editcustomcontact.php', $params);

require_login($course);
$context = context_course::instance($course->id);
require_capability('format/ucl:editcoursecontacts', $context);
$PAGE->set_context($context);

$redirect = new moodle_url('/course/view.php', ['id' => $course->id]);

$data = ['id' => $contactid];
$customcontact = custom_contact::get_record($data) ?: new format_ucl\local\data\custom_contact();
$customdata = ['persistent' => $customcontact];
$mform = new format_ucl\form\custom_contact_form(
    new moodle_url('/course/format/ucl/editcustomcontact.php', $params),
    $customdata
);

$mform->process();
redirect($redirect);
