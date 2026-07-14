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
 * Course TOC main component.
 *
 * @module     format_ucl/local/courseindex/courseindex
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */

import {BaseComponent} from 'core/reactive';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import {get_string as getString} from 'core/str';
import Ajax from 'core/ajax';

export default class Component extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'toc';
        // Keep section elements by section id.
        this.sections = {};
    }

    /**
     * Build this TOC component for the template markup.
     *
     * @param {element|string} target the main DOM element, or its ID
     * @param {object} selectors optional CSS selector overrides
     * @return {Component}
     */
    static init(target, selectors) {
        return new this({
            element: document.getElementById(target),
            reactive: getCurrentCourseEditor(),
            selectors,
        });
    }

    /**
     * Initial state ready method.
     */
    stateReady() {
        // Get sections.
        const sections = this.getElements(`[data-for='tocsection']`);
        sections.forEach((section) => {
            this.sections[section.dataset.id] = section;
        });
    }

    getWatchers() {
        return [
            {watch: `section:deleted`, handler: this._deleteSection},
            // Sections and cm sorting.
            {watch: `course.sectionlist:updated`, handler: this._refreshCourseToc},
            // Progress updates.
            {watch: `cm.completionstate:updated`, handler: this._refreshCompletion},
        ];
    }

    /**
     * Section progress updates when completion is toggled.
     *
     * @param {Object} details the update details.
     * @param {Object} details.element the element data.
     */
    async _refreshCompletion({element}) {
        const cm = this.reactive.get('cm', element.id);
        const sectionId = cm.sectionid;
        const progress = await Ajax.call([{
            methodname: 'format_ucl_get_section_progress',
            args: {sectionid: sectionId}
        }])[0];

        const total = progress.total;
        const done = progress.completed;

        this._renderProgress({sectionId, total, done});
    }

    /**
     * Update the progress label and animate it to the new percentage.
     *
     * @param {Object} progress progress details
     * @param {string|number} progress.sectionId the section id
     * @param {number} progress.total total number of completable items
     * @param {number} progress.done number of completed items
     */
    _renderProgress(progress) {
        const {sectionId, total, done} = progress;
        const progressContainer = this.element.querySelector(`.progress-indicator[data-id="${sectionId}"]`);
        const progressElement = progressContainer?.querySelector('.pie');
        if (!progressElement || total <= 0) {
            return;
        }

        const percentage = Math.round((done / total) * 100);
        const currentPercentage = Number(progressElement.getAttribute('data-behat-percentage')) || 0;

        if (currentPercentage === percentage) {
            return;
        }

        const srOnlyElement = progressContainer.querySelector('.sr-only');
        const fallbackTooltip = `${done} of ${total} complete`;
        progressElement.setAttribute('title', fallbackTooltip);
        progressElement.setAttribute('data-original-title', fallbackTooltip);
        srOnlyElement.textContent = fallbackTooltip;

        getString('xofycomplete', 'format_ucl', {complete: done, total})
            .then((tooltip) => {
                progressElement.setAttribute('title', tooltip);
                progressElement.setAttribute('data-original-title', tooltip);
                srOnlyElement.textContent = tooltip;
                return tooltip;
            })
            .catch(() => {
                // Keep fallback tooltip when language string lookup fails.
            });

        // Mark fully complete sections for styling.
        progressElement.classList.toggle('complete', percentage === 100);

        // Add the percentage value for Behat.
        progressElement.setAttribute('data-behat-percentage', percentage);

        // Animate from the current value to the new value.
        let value = currentPercentage;
        const interval = setInterval(() => {
            value += percentage > value ? 1 : -1;
            progressElement.style.setProperty('--p', value);

            // Stop once we hit the target.
            if (value === percentage) {
                clearInterval(interval);
            }
        }, 20);
    }

    /**
     * Refresh the section list.
     *
     * @param {object} param
     * @param {Object} param.state
     */
    _refreshCourseToc({state}) {
        const sectionlist = this.reactive.getExporter().listedSectionIds(state);

        if (
            sectionlist.length === this.element.children.length
            && sectionlist.every((sectionid, index) => this.element.children[index]?.dataset.id === String(sectionid))
        ) {
            return;
        }

        this._updateOrder(this.element, sectionlist, this.sections);
    }

    /**
     * Reorder section elements to match the latest section ID order.
     *
     * @param {Element} container the parent list element
     * @param {Array} neworder ordered section IDs
     * @param {Array} allitems section elements keyed by section ID
     */
    _updateOrder(container, neworder, allitems) {

        // Empty lists should not be visible.
        if (!neworder.length) {
            container.classList.add('hidden');
            container.innerHTML = '';
            return;
        }

        // Ensure the list is visible.
        container.classList.remove('hidden');

        // Move the elements in order at the beginning of the list.
        neworder.forEach((itemid, index) => {
            const item = allitems[itemid];
            // Get the current element at that position.
            const currentitem = container.children[index];
            if (currentitem === undefined && item !== undefined) {
                container.append(item);
                return;
            }
            if (currentitem !== item && item) {
                container.insertBefore(item, currentitem);
            }
        });
        // Remove the remaining elements.
        while (container.children.length > neworder.length) {
            container.removeChild(container.lastChild);
        }
    }

    /**
     * Remove a section from the list.
     *
     * The actual DOM element removal is delegated to the section component.
     *
     * @param {Object} details the update details.
     * @param {Object} details.element the element data.
     */
    _deleteSection({element}) {
        delete this.sections[element.id];
    }
}
