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

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core_course_list_element;
use core_user;
use format_ucl;
use moodle_url;
use stdClass;

/**
 * Contacts for a course.
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
     * Return data for contacts.
     *
     * @return stdClass|array the assessment data
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;

        if (!$course = $this->format->get_course()) {
            return [];
        }

        $course = new core_course_list_element($course);
        $contacts = $course->get_course_contacts();
        $template = new stdClass();

        if (!empty($contacts)) {
            $template->hascontacts = true;

            foreach ($contacts as $c) {
                $contact = new stdClass();
                $contact->id = $c['user']->id;
                $contact->name = $c['username'];
                $contact->role = $c['rolename'];
                $contact->url = new moodle_url('/user/view.php', [ 'id' => $contact->id, 'course' => $course->id]);

                // Image.
                $user = core_user::get_user($contact->id);
                $userpicture = new \user_picture($user);
                $userpicture->link = false;
                $userpicture->alttext = false;
                $userpicture->size = 50;
                $contact->picture = $userpicture->get_url($PAGE)->out(false);

                // Email.
                $contact->email = $user->email;

                // Group by UCL specific roles.
                switch (strtolower($contact->role)) {
                    // N.B. Moodle dosn't like us using the foo.0 syntax in mustache.
                    // So we explicitly set hasfoo.
                    case "leader":
                        $template->leader[] = $contact;
                        $template->hasleader = true;
                      break;
                    case "tutor":
                        $template->tutor[] = $contact;
                        $template->hastutor = true;
                      break;
                    case "course administrator":
                        $template->admin[] = $contact;
                        $template->hasadmin = true;
                      break;
                    default:
                        // Do nothing.
                }

                // Test output - no ucl roles.
                switch (strtolower($contact->role)) {
                    case "teacher":
                        $template->leader[] = $contact;
                        $template->hasleader = true;
                      break;
                    case "teacher":
                        $template->tutor[] = $contact;
                        $template->hastutor = true;
                      break;
                    default:
                        // Do nothing.
                }
            }
        }

        return $template;
    }
}