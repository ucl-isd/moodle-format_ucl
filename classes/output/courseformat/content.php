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
use core_courseformat\output\local\content as content_base;
use format_ucl;
use format_ucl\output\widgets\assessments;
use format_ucl\output\widgets\sectionactions;
use format_ucl\output\widgets\toc;
use moodle_url;
use stdClass;

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

        // We want to use both section navigation and course index.
        $singlesectionnum = $this->format->get_sectionnum();

        /** @var format_ucl $format */
        $format = $this->format;
        $course = $format->get_course();

        // Am i editing?
        $data->isediting = $USER->editing;

        // Single section specific data.
        if (isset($data->singlesection) && $singlesectionnum > 0) {
            $sectioninfo = $format->get_modinfo()->get_section_info($singlesectionnum);
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
     * @param $data
     * @param \renderer_base $output
     * @return stdClass
     */
    public function get_ucl_initialsection($data, \renderer_base $output): stdClass {
        $section = $data->singlesection;
        // TODO - does this actually improve speed? - This will be an empty array.
        $data->sections = ''; // Remove the rest of the data, not needed.
        if ($section->num == '0') {
            $section->displayonesection = true; // Magic to stop accordians.

            // Section title.
            $data->sectionname = $section->header->name;
            // Summary.
            $data->initialsectionsummary = $this->get_ucl_initialsection_summary_text($section);
            $section->summary = ""; // We output this as initialsectionsummary in a different place.

            // Add next section for next/previous section template.
            if ($next = $this->get_ucl_next_section()) {
                $data->sectionselector = $next;
            }

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

            // Set first section to enable adding ucl metadata.
            $data->initialsection = $section;

            // phpcs:disable Squiz.PHP.CommentedOutCode.Found
            // Assessments.
            // $assessmentswidget = new assessments($this->format);
            // $data->assessments = $assessmentswidget->export_for_template($output);

            // Contacts.
            // $data->contacts = contacts::course_contacts_list();
        }
        return $data;
    }

    /**
     * SHAME - copied from
     * moodle/course/format/classes/output/local/content/section/summary.php
     * Generate html for first section summary.
     *
     * @param stdClass $section
     *
     */
    public function get_ucl_initialsection_summary_text(stdClass $section): string {
        global $COURSE;
        $context = context_course::instance($COURSE->id);
        $summarytext = file_rewrite_pluginfile_urls(
            $section->summary->summarytext,
            'pluginfile.php',
            $context->id,
            'course',
            'section',
            $section->id
        );

        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
        return format_text($summarytext, FORMAT_HTML, $options);
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

    // TODO - best practice - build into format.

    // phpcs:disable moodle.Commenting.InlineComment.InvalidEndChar
    // More than 16 sections - not display well on laptops.
    // This course contains unnamed sections - you can improve your course by giving each section a meanigful title.
    // This course contains sections with one or less visbible actitivites - you can imporve your course by re-organising these.
    // This section contains lots of activites without any structure - you can improve this by using lables to structure the content.
    // etc
}
