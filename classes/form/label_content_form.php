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

namespace format_ucl\form;

use context;
use context_module;
use core_form\dynamic_form;
use moodle_url;
use stdClass;

/**
 * Dynamic form for editing label content inline on a UCL course page.
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class label_content_form extends dynamic_form {
    /**
     * Define the label content editor and action buttons.
     */
    protected function definition() {
        global $CFG;

        $cm = $this->get_cm_record();
        $mform = $this->_form;
        $mform->addElement(
            'editor',
            'introeditor',
            get_string('labeltext', 'label'),
            null,
            [
                'context' => context_module::instance($cm->id),
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'maxbytes' => $CFG->maxbytes,
                'trusttext' => false,
                'noclean' => true,
                'subdirs' => true,
            ]
        );
        $mform->setType('introeditor', PARAM_RAW);
        $mform->addElement('hidden', 'cmid', $cm->id);
        $mform->setType('cmid', PARAM_INT);
        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Return the module context for the label being edited.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $cm = $this->get_cm_record();
        return context_module::instance($cm->id);
    }

    /**
     * Check that the module is a UCL label and the user can manage course activities.
     */
    protected function check_access_for_dynamic_submission(): void {
        $cm = $this->get_cm_record();
        $course = get_course($cm->course);
        if ($course->format !== 'ucl' || $cm->modname !== 'label') {
            throw new \moodle_exception('invalidcourseid');
        }
        require_capability('moodle/course:manageactivities', context_module::instance($cm->id));
    }

    /**
     * Save label content through Moodle's standard module update lifecycle.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');
        $cm = $this->get_cm_record();
        $course = get_course($cm->course);
        [, , , $moduleinfo] = get_moduleinfo_data($cm, $course);
        $moduleinfo->introeditor = $this->get_data()->introeditor;
        $moduleinfo->enableaitools = $cm->enableaitools;
        $moduleinfo->enabledaiactions = $cm->enabledaiactions;
        $aiactions = $cm->enabledaiactions === null
            ? array_fill_keys(array_column(
                \core_ai\manager::get_placement_actions_available(context_module::instance($cm->id), false),
                'action'
            ), 1)
            : json_decode($cm->enabledaiactions, true, 512, JSON_THROW_ON_ERROR);
        foreach ($aiactions as $action => $enabled) {
            $moduleinfo->{'action-' . $action} = $enabled;
        }
        update_moduleinfo($cm, $moduleinfo, $course);

        $label = $DB->get_record('label', ['id' => $moduleinfo->instance], '*', MUST_EXIST);
        $labelhtml = format_module_intro('label', $label, $cm->id, false);

        return [
            'labelhtml' => $labelhtml,
            'hascontent' => !empty($labelhtml),
        ];
    }

    /**
     * Load the current label content and embedded files into the editor draft area.
     */
    public function set_data_for_dynamic_submission(): void {
        global $CFG;

        require_once($CFG->dirroot . '/course/modlib.php');
        $cm = $this->get_cm_record();
        $course = get_course($cm->course);
        [, , , $data] = get_moduleinfo_data($cm, $course);
        $data->cmid = $cm->id;
        $this->set_data($data);
    }

    /**
     * Return the course URL used by editor services such as autosave.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        $cm = $this->get_cm_record();
        $course = get_course($cm->course);
        $url = course_get_format($course)->get_view_url($cm->sectionnum, ['navigation' => true]);
        $url->set_anchor('module-' . $cm->id);
        return $url;
    }

    /**
     * Load the course module addressed by the dynamic form request.
     *
     * @return stdClass
     */
    private function get_cm_record(): stdClass {
        $cmid = $this->optional_param('cmid', 0, PARAM_INT);
        return get_coursemodule_from_id('', $cmid, 0, true, MUST_EXIST);
    }
}
