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
import CourseEvents from 'core_course/events';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

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
     * Static method to create a component instance from the mustache template.
     *
     * @param {element|string} target the DOM main element or its ID
     * @param {object} selectors optional css selector overrides
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

        this._initSectionProgress();
    }

    /**
     * Section progress updates on load and when completion is toggled.
     */
    _initSectionProgress() {
        // Core stuff.
        const manualCompletionEvent = (CourseEvents && CourseEvents.manualCompletionToggled)
            || 'core_course:manualcompletiontoggled';

        document.addEventListener(manualCompletionEvent, (event) => {
            const detail = event.detail || {};
            const cm = this.reactive.get('cm', detail.cmid);
            const currentProgress = this.element.querySelector(`.pie[data-id]`);
            const sectionId = (cm && cm.sectionid) || (currentProgress && currentProgress.dataset.id);

            if (sectionId) {
                this._updateSectionProgress(sectionId);
            }
        });

        const currentProgress = this.element.querySelector(`.pie[data-id]`);
        if (currentProgress) {
            this._updateSectionProgress(currentProgress.dataset.id);
        }
    }

    /**
     * Count done items in one section, then pass the totals to the UI.
     *
     * @param {string|number} sectionId the section id
     */
    _updateSectionProgress(sectionId) {
        const section = this.reactive.get('section', sectionId);
        if (!section) {
            return;
        }

        const cmlist = section.cmlist || [];
        let total = 0;
        let done = 0;

        cmlist.forEach((cmId) => {
            const cm = this.reactive.get('cm', cmId);
            if (!cm || typeof cm.completionstate === 'undefined') {
                return;
            }

            total += 1;
            if (cm.isoverallcomplete === true || cm.completionstate === 1 || cm.completionstate === 2) {
                done += 1;
            }
        });

        this._renderProgress(sectionId, total, done);
    }

    /**
     * Update the progress label and animate it to the new percentage.
     *
     * @param {string|number} sectionId the section id
     * @param {number} total total number of completable items
     * @param {number} done number of completed items
     */
    _renderProgress(sectionId, total, done) {
        const progress = this.element.querySelector(`.pie[data-id="${sectionId}"]`);
        if (!progress || total <= 0) {
            return;
        }

        const percentage = Math.round((done / total) * 100);
        const currentPercentage = Number(progress.getAttribute('data-percentage')) || 0;

        if (currentPercentage === percentage) {
            return;
        }

        const tooltip = `${done} of ${total} complete`;
        progress.setAttribute('data-original-title', tooltip);
        progress.setAttribute('aria-label', tooltip);

        // Mark fully complete sections for styling.
        progress.classList.toggle('complete', percentage === 100);

        // Animate from the current value to the new value.
        let value = currentPercentage;
        const interval = setInterval(() => {
            value += percentage > value ? 1 : -1;
            progress.style.setProperty('--p', value);
            progress.setAttribute('data-percentage', value);

            // Stop once we hit the target.
            if (value === percentage) {
                clearInterval(interval);
            }
        }, 20);
    }

    getWatchers() {
        return [
            {watch: `section:deleted`, handler: this._deleteSection},
            // Sections and cm sorting.
            {watch: `course.sectionlist:updated`, handler: this._refreshCourseToc},
        ];
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

        this._fixOrder(this.element, sectionlist, this.sections);
    }

    /**
     * Fix or reorder the section order.
     *
     * @param {Element} container the HTML element to reorder.
     * @param {Array} neworder an array with the ids order
     * @param {Array} allitems the list of html elements that can be placed in the container
     */
    _fixOrder(container, neworder, allitems) {

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
