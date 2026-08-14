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

/**
 * Javascript module for adding a section with a name
 *
 * @module      format_ucl/addsection
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */

import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import {getString} from 'core/str';

/**
 * Initialize module
 */
export const init = () => {
    const triggerElement = document.querySelector('.behat-add-section');

    triggerElement.addEventListener('click', event => {
        event.preventDefault();

        const args = {
            courseid: triggerElement.dataset.courseid,
            sectionreturn: triggerElement.dataset.sectionreturn,
        };

        const modalForm = new ModalForm({
            modalConfig: {
                title: getString('addnewsection', 'format_ucl'),
            },
            formClass: 'format_ucl\\form\\addsection_form',
            saveButtonText: getString('add', 'format_ucl'),
            returnFocus: triggerElement,
            args: args,
        });

        // Redirect to the new section when the form is submitted.
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, event => {
            if (event.detail.result) {
                window.location.assign(event.detail.url);
            } else {
                const warningMessages = event.detail.warnings.map(warning => warning.message);
                Notification.addNotification({
                    type: 'error',
                    message: warningMessages.join('<br>')
                });
            }
        });

        modalForm.show();
    });
};