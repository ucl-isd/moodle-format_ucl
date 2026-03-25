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
 * @module     core_courseformat/local/courseindex/courseindex
 * @class     core_courseformat/local/courseindex/courseindex
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

export default class Component extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'toc';
        // Default query selectors.
        this.selectors = {
            SECTION: `[data-for='tocsection']`,
        };
        // Default classes to toggle on refresh.
        this.classes = {
            SECTIONHIDDEN: 'dimmed',
            SECTIONCURRENT: 'current',
            SHOW: `show`,
        };
        // Arrays to keep cms and sections elements.
        this.sections = {};
    }

    /**
     * Static method to create a component instance form the mustache template.
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
        // Get cms and sections elements.
        const sections = this.getElements(this.selectors.SECTION);
        sections.forEach((section) => {
            this.sections[section.dataset.id] = section;
        });
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
        this._fixOrder(this.element, sectionlist, this.sections);
        // Fire section navigation updated @TODO temp using section updated.

    }

    /**
     * Fix/reorder the section or cms order.
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

        // Grant the list is visible (in case it was empty).
        container.classList.remove('hidden');

        // Move the elements in order at the beginning of the list.
        neworder.forEach((itemid, index) => {
            const item = allitems[itemid];
            // Get the current element at that position.
            const currentitem = container.children[index];
            if (currentitem === undefined && item != undefined) {
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
