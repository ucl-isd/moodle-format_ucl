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

namespace format_ucl\output\courseformat;

use context_course;
use core\exception\moodle_exception;
use core_courseformat\output\local\content as content_base;
use format_ucl;
use format_ucl\output\widgets\toc;
use moodle_url;
use stdClass;
use core_course_list_element;
use core_user;

/**
 * UCL content class.
 *
 * @package     format_ucl
 * @copyright   2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {
    /**
     * Returns the output class template path.
     *
     * This method redirects the default template when the course content is rendered.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_ucl/local/main';
    }

    /**
     * Get the context variables
     *
     * @param \renderer_base $output
     * @return mixed
     */
    public function export_for_template(\renderer_base $output) {
        global $USER, $PAGE;

        $PAGE->requires->js_call_amd('format_ucl/mutations', 'init');
        $PAGE->requires->js_call_amd('format_ucl/section', 'init');

        $data = parent::export_for_template($output);
        if (!$data->singlesection) {
            // Something is wrong.
            debugging(
                "UCL Format requires a single section per page layout",
                DEBUG_DEVELOPER
            );
        }

        // We want to use both section navigation and course index.
        $singlesectionnum = $data->sectionreturn;

        /** @var format_ucl $format */
        $format = $this->format;
        $data->courseid = $format->get_course()->id;
        $sectioninfo = $format->get_modinfo()->get_section_info($singlesectionnum);

        // Am i editing?
        $data->isediting = $USER->editing;
        $data->hassummary = (bool)$data->singlesection?->summary?->summarytext;
        $data->addsectiondescurl = new moodle_url(
            '/course/editsection.php',
            ['id' => $sectioninfo->id, 'sr' => $singlesectionnum]
        );
        $data->addsectiondescurl->set_anchor('id_summary_editor');

        // Single section specific data.
        if (isset($data->singlesection) && $singlesectionnum > 0) {
            $data->id = $sectioninfo->id;
            $data->sectionname = $output->container(
                $output->render($format->inplace_editable_render_section_name($sectioninfo, false)),
                attributes: ['data-for' => 'section_title'],
            );

            if ($data->isediting) {
                $data->sectionactions = parent::get_page_header_action($output);
            }
        }

        // TOC layout.
        // TODO.
        $layout = 'toc';
        if ($layout == 'toc') {
            // Table of contents for ucl format.
            $tocwidget = new toc($format);
            $data->toc[] = $tocwidget->export_for_template($output);

            // Get section 0 only for first page.
            // SHAME - Is there a better way to do this?
            if ($singlesectionnum === 0) {
                $data = $this->get_ucl_initialsection($data, $output);
            }
        }

        return $data;
    }

    /**
     * Retrieves the action menu for the page header of the local content section.
     *
     * @param \renderer_base $output The renderer object used for rendering the action menu.
     * @return string|null The rendered action menu HTML, null if page no action menu is available.
     */
    public function get_page_header_action(\renderer_base $output): ?string {
        return '';
    }

    /**
     * Return data for first section.
     *
     * @param stdClass $data
     * @param \renderer_base $output
     * @return stdClass
     */
    public function get_ucl_initialsection(stdClass $data, \renderer_base $output): stdClass {
        $section = $data->singlesection;
        if ($section->num == '0') {
            $section->displayonesection = true; // Magic to stop accordians.

            // Section title.
            $data->sectionname = $section->header->name;
            // Section summary.
            $section->summarytext = $section->summary->summarytext;

            // Single section editing.
            if ($data->isediting) {
                // Edit.
                $params = [
                    'id' => $section->id,
                    'section' => $section->num,
                    'sectionid' => $section->id,
                    'sesskey' => sesskey(),
                ];
                $data->editurl = new moodle_url('/course/editsection.php', $params);
                $data->singleedit = true;
            }

            // Course contacts.
            // TODO - make better!
            $course = $this->format->get_course();
            $courselement = new core_course_list_element($course);
            $contacts = $courselement->get_course_contacts();
            $template = new stdClass();
            $template->contacts = [];
            $allcontacts = [];

            if (!empty($contacts)) {
                foreach ($contacts as $c) {
                    $contact = new stdClass();
                    $userobj = $c['user'];
                    $user = \core_user::get_user($userobj->id, '*', MUST_EXIST);

                    $contact->id = $user->id;
                    $contact->name = $c['username'];
                    $contact->role = $c['rolename'];
                    $contact->email = $user->email;

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

                    // TODO - demo code
                    // Using the mail dispay to hide/show contacts.
                    $contact->hidden = ($user->maildisplay == 0);
                    // If hidden and not editing, don't show.
                    if ($contact->hidden && !$data->isediting) {
                        continue;
                    }

                    $allcontacts[] = $contact;
                }

                // Sort the users A-Z by lastname.
                usort($allcontacts, function ($a, $b) {
                    return strcasecmp($a->lastname, $b->lastname);
                });

                // Contact roles.
                $admins = [];
                $leaders = [];
                $tutors = [];
                $teachers = [];

                foreach ($allcontacts as $c) {
                    $rolename = strtolower($c->role);
                    if ($rolename === 'course administrator') {
                        $admins[] = $c;
                    } else if ($rolename === 'leader') {
                        $leaders[] = $c;
                    } else if ($rolename === 'tutor') {
                        $tutors[] = $c;
                    } else if ($rolename === 'teacher') {
                        $teachers[] = $c;
                    }
                }

                // Merge roles.
                $data->contacts = array_merge($leaders, $tutors, $teachers, $admins);

                if (!empty($data->contacts)) {
                    $data->hascontacts = true;
                    $context = context_course::instance($course->id);
                    // TODO - only allow if course admin or leader for ucl, teacher for open source.
                    $data->caneditroles = has_capability('moodle/role:assign', $context);
                }
            }
            // Set first section to enable adding ucl metadata.
            $data->initialsection = $section;
            $data->beforefirstsectionhtml = $this->get_before_first_section_html($output, $data);
            $data->afterfirstsectionhtml = $this->get_after_first_section_html($output, $data);
        }
        return $data;
    }

    /**
     * Return template data for next visible section - only called by section 0.
     * By default section 0 dosn't have the previous/next to output in the mustache template.
     *
     */
    public function get_ucl_next_section(): stdClass {
        global $COURSE;
        $course = $COURSE;

        $format = course_get_format($course);
        $sections = $format->get_sections();
        $numsections = count($sections);
        $context = context_course::instance($course->id);
        $canviewhidden = has_capability('moodle/course:update', $context);

        // Iterate through sections to see if any are visible.
        $i = 1;
        while ($i < $numsections) {
            $s = $sections[$i];
            if ($s->visible || $canviewhidden) {
                $n = new stdClass();
                $n->nextname = $format->get_section_name($s);
                $n->hasnext = true;
                $n->nexturl = new moodle_url('/course/section.php', ['id' => $s->id]);
                // Section hidden from students.
                if (!$s->visible) {
                    $n->nexthidden = true;
                }
                return $n;
            }
            $i++;
        }
        return new stdClass();
    }

    /**
     * Dispatch hook to allow other plugins to add content before the first section html.
     *
     * @param \renderer_base $output
     * @param array|stdClass $data
     * @return string
     */
    public function get_before_first_section_html(\renderer_base $output, array|stdClass $data): string {
        $course = $this->format->get_course();
        // Dispatch hook to retrieve extra content to add at the start of the section.
        $hook = new \format_ucl\hook\before_first_section_html($output, $data, $course, '');
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        return $hook->get_output();
    }

    /**
     * Dispatch hook to allow other plugins to add content after the first section html.
     *
     * @param \renderer_base $output
     * @param array|stdClass $data
     * @return string
     */
    public function get_after_first_section_html(\renderer_base $output, array|stdClass $data): string {
        $course = $this->format->get_course();
        // Dispatch hook to retrieve extra content to add at the end of the section.
        $hook = new \format_ucl\hook\after_first_section_html($output, $data, $course, '');
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        return $hook->get_output();
    }

    // TODO - best practice - build into format.

    // phpcs:disable moodle.Commenting.InlineComment.InvalidEndChar
    // phpcs:disable moodle.Files.LineLength.TooLong
    // More than 16 sections - not display well on laptops.
    // This course contains unnamed sections - you can improve your course by giving each section a meanigful title.
    // This course contains sections with one or less visbible actitivites - you can imporve your course by re-organising these.
    // This section contains lots of activites without any structure - you can improve this by using lables to structure the content.
    // etc
}
