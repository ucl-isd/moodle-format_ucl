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

use core\notification;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core_user;
use format_ucl\local\data\custom_contact;

/**
 * Custom course contact form
 *
 * @package    format_ucl
 * @copyright  2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_contact_form extends \core\form\persistent implements renderable, templatable {
    /** @var string The fully qualified classname. */
    protected static $persistentclass = custom_contact::class;

    /**
     * Form definition.
     *
     * @return void
     */
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'courseid');

        $attributes = [
            'placeholder' => get_string('role:placeholder', 'format_ucl'),
            'class' => 'm-3 flex-fill',
        ];
        $mform->addElement('text', 'role', get_string('role', 'format_ucl'), $attributes);
        $mform->setType('role', PARAM_TEXT);
        $mform->addRule('role', '', 'required');

        $attributes = [
            'placeholder' => get_string('name:placeholder', 'format_ucl'),
            'class' => 'm-3 flex-fill',
        ];
        $mform->addElement('text', 'name', get_string('name', 'format_ucl'), $attributes);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', '', 'required');

        $attributes = [
            'placeholder' => get_string('email:placeholder', 'format_ucl'),
            'class' => 'm-3 flex-fill',
        ];
        $mform->addElement('text', 'email', get_string('email'), $attributes);
        $mform->setType('email', core_user::get_property_type('email'));
        $mform->addRule('email', '', 'required');
        $mform->setForceLtr('email');

        $attributes = [
            'placeholder' => get_string('description:placeholder', 'format_ucl'),
        ];
        $mform->addElement('text', 'description', get_string('description', 'format_ucl'), $attributes);
        $mform->setType('description', PARAM_TEXT);
        $mform->addRule('description', '', 'required');

        $this->set_display_vertical();
        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        ob_start();
        $this->display();
        $formhtml = ob_get_contents();
        ob_end_clean();
        $context = [
            'formhtml' => $formhtml,
        ];
        return $context;
    }

    /**
     * Handle form submission
     *
     * @return bool
     */
    public function process(): bool {
        if ($this->is_cancelled()) {
            return true;
        }

        if ($data = $this->get_data()) {
            /** @var custom_contact $customcontact */
            $customcontact = $this->get_persistent();

            try {
                $customcontact->from_record($data);
                $customcontact->save();
                notification::success(get_string('changessaved'));
            } catch (\Exception $e) {
                notification::error($e->getMessage());
            }

            return true;
        }

        return false;
    }
}
