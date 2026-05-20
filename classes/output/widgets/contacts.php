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
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core_course_list_element;
use format_ucl;
use format_ucl\course_contacts;
use format_ucl\form\custom_contact_form;
use format_ucl\local\data\custom_contact;
use moodle_url;
use stdClass;

/**
 * Course contacts.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
class contacts implements renderable, templatable {
    /**
     * Constructor
     *
     * @param format_ucl $format
     */
    public function __construct(
        /** @var  format_ucl format */
        private format_ucl $format,
    ) {
    }

    /**
     * Return data for course table of contents.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $USER, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        if (empty($CFG->coursecontact)) {
            // There are no course contact roles.
            return [];
        }

        $course = $this->format->get_course();
        $contacts = $this->get_course_contacts($output);
        $customcontacts = $this->get_course_custom_contacts($output);
        $data = [
            'contacts' => $contacts,
            'customcontacts' => $customcontacts,
            'caneditroles' => $USER->editing,
            'hascontacts' => $contacts || $customcontacts,
            'showcontacts' => $contacts || $customcontacts || $USER->editing,
        ];

        if ($data['caneditroles']) {
            $data['customcontactform'] = self::get_custom_contact_form($course, $output);
        }

        return $data;
    }

    /**
     * Get the enrolled course contacts
     *
     * @param renderer_base $output
     * @return array
     */
    public function get_course_contacts(renderer_base $output): array {
        global $USER, $CFG;

        // Course contacts.
        $course = $this->format->get_course();
        $courselement = new core_course_list_element($course);
        $contacts = $courselement->get_course_contacts();
        $allcontacts = [];

        foreach ($contacts as $c) {
            $roleid = $c['role']->id;
            if (!in_array($roleid, explode(',', $CFG->coursecontact))) {
                // The role is not one of course contact roles.
                continue;
            }

            $contact = new stdClass();
            $userobj = $c['user'];
            $user = \core_user::get_user($userobj->id, '*', MUST_EXIST);

            $contact->id = $user->id;
            $contact->name = $c['username'];
            $contact->roleid = $c['role']->id;
            $contact->role = $c['rolename'];
            $contact->roleshortname = $c['role']->shortname;
            $contact->email = $user->maildisplay == 0 ? null : $user->email;

            // URL.
            $contacturl = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]);
            $contact->url = $contacturl->out(false);

            // Description.
            $contact->description = format_text(
                $user->description,
                $user->descriptionformat,
                ['context' => \context_course::instance($course->id)]
            );

            // Image / Initials.
            $userpicture = new \user_picture($user);
            $userpicture->link = false;
            $userpicture->alttext = false;
            $userpicture->size = 50;
            $contact->picture = $output->render($userpicture);

            // Last name for a-z sorting.
            $contact->lastname = $user->lastname;

            if (!$group = groups_get_group_by_idnumber($course->id, course_contacts::GROUP_IDNUMBER)) {
                $contact->show = false;
            } else {
                $contact->show = groups_is_member($group->id, $contact->id);
            }

            // If hidden and not editing, don't show.
            if (!$contact->show && !$USER->editing) {
                continue;
            }

            $allcontacts[] = $contact;
        }

        // Sort by the users role and then A-Z by lastname.
        usort($allcontacts, function ($a, $b) {
            if ($a->roleid === $b->roleid) {
                return strcasecmp($a->lastname, $b->lastname);
            }
            return $a->roleid <=> $b->roleid;
        });

        return $allcontacts;
    }

    /**
     * Get the custom course contacts
     *
     * @param renderer_base $output
     * @return array
     */
    public function get_course_custom_contacts(renderer_base $output): array {
        global $USER;

        $course = $this->format->get_course();
        $context = context_course::instance($course->id);
        $customcontacts = custom_contact::get_records(['courseid' => $course->id]);
        $contacts = [];

        foreach ($customcontacts as $customcontact) {
            $editform = $USER->editing ? self::get_custom_contact_form($course, $output, $customcontact) : null;
            $contact = $customcontact->to_record();
            $contact->contactid = $contact->id;
            $contact->custom = true;
            $contact->show = true;
            $contact->editform = $editform;
            $contacts[] = $contact;
        }

        return $contacts;
    }

    /**
     * Get a form to add/edit custom contacts
     *
     * @param stdClass $course
     * @param renderer_base $output
     * @param custom_contact|null $customcontact
     * @return array
     */
    public static function get_custom_contact_form(
        stdClass $course,
        renderer_base $output,
        ?custom_contact $customcontact = null
    ): array {
        $customdata = ['persistent' => $customcontact ?: new custom_contact(0, (object)['courseid' => $course->id])];
        $customcontactform = new custom_contact_form(
            new moodle_url('/course/format/ucl/editcustomcontact.php', ['courseid' => $course->id]),
            $customdata,
            'post',
            '',
        );
        return $customcontactform->export_for_template($output);
    }
}
