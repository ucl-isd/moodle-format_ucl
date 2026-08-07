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

namespace format_ucl\form;

use context;
use context_course;
use core_courseformat\formatactions;
use moodle_exception;
use moodle_url;
use core_form\dynamic_form;

/**
 * Add section modal form
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class addsection_form extends dynamic_form {
    /**
     * Form definition
     */
    protected function definition() {
        $mform  = $this->_form;

        $defaultsectionname = get_string('sectiondefaultname', 'format_ucl');

        $mform->addElement(
            'text',
            'name',
            get_string('sectionname'),
            [
                'placeholder' => $defaultsectionname,
                'size' => 30,
                'maxlength' => 255,
            ],
        );
        $mform->setType('name', PARAM_RAW);
        $mform->setDefault('name', $defaultsectionname);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('hidden', 'courseid', 0);
        $mform->setType('courseid', PARAM_INT);
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $datasource = $this->_customdata ?? $this->_ajaxformdata;

        return context_course::instance($datasource['courseid']);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        $datasource = $this->_customdata ?? $this->_ajaxformdata;
        $context = context_course::instance($datasource['courseid']);
        require_capability('moodle/course:update', $context);
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array
     */
    public function process_dynamic_submission() {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $data = $this->get_data();
        $section = formatactions::section($data->courseid)->create_from_form($data);
        $returnurl = course_get_url($data->courseid, $section, ['sr' => $section->section]);
        return [
            'result' => true,
            'url' => $returnurl->out(false),
            'name' => get_section_name($data->courseid, $section),
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable)
     */
    public function set_data_for_dynamic_submission(): void {
        $datasource = $this->_customdata ?? $this->_ajaxformdata;
        $data = (object)[
            'courseid' => $datasource['courseid'],
        ];
        $this->set_data($data);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $datasource = $this->_customdata ?? $this->_ajaxformdata;

        return course_get_url($datasource['courseid'], $this->optional_param('sectionreturn', 0, PARAM_INT));
    }
}
