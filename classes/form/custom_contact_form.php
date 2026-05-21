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
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
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

        $mform->addElement('html', '<legend>' . get_string('customcontact', 'format_ucl') . '</legend>');
        $mform->addElement('html', '<p class="small">' . get_string('customcontact:desc', 'format_ucl') . '</p>');

        $mform->addElement('html', '<div class="d-flex align-items-center w-100">');

        // Role.
        $attributes = [
            'placeholder' => get_string('role:placeholder', 'format_ucl'),
            'class' => 'm-3 flex-fill',
        ];
        $mform->addElement('text', 'role', get_string('role', 'format_ucl'), $attributes);
        $mform->setType('role', PARAM_TEXT);

        // Name.
        $attributes = [
            'placeholder' => get_string('name:placeholder', 'format_ucl'),
            'class' => 'm-3 flex-fill',
            'required' => 'required',
        ];
        $mform->addElement('text', 'name', get_string('name', 'format_ucl'), $attributes);
        $mform->setType('name', PARAM_TEXT);

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

        // Custom action buttons to allow delete and cancel.
        $mform->addElement('html', '<div class="d-flex">');
        // Save.
        $savebtn = '<button type="submit" name="submitbutton" class="btn btn-primary mr-2">'
            . get_string('save') .
            '</button>';
        $mform->addElement('html', $savebtn);

         // Delete.
        if ($this->get_persistent()->get('id')) {
            // Setup the string configs for confirmation modal.
            $titleparam = json_encode(['deletecheck', 'core', get_string('customcontact', 'format_ucl')]);
            $contentparam = json_encode(['deletecustomcontact', 'format_ucl']);
            $yesparam = json_encode(['delete', 'core']);

            // Delete button with confirm.
            $deletebtn = '<button type="submit" name="deletebutton" formnovalidate
                        value="delete"
                        class="btn btn-danger mx-2"
                        data-confirmation="modal"
                        data-confirmation-title-str="' . s($titleparam) . '"
                        data-confirmation-content-str="' . s($contentparam) . '"
                        data-confirmation-yes-button-str="' . s($yesparam) . '">
                    ' . get_string('delete') . '
                </button>';
            $mform->addElement('html', $deletebtn);
            $mform->registerNoSubmitButton('deletebutton');
        }

        // Cancel button - closes the collapse.
        $mform->addElement('html', '<div class="ml-auto">');

        // Target collapse container - if contactid exists, target that specific form, otherwise target the generic form container.
        $contactid = $this->get_persistent()->get('id');
        $targetid = $contactid ? 'ucl-format-customcontact-form-' . $contactid : 'ucl-format-customcontact-form';

        $cancelbtn = '<a role="button" class="btn btn-secondary" data-toggle="collapse" href="#' . $targetid . '"
            aria-expanded="false" name="cancelbutton"
            aria-controls="' . $targetid . '">'
            . get_string('cancel') .
        '</a>';

         $mform->addElement('html', $cancelbtn);
         $mform->addElement('html', '</div>');

         $mform->addElement('html', '</div>');

         $this->set_display_vertical();
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
    /**
     * Handle form submission
     *
     * @return bool Returns true (save or delete) or false (error/no action)
     */
    public function process(): bool {
        $submitteddata = $this->_form->getSubmitValues();

        // Delete.
        if (!empty($submitteddata['deletebutton'])) {
            $id = isset($submitteddata['id']) ? (int)$submitteddata['id'] : 0;
            if ($id > 0) {
                try {
                    $customcontact = new custom_contact($id);
                    $customcontact->delete();
                    notification::success(get_string('customcontactdeleted', 'format_ucl'));
                    return true;
                } catch (\Exception $e) {
                    notification::error($e->getMessage());
                    return false;
                }
            }
        }

        // Save.
        if ($data = $this->get_data()) {
            $customcontact = $this->get_persistent();
            try {
                $customcontact->from_record($data);
                $customcontact->save();
                notification::success(get_string('changessaved'));
                return true;
            } catch (\Exception $e) {
                notification::error($e->getMessage());
            }
        }

        return false;
    }
}
