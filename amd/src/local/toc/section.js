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
 * Course index section component.
 *
 * This component is used to control specific course section interactions like drag and drop.
 *
 * @module     core_courseformat/local/toc/section
 * @class      core_courseformat/local/toc/section
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from "core/reactive";

export default class Component extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'toc_section';
        // Default query selectors.
        this.selectors = {
            SECTION: `[data-for='section']`,
            SECTION_ITEM: `[data-for='section_item']`,
            SECTION_TITLE: `[data-for='section_title']`,
            DND_ALLOWED: `[data-courseindexdndallowed='true']`,
        };
        // Default classes to toggle on refresh.
        this.classes = {
            SECTIONHIDDEN: 'dimmed',
            SECTIONCURRENT: 'current',
            LOCKED: 'editinprogress',
            RESTRICTIONS: 'restrictions',
            OVERLAYBORDERS: 'overlay-preview-borders',
        };

        // We need our id to watch specific events.
        this.id = this.element.dataset.id;
    }

    /**
     * Static method to create a component instance form the mustache template.
     *
     * @param {string} target the DOM main element or its ID
     * @param {object} selectors optional css selector overrides
     * @return {Component}
     */
    static init(target, selectors) {
        return new this({
            element: document.getElementById(target),
            selectors,
        });
    }

    /**
     * Component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            {watch: `section[${this.id}]:deleted`, handler: this.remove},
            {watch: `section[${this.id}]:updated`, handler: this._refreshSection},
        ];
    }

    /**
     * Update a course index section using the state information.
     *
     * @param {Object} param details the update details.
     * @param {Object} param.element the section element
     */
    _refreshSection({element}) {
        // Update classes.
        this.element.classList.toggle(this.classes.SECTIONHIDDEN, !element.visible);
        this.element.classList.toggle(this.classes.SECTIONCURRENT, element.current);
        // Update title.
        this.getElement(this.selectors.SECTION_TITLE).innerHTML = element.title;
    }
}