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

use completion_info;
use context_course;
use core\exception\coding_exception;
use core\exception\moodle_exception;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core_course\external\course_summary_exporter;
use core_course_list_element;
use core_courseformat\base;
use format_ucl;
use format_ucl\course_contacts;
use moodle_url;
use section_info;
use stdClass;

/**
 * Course contacts.
 *
 * @package    format_ucl
 * @copyright  2024 onwards University College London {@link https://www.ucl.ac.uk/}
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
        protected format_ucl $format,
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

        require_once($CFG->dirroot.'/group/lib.php');

        if (empty($CFG->coursecontact)) {
            // There are no course contact roles.
            return [];
        }

        // Course contacts.
        $course = $this->format->get_course();
        $courselement = new core_course_list_element($course);
        $contacts = $courselement->get_course_contacts();
        $allcontacts = [];

        if (!empty($contacts)) {
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
                    $contact->hidden = true;
                } else {
                    $contact->hidden = !groups_is_member($group->id, $contact->id);
                }

                // If hidden and not editing, don't show.
                if ($contact->hidden && !$USER->editing) {
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
        }
        return $allcontacts;
    }
}
