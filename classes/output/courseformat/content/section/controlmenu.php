<?php
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
 * Contains the default section controls output class.
 *
 * @package   format_ucl
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ucl\output\courseformat\content\section;

use core\output\action_menu;
use core\output\action_menu\link;
use core\output\action_menu\link as action_menu_link;
use core\output\action_menu\link_secondary;
use core\url;
use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section\controlmenu as controlmenu_base;
use moodle_url;
use action_menu_link_secondary;
use pix_icon;
use section_info;

/**
 * Base class to render a course section menu.
 *
 * @package   format_ucl
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends controlmenu_base {
    /**
     * Generate the edit control items of a section.
     *
     * @return array of edit control items
     */
    public function section_control_items() {
        $controls = [];

        if (!$this->section->is_orphan()) {
            $controls['edit'] = $this->get_section_edit_item();
            $controls['movesection'] = $this->get_section_movesection_item();
            $controls['visibility'] = $this->get_section_visibility_item();
            $divider = new \action_menu_filler();
            $divider->primary = false;
            $controls['divider'] = $divider;
            $controls['highlight'] = $this->get_section_highlight_item();
            $controls['duplicate'] = $this->get_section_duplicate_item();
        }

        $controls['delete'] = $this->get_section_delete_item();

        return $controls;
    }

    /**
     * Retrieves the edit item for the section control menu.
     *
     * @return link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_edit_item(): ?link {
        if (!has_capability('moodle/course:update', $this->coursecontext)) {
            return null;
        }

        $sectionreturn = $this->format->get_sectionnum();
        $returnparams = !is_null($sectionreturn) ? ['sr' => $sectionreturn] : [];
        $url = new url(
            '/course/editsection.php',
            array_merge(['id' => $this->section->id], $returnparams)
        );

        return new link_secondary(
            url: $url,
            icon: new pix_icon('i/manual_item', ''),
            text: get_string('editsection'),
            attributes: ['class' => 'edit'],
        );
    }

    /**
     * Retrieves the view item for the section control menu.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_highlight_item(): \core\output\action_menu\link_secondary {
        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();
        $sectionreturn = $format->get_sectionnum();

        $highlightoff = get_string('highlightoff');
        $highlightofficon = 'i/marked';

        $highlighton = get_string('highlight');
        $highlightonicon = 'i/marker';

        if ($course->marker == $section->sectionnum) {  // Show the "light globe" on/off.
            $action = 'section_unhighlight';
            $icon = $highlightofficon;
            $name = $highlightoff;
            $attributes = [
                'class' => 'editing_highlight',
                'data-action' => 'sectionUnhighlight',
                'data-sectionreturn' => $sectionreturn,
                'data-id' => $section->id,
                'data-icon' => $highlightofficon,
                'data-swapname' => $highlighton,
                'data-swapicon' => $highlightonicon,
            ];
        } else {
            $action = 'section_highlight';
            $icon = $highlightonicon;
            $name = $highlighton;
            $attributes = [
                'class' => 'editing_highlight',
                'data-action' => 'sectionHighlight',
                'data-sectionreturn' => $sectionreturn,
                'data-id' => $section->id,
                'data-icon' => $highlightonicon,
                'data-swapname' => $highlightoff,
                'data-swapicon' => $highlightofficon,
            ];
        }

        $url = $this->format->get_update_url(
            action: $action,
            ids: [$section->id],
            returnurl: $this->baseurl,
        );

        return new action_menu_link_secondary(
            url: $url,
            icon: new \core\output\pix_icon($icon, ''),
            text: $name,
            attributes: $attributes,
        );
    }

    /**
     * Return the course url.
     *
     * @return moodle_url
     */
    protected function get_course_url(): moodle_url {
        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();
        $sectionreturn = $format->get_sectionnum();

        if ($sectionreturn) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());
        return $url;
    }


    /**
     * Return the specific section highlight action.
     *
     * @return array the action element.
     */
    protected function get_highlight_control(): array {
        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();
        $sectionreturn = $format->get_sectionnum();
        $url = $this->get_course_url();
        if (!is_null($sectionreturn)) {
            $url->param('sectionid', $format->get_sectionid());
        }

        $highlightoff = get_string('highlightoff');
        $highlightofficon = 'i/marked';

        $highlighton = get_string('highlight');
        $highlightonicon = 'i/marker';

        if ($course->marker == $section->section) {  // Show the "light globe" on/off.
            $url->param('marker', 0);
            $result = [
                'url' => $url,
                'icon' => $highlightofficon,
                'name' => $highlightoff,
                'pixattr' => ['class' => 'fa-regular'],
                'attr' => [
                    'class' => 'editing_highlight',
                    'data-action' => 'sectionUnhighlight',
                    'data-sectionreturn' => $sectionreturn,
                    'data-id' => $section->id,
                    'data-icon' => $highlightofficon,
                    'data-swapname' => $highlighton,
                    'data-swapicon' => $highlightonicon,
                ],
            ];
        } else {
            $url->param('marker', $section->section);
            $result = [
                'url' => $url,
                'icon' => $highlightonicon,
                'name' => $highlighton,
                'pixattr' => ['class' => 'fa-regular'],
                'attr' => [
                    'class' => 'editing_highlight',
                    'data-action' => 'sectionHighlight',
                    'data-sectionreturn' => $sectionreturn,
                    'data-id' => $section->id,
                    'data-icon' => $highlightonicon,
                    'data-swapname' => $highlightoff,
                    'data-swapicon' => $highlightofficon,
                ],
            ];
        }
        return $result;
    }

    /**
     * Return the specific section move action.
     *
     * @return array the action element.
     */
    protected function get_move_control(): array {
        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();
        $sectionreturn = $format->get_sectionnum();
        $url = $this->get_course_url();
        $url = course_get_url($course, $section->section);
        $url->param('movesection', 1);
        $url->param('section', $section->section);
        $url->param('move', 0);
        $result = [
            'url' => $url,
            'icon' => 'i/dragdrop',
            'name' => get_string('move', 'moodle'),
            'pixattr' => ['class' => 'fa-regular'],
            'attr' => [
                'class' => 'editing_highlight',
                'data-action' => 'moveSection',
                'data-id' => $section->id,
             ],
        ];

        return $result;
    }
}
