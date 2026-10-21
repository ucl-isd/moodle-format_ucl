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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace format_ucl\form;

use context;
use context_course;
use core_form\dynamic_form;
use moodle_url;
use stdClass;

/**
 * Inline form for editing a course section summary.
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class section_summary_form extends dynamic_form {
    /**
     * Define the summary editor and save/cancel controls.
     */
    protected function definition() {
        $section = $this->get_section_record();
        $mform = $this->_form;
        $mform->addElement(
            'editor',
            'summary_editor',
            get_string('description'),
            null,
            $this->get_editor_options($section)
        );
        $mform->setType('summary_editor', PARAM_RAW);
        $mform->addElement('hidden', 'sectionid', $section->id);
        $mform->setType('sectionid', PARAM_INT);
        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Return the context for the section being edited.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $section = $this->get_section_record();
        return context_course::instance($section->course);
    }

    /**
     * Check that the section belongs to this format and the user can edit the course.
     */
    protected function check_access_for_dynamic_submission(): void {
        $section = $this->get_section_record();
        $course = $this->get_course_record($section);
        if ($course->format !== 'ucl') {
            throw new \moodle_exception('invalidcourseid');
        }
        require_capability('moodle/course:update', context_course::instance($course->id));
    }

    /**
     * Save the editor content and return the formatted summary for inline display.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/filelib.php');
        $section = $this->get_section_record();
        $course = $this->get_course_record($section);
        $context = context_course::instance($course->id);
        $data = file_postupdate_standard_editor(
            $this->get_data(),
            'summary',
            $this->get_editor_options($section),
            $context,
            'course',
            'section',
            $section->id
        );
        $updatedsection = (object)[
            'summary' => $data->summary,
            'summaryformat' => $data->summaryformat,
            'summarytrust' => $data->summarytrust,
        ];
        course_update_section($course, $section, $updatedsection);

        $summarytext = file_rewrite_pluginfile_urls(
            $updatedsection->summary,
            'pluginfile.php',
            $context->id,
            'course',
            'section',
            $section->id
        );
        $options = (object)[
            'noclean' => true,
            'overflowdiv' => true,
        ];

        return [
            'summaryhtml' => format_text($summarytext, $updatedsection->summaryformat, $options),
            'hassummary' => !empty($updatedsection->summary),
        ];
    }

    /**
     * Load the existing summary and its files into the editor draft area.
     */
    public function set_data_for_dynamic_submission(): void {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $section = $this->get_section_record();
        $data = (object)[
            'sectionid' => $section->id,
            'summary' => $section->summary,
            'summaryformat' => $section->summaryformat,
        ];
        $data = file_prepare_standard_editor(
            $data,
            'summary',
            $this->get_editor_options($section),
            context_course::instance($section->course),
            'course',
            'section',
            $section->id
        );
        $this->set_data($data);
    }

    /**
     * Return the course page URL used by editor services such as autosave.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        $section = $this->get_section_record();
        $course = $this->get_course_record($section);
        return course_get_format($course)->get_view_url($section->section, ['navigation' => true]);
    }

    /**
     * Return Moodle editor options for a section summary.
     *
     * @param stdClass $section
     * @return array
     */
    private function get_editor_options(stdClass $section): array {
        global $CFG;

        return [
            'context' => context_course::instance($section->course),
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'trusttext' => false,
            'noclean' => true,
            'subdirs' => true,
        ];
    }

    /**
     * Load the section addressed by the dynamic form request.
     *
     * @return stdClass
     */
    private function get_section_record(): stdClass {
        global $DB;

        $sectionid = $this->optional_param('sectionid', 0, PARAM_INT);
        return $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);
    }

    /**
     * Load the course containing the section.
     *
     * @param stdClass $section
     * @return stdClass
     */
    private function get_course_record(stdClass $section): stdClass {
        global $DB;

        return $DB->get_record('course', ['id' => $section->course], '*', MUST_EXIST);
    }
}
