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
 * Format ucl section extra logic component.
 *
 * @module     format_ucl/section
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import Pending from "core/pending";
import Fragment from "core/fragment";
import Config from "core/config";
import Templates from "core/templates";

class Section extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'format_ucl_section';
        // Default query selectors.
        this.selectors = {
            SECTION: `[data-for='section']`,
            SECTION_NAVIGATION: `[data-for='section-nav']`,
            SECTION_DIVIDER: `[data-for='section-divider']`,
            SECTION_CONTROLMENU: `[data-for='section-controlmenu']`,
            SETMARKER: `[data-action="sectionHighlight"]`,
            REMOVEMARKER: `[data-action="sectionUnhighlight"]`,
            ACTIONTEXT: `.menu-action-text`,
            ICON: `.icon`,
        };
        // Default classes to toggle on refresh.
        this.classes = {
            HIDE: 'd-none',
        };
        // The ucl format section specific actions.
        this.formatActions = {
            HIGHLIGHT: 'sectionHighlight',
            UNHIGHLIGHT: 'sectionUnhighlight',
        };
    }

    /**
     * Component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            {watch: `section.current:updated`, handler: this._refreshHighlight},
            {watch: `section.number:updated`, handler: this._reloadSectionDividerAndNavigation},
            {watch: `section.number:updated`, handler: this._refreshSectionNumber},
        ];
    }

    /**
     * Update a content section using the state information.
     *
     * @param {object} param
     * @param {Object} param.element details the update details.
     */
    async _refreshHighlight({element}) {
        let selector;
        let newAction;
        if (element.current) {
            selector = this.selectors.SETMARKER;
            newAction = this.formatActions.UNHIGHLIGHT;
        } else {
            selector = this.selectors.REMOVEMARKER;
            newAction = this.formatActions.HIGHLIGHT;
        }
        // Find the affected action.
        const affectedAction = this.getElement(`${selector}`, element.id);
        if (!affectedAction) {
            return;
        }
        // Change action, text and icon.
        affectedAction.dataset.action = newAction;
        const actionText = affectedAction.querySelector(this.selectors.ACTIONTEXT);
        if (affectedAction.dataset?.swapname && actionText) {
            const oldText = actionText?.innerText;
            actionText.innerText = affectedAction.dataset.swapname;
            affectedAction.dataset.swapname = oldText;
        }
        const icon = affectedAction.querySelector(this.selectors.ICON);
        if (affectedAction.dataset?.swapicon && icon) {
            const newIcon = affectedAction.dataset.swapicon;
            if (newIcon) {
                const pixHtml = await Templates.renderPix(newIcon, 'core');
                Templates.replaceNode(icon, pixHtml, '');
                affectedAction.dataset.swapicon = affectedAction.dataset.icon;
                affectedAction.dataset.icon = newIcon;
            }
        }
    }

    /**
     * Reload a course section navigation.
     *
     * @param {details} param0 the watcher details
     * @param {object} param0.element the state object
     */
    _reloadSectionNavigation({element}) {
        const pendingReload = new Pending(`format_ucl/section:reloadNavigation_${element.id}`);
        const sectionnavigation = this.getElement(this.selectors.SECTION_NAVIGATION, element.id);

        if (sectionnavigation) {
            const promise = Fragment.loadFragment(
                'format_ucl',
                'section_navigation',
                Config.courseContextId,
                {
                    id: element.id,
                    courseid: Config.courseId,
                    sr: this.reactive?.sectionReturn ?? null,
                    pagesectionid: this.reactive?.pageSectionId ?? null,
                }
            );
            promise.then((html, js) => {
                Templates.replaceNode(sectionnavigation, html, js);
                pendingReload.resolve();
                return;
            }).catch(() => {
                pendingReload.resolve();
            });
        }
    }

    /**
     * Reload a course section divider.
     *
     * @param {details} param0 the watcher details
     * @param {object} param0.element the state object
     */
    _reloadSectionDivider({element}) {
        const pendingReload = new Pending(`format_ucl/section:reloadDivider_${element.id}`);
        const sectiondivider = this.getElement(this.selectors.SECTION_DIVIDER, element.id);

        if (sectiondivider) {
            const promise = Fragment.loadFragment(
                'format_ucl',
                'section_divider',
                Config.courseContextId,
                {
                    id: element.id,
                    courseid: Config.courseId,
                }
            );
            promise.then((html, js) => {
                Templates.replaceNode(sectiondivider, html, js);
                pendingReload.resolve();
                return;
            }).catch(() => {
                pendingReload.resolve();
            });
        }
    }

    /**
     * Reload a course section control menu.
     *
     * @param {details} param0 the watcher details
     * @param {object} param0.element the state object
     */
    _reloadSectionControlMenu({element}) {
        const pendingReload = new Pending(`format_ucl/section:reloadControlmenu_${element.id}`);
        const sectioncontrolmenu = this.getElement(this.selectors.SECTION_CONTROLMENU, element.id);

        if (sectioncontrolmenu) {
            const promise = Fragment.loadFragment(
                'format_ucl',
                'section_controlmenu',
                Config.courseContextId,
                {
                    id: element.id,
                    courseid: Config.courseId,
                }
            );
            promise.then((html, js) => {
                Templates.replaceNode(sectioncontrolmenu, html, js);
                pendingReload.resolve();
            }).catch(() => {
                pendingReload.resolve();
            });
        }
    }

    /**
     * Reload a course section divider and navigation.
     *
     * Called when a section has been moved
     *
     * @param {details} param0 the watcher details
     * @param {object} param0.element the state object
     */
    _reloadSectionDividerAndNavigation({element}) {
        this._reloadSectionDivider({element});
        this._reloadSectionNavigation({element});
        this._reloadSectionControlMenu({element});
    }

    /**
     * Update a course section when the section number changes.
     *
     * The courseActions module used for most course section tools still depends on css classes and
     * section numbers (not id). To prevent inconsistencies when a section is moved, we need to refresh
     * the sectionreturnnum
     *
     * @param {Object} param
     * @param {Object} param.element details the update details.
     */
    _refreshSectionNumber({element}) {
        // Find the element.
        const target = this.getElement(this.selectors.SECTION, element.id);
        if (!target) {
            // Job done. Nothing to refresh.
            return;
        }
        // The data-sectionnumber is the attribute used by components to store the section number.
        target.dataset.sectionreturnnum = element.number;
    }
}

export const init = () => {
    // Add component to the section.
    const courseEditor = getCurrentCourseEditor();
    if (courseEditor.supportComponents && courseEditor.isEditing) {
        new Section({
            element: document.getElementById('page'),
            reactive: courseEditor,
        });
    }
};
