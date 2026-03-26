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
 * Intermediate page to redirect to edit section page with new section ID
 *
 * @package   format_ucl
 * @copyright 2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Amanda Doughty <m.doughty@ucl.ac.uk>
 */

require_once("../../../config.php");

global $PAGE, $DB;

$courseid = required_param('course', PARAM_INT);
$sectionnum = optional_param('section', null, PARAM_INT);

$course = get_course($courseid);
$params = ['course' => $course->id, 'section' => $sectionnum];
$PAGE->set_url('/course/format/ucl/newsectionredirect.php', $params);

if (!$section = $DB->get_record('course_sections', $params, '*')) {
    redirect(course_get_url($course));
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:update', $context);

redirect(new moodle_url('/course/editsection.php', ['id' => $section->id]));
