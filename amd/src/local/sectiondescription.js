/**
 * Inline editing for course section summaries.
 *
 * @module format_ucl/local/sectiondescription
 */

import DynamicForm from 'core_form/dynamicform';
import Notification from 'core/notification';

const forms = new WeakMap();
let initialized = false;
let tinymcepreload;

/**
 * Start loading TinyMCE before the summary form is requested.
 */
const preloadTinyMCE = () => {
    if (tinymcepreload) {
        return;
    }

    tinymcepreload = import('editor_tiny/editor').catch(exception => {
        tinymcepreload = null;
        window.console.warn('TinyMCE preload failed; it will load when the editor is opened.', exception);
    });
};

/**
 * Initialize inline section summary editing.
 */
export const init = () => {
    if (initialized) {
        return;
    }
    initialized = true;
    preloadTinyMCE();

    document.addEventListener('click', event => {
        const editlink = event.target.closest('[data-action="edit-section-summary"]');
        if (!editlink) {
            return;
        }

        const root = editlink.closest('[data-region="ucl-section-summary"]');
        const display = root?.querySelector('[data-region="summary-display"]');
        const formcontainer = root?.querySelector('[data-region="summary-form"]');
        const loadingtemplate = root?.querySelector('[data-region="summary-loading-template"]');
        const label = editlink.querySelector('[data-region="summary-action-label"]');
        if (!root || !display || !formcontainer || !loadingtemplate || !label) {
            return;
        }

        let form = forms.get(root);
        if (!form) {
            form = new DynamicForm(formcontainer, '\\format_ucl\\form\\section_summary_form');
            forms.set(root, form);

            form.addEventListener(form.events.FORM_SUBMITTED, submitEvent => {
                submitEvent.preventDefault();
                const {summaryhtml, hassummary} = submitEvent.detail;
                display.innerHTML = summaryhtml;
                root.dataset.hasSummary = hassummary ? 'true' : 'false';
                display.classList.toggle('d-none', !hassummary);
                label.textContent = hassummary ? editlink.dataset.editLabel : editlink.dataset.addLabel;
                formcontainer.replaceChildren();
                editlink.classList.remove('d-none');
                editlink.setAttribute('aria-expanded', 'false');
                editlink.focus();
            });

            form.addEventListener(form.events.FORM_CANCELLED, () => {
                display.classList.toggle('d-none', root.dataset.hasSummary !== 'true');
                editlink.classList.remove('d-none');
                editlink.setAttribute('aria-expanded', 'false');
                editlink.focus();
            });
        }

        editlink.classList.add('d-none');
        editlink.setAttribute('aria-expanded', 'true');
        display.classList.add('d-none');
        formcontainer.replaceChildren(loadingtemplate.content.cloneNode(true));
        form.load({sectionid: root.dataset.sectionid})
            .catch(exception => {
                display.classList.toggle('d-none', root.dataset.hasSummary !== 'true');
                formcontainer.replaceChildren();
                editlink.classList.remove('d-none');
                editlink.setAttribute('aria-expanded', 'false');
                Notification.exception(exception);
            });
    });
};
