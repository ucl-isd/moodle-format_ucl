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