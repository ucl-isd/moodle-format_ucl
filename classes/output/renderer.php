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

namespace format_ucl\output;

use context_course;
use core_courseformat\base as course_format;
use core_courseformat\output\section_renderer;
use moodle_url;
use section_info;

/**
 * UCL content class.
 *
 * @package     format_ucl
 * @copyright   2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {
    // Override any necessary renderer method here.

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page.
     *
     * This method is required to enable the inplace section title editor.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Generate the section title to be displayed on the section page, without a link.
     *
     * This method is required to enable the inplace section title editor.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param int|stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }

    /**
     * Renders HTML for the menus to add activities and resources to the current course
     *
     * Renders the ajax control (the link which when clicked produces the activity chooser modal). No noscript fallback.
     *
     * @param stdClass $course
     * @param int $section relative section number (field course_sections.section)
     * @param int $sectionreturn The section to link back to
     * @param array $displayoptions additional display options, for example blocks add
     *     option 'inblock' => true, suggesting to display controls vertically
     * @return string
     */
    function course_section_add_cm_control($course, $section, $sectionreturn = null, $displayoptions = array()) {
        // Check to see if user can add menus.
        if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))
            || !$this->page->user_is_editing()) {
            return '';
        }

        // Load the JS for the modal.
        $this->course_activitychooser($course->id);
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();
        $sectioninfo = $modinfo->get_section_info($section, MUST_EXIST);

        return $this->render_from_template(
            'format_ucl/local/content/divider',
            [
                'id' => $sectioninfo->id,
                'section' => $section,
                'courseid' => $course->id,
                'labelurl' => new moodle_url(
                    '/course/mod.php',
                    [
                        'id' => $course->id,
                        'add' => 'label',
                        'section' => $section,
                        'beforemod' => '0',
                        'sr' => $section,
                    ]
                ),
            ]
        );
    }

    /**
     * Get the updated rendered version of the section navigation.
     *
     * This method will only be used when the course editor requires to get an updated section navigation HTML
     * to perform partial page refresh. It will be used for supporting the course editor webservices.
     *
     *
     * @param course_format $format the course format
     * @param section_info $section the section info
     * @return string the rendered element
     */
    public function course_section_navigation_updated(
        course_format $format,
        section_info $section
    ): string {
        $sectionnavigationclass = $format->get_output_classname('content\\sectionnavigation');
        $sectionselectorclass = $format->get_output_classname('content\\sectionselector');
        $sectionnavigation = new $sectionnavigationclass($format, $section->section);
        $sectionselector = new $sectionselectorclass($format, $sectionnavigation);

        return $this->render($sectionselector);
    }
}
