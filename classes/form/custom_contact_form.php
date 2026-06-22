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
use format_ucl\local\data\custom_contact;

/**
 * Custom course contact form
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class custom_contact_form extends \core\form\persistent implements renderable, templatable {
    /** @var string */
    public const DELETE = 'delete';
    /** @var string */
    public const SAVE = 'save';

    /** @var string The fully qualified classname. */
    protected static $persistentclass = custom_contact::class;

    /**
     * Form definition.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $contactid = $this->get_persistent()->get('id');

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'courseid');

        $mform->addElement('html', '<legend>' . get_string('customcontact', 'format_ucl') . '</legend>');
        $mform->addElement('html', '<p class="small">' . get_string('customcontact:desc', 'format_ucl') . '</p>');

        $mform->addElement('html', '<div class="d-flex align-items-center w-100">');

        // Name.
        $attributes = [
            'placeholder' => get_string('name:placeholder', 'format_ucl'),
            'class' => 'm-3 flex-fill',
            'required' => 'required',
        ];
        $mform->addElement('text', 'name', get_string('name', 'format_ucl'), $attributes);
        $mform->setType('name', PARAM_TEXT);

         // Role.
        $attributes = [
            'placeholder' => get_string('role:placeholder', 'format_ucl'),
            'class' => 'flex-fill',
        ];
        $mform->addElement('text', 'role', get_string('role', 'format_ucl'), $attributes);
        $mform->setType('role', PARAM_TEXT);

        // Email.
        $attributes = [
            'placeholder' => get_string('email:placeholder', 'format_ucl'),
            'class' => 'm-3 flex-fill',
            'required' => 'required',
            'pattern' => "[^@\s]+@[^@\s]+\.[^@\s]+", // Email as pattern till moodle gets native input type=email.
        ];
        $mform->addElement('text', 'email', get_string('email'), $attributes);
        $mform->setType('email', PARAM_NOTAGS);

        $mform->addElement('html', '</div>');

        // Description.
        $attributes = [
            'placeholder' => get_string('description:placeholder', 'format_ucl'),
        ];
        $mform->addElement('text', 'description', get_string('description', 'format_ucl'), $attributes);
        $mform->setType('description', PARAM_TEXT);
        $this->set_display_vertical();

        // Custom action buttons to allow delete and cancel.
        $mform->addElement('html', '<div class="d-flex">');

        // Save.
        $options = [
            'customclassoverride' => 'btn btn-primary',
        ];
        $mform->addElement('submit', 'submitbutton', get_string('save'), [], null, $options);

        // Delete.
        if ($contactid) {
            // We will add a second submit button to the form that will be used to delete a contact.
            $mform->registerNoSubmitButton('deletebutton');
            $options = [
                'customclassoverride' => 'btn btn-danger mx-2',
            ];
            $params = [
                'courseid' => $this->get_persistent()->get('courseid'),
                'contactid' => $contactid,
                'action' => self::DELETE,
                'sesskey' => sesskey(),
            ];
            $url = new \moodle_url('/course/format/ucl/editcustomcontact.php', $params);
            $attributes = [
                'formnovalidate' => 'formnovalidate',
                'data-confirmation' => 'modal',
                'data-confirmation-title-str' => json_encode(["customcontact", "format_ucl"]),
                'data-confirmation-content-str' => json_encode(["deletecustomcontact", "format_ucl"]),
                'data-confirmation-yes-button-str' => json_encode(["delete"]),
                'data-confirmation-destination' => $url->out(false),
            ];

            $mform->addElement(
                'submit',
                'deletebutton',
                get_string('delete'),
                $attributes,
                null,
                $options
            );
        }

        $mform->addElement('html', '<div class="ml-auto">');

        // Cancel button - closes the collapse.
        // Target collapse container - if contactid exists, target that specific form, otherwise target the generic form container.
        $targetid = $contactid ? 'ucl-format-customcontact-form-' . $contactid : 'ucl-format-customcontact-form';
        $options = [
            'customclassoverride' => 'btn btn-secondary',
        ];
        $attributes = [
            'data-toggle' => 'collapse',
            'href' => "#$targetid",
            'aria-expanded' => 'false',
            'aria-controls' => '$targetid',
        ];
        $mform->addElement('button', 'cancelbutton', get_string('cancel'), $attributes, $options);

        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');
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

        // Run the data assignment first so $data is defined.
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

        // If validation fails on creation ($data is empty), check the errors here.
        // NB we can't override persistent::validation to save the errors.
        if ($this->is_submitted()) {
            $validationerrors = $this->validation($this->_form->getSubmitValues(), []);
            if (!empty($validationerrors)) {
                foreach ($validationerrors as $field => $error) {
                    notification::error($field . ': ' . $error);
                }
            }
        }

        return false;
    }
}
