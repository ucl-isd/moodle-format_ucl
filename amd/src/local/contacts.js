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
 * Format ucl course contacts visibility.
 *
 * @module     format_ucl/local/contacts
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';

/**
 * Initialise the course contacts.
 * @param {number} courseid
 */
export let init = (courseid) => {
    document.addEventListener('click', e => {
        if (!e || !e.target || (typeof e.target.closest === "undefined")) {
            return;
        }

        const input = e.target.closest('.custom-switch input');
        if (input) {
            let coursecontact = input.closest('.ucl-format-coursecontact');
            let userid = coursecontact.dataset.userid;
            let action = input.dataset.action;
            if (!userid || !action) {
                return;
            }

            if (toggleCourseContactVisibility(courseid, userid, action)) {
                input.dataset.action = action === 'show' ? 'hide' : 'show';
            } else {
                Notification.addNotification({
                    type: 'error',
                    message: getString('togglevisibilityfailed', 'format_ucl')
                });
            }
        }
    });
};

/**
 * Ajax call to toggle visibility of course contacts
 *
 * @method toggleCourseContactVisibility
 * @param {number} courseid
 * @param {number} userid
 * @param {string} action
 * @return {object} Promise
 */
const toggleCourseContactVisibility = (courseid, userid, action) => {
    const request = {
        methodname: 'format_ucl_set_contact_visibility',
        args: {
            courseid: courseid,
            userid: userid,
            action: action
        },
        fail: Notification.exception
    };

    return Ajax.call([request])[0];
};