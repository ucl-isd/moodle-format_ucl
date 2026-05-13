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

namespace format_ucl\output\widgets;

use context_course;
use core\output\renderer_base;
use format_ucl\form\custom_contact_form;
use format_ucl\local\data\custom_contact;
use moodle_url;
use stdClass;

/**
 * Course custom contacts.
 *
 * @package     format_ucl
 * @category    upgrade
 * @copyright   2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_contacts extends contacts {
    /**
     * Return data for custom contacts.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $USER;

        $course = $this->format->get_course();
        $context = context_course::instance($course->id);
        $caneditroles = $USER->editing && has_capability('format/ucl:editcoursecontacts', $context);
        $customcontacts = custom_contact::get_records(['courseid' => $course->id]);
        $contacts = [];

        foreach ($customcontacts as $customcontact) {
            $editform = $caneditroles ? self::get_custom_contact_form($course, $output, $customcontact) : null;
            $contact = $customcontact->to_record();
            $contact->contactid = $contact->id;
            $contact->editform = $editform;
            $contacts[] = $contact;
        }

        return $contacts;
    }

    /**
     * Get a form to add/edit custom contacts
     *
     * @param stdClass $course
     * @param \renderer_base $output
     * @param custom_contact|null $customcontact
     * @return array
     */
    public static function get_custom_contact_form(
        stdClass $course,
        \renderer_base $output,
        ?custom_contact $customcontact = null
    ): array {
        $customdata = ['persistent' => $customcontact ?: new custom_contact(0, (object)['courseid' => $course->id])];
        $customcontactform = new custom_contact_form(
            new moodle_url('/course/format/ucl/editcustomcontact.php', ['courseid' => $course->id]),
            $customdata,
            'post',
            '',
            ['class' => 'course-contact-form p-3 border']
        );
        return $customcontactform->export_for_template($output);
    }
}
