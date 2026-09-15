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
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */

namespace format_ucl\output\courseformat\content\section;

use core\output\action_menu\link;
use core\output\action_menu\link_secondary;
use core\url;
use core_courseformat\output\local\content\section\controlmenu as controlmenu_base;
use pix_icon;

/**
 * Base class to render section controls.
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
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
     * Retrieves the move item for the section control menu.
     *
     * Overridden to remove the check: $this->format->get_sectionid()
     * as we want to show the move section option even when in single
     * section view.
     *
     * @return link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_movesection_item(): ?link
    {
        if (
            $this->section->sectionnum == 0
            || !has_capability('moodle/course:movesections', $this->coursecontext)
        ) {
            return null;
        }

        $url = new url(
            $this->baseurl,
            [
                'movesection' => $this->section->sectionnum,
                'section' => $this->section->sectionnum,
            ]
        );

        return new link_secondary(
            url: $url,
            icon: new \core\output\pix_icon('i/dragdrop', ''),
            text: get_string('move'),
            attributes: [
                // This tool requires ajax and will appear only when the frontend state is ready.
                'class' => 'move waitstate',
                'data-action' => 'moveSection',
                'data-id' => $this->section->id,
            ],
        );
    }

    /**
     * Retrieves the highlight item for the section control menu.
     *
     * @return link_secondary The menu item if applicable, otherwise null.
     */
    protected function get_section_highlight_item(): link_secondary
    {
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
            targetsectionid: $section->id,
            returnurl: $this->baseurl,
        );

        return new link_secondary(
                url: $url,
                icon: new pix_icon($icon, ''),
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

}
