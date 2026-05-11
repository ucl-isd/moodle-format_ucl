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
 * Mobile TOC modal handler for the UCL course format.
 *
 * @module     format_ucl/mobiletoc
 * @copyright  2026 UCL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    return {
        /**
         * Initialise the mobile TOC logic.
         */
        init: function() {
            // SHAME - jQuery modal because of Bootstrap's requirements.
            const modalElement = $('#ucl-format-mobile-toc');

            // Standard JS for the rest.
            const modalBody = document.getElementById('ucl-format-modal-toc-body');
            const mobileFab = document.getElementById('ucl-format-mobile-toc-fab');
            const tocNav = document.getElementById('ucl-format-toc');
            const tocParent = tocNav.parentElement;

            // Exit early if the required elements aren't on this page.
            if (!modalElement.length || !modalBody || !tocNav) {
                return;
            }

            // Move Nav into modal.
            modalElement.on('show.bs.modal', function() {
                modalBody.appendChild(tocNav);
                mobileFab.classList.add('d-none');

            });

            // Move Nav back home.
            modalElement.on('hidden.bs.modal', function() {
                tocParent.appendChild(tocNav);
                mobileFab.classList.remove('d-none');
            });

            // Close on link click.
            tocNav.addEventListener('click', (e) => {
                if (e.target.closest('a')) {
                    modalElement.modal('hide');
                }
            });

            // Close on desktop resize.
            window.addEventListener('resize', () => {
                if (window.innerWidth > 991 && modalElement.hasClass('show')) {
                    modalElement.modal('hide');
                }
            });
        }
    };
});