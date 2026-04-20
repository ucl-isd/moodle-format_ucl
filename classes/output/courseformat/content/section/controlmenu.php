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
use core_courseformat\output\local\content\section\controlmenu as controlmenu_base;
use moodle_url;
use action_menu_link_secondary;
use pix_icon;

/**
 * Base class to render a course section menu.
 *
 * @package   format_ucl
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends controlmenu_base {
    /** @var array sortorder */
    protected const SORTORDER = [
        'edit',
        'move',
        'visibility',
        'highlight',
        'unhighlight',
        'duplicate',
        'delete',
    ];

    /** @var \core_courseformat\base the course format class */
    protected $format;

    /** @var \section_info the course section class */
    protected $section;

    /**
     * Generate the edit control items of a section.
     *
     * This method must remain public until the final deprecation of section_edit_control_items.
     *
     * @return array of edit control items
     */
    public function section_control_items() {
        global $USER;

        $format = $this->format;
        $section = $this->section;
        $coursecontext = $format->get_context();

        // Maintain the logic/capability checks from the parent function.
        $controls = parent::section_control_items();

        if (!$section->is_orphan() && $section->section) {
            if (has_capability('moodle/course:setcurrentsection', $coursecontext)) {
                $controls['highlight'] = $this->get_highlight_control();
            }

            if (has_capability('moodle/course:movesections', $coursecontext, $USER)) {
                $controls['movepopup'] = $this->get_move_control();
            }
        }

        return $controls;
    }

    /**
     * Format control array into an action_menu.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return action_menu|null the action menu
     */
    protected function format_controls(array $controls): ?action_menu {
        if (empty($controls)) {
            return null;
        }

        $menu = new action_menu();
        $menu->set_kebab_trigger(get_string('edit'));
        $menu->attributes['class'] .= ' section-actions';
        $menu->attributes['data-sectionid'] = $this->section->id;

        if (array_key_exists('edit', $controls)) {
            $control = $controls['edit'];
            // We want to use a different icon.
            $control['icon'] = 'i/manual_item';
            $actionlink = $this->format_control($control);
            $menu->add($actionlink);
        }

        if (array_key_exists('movepopup', $controls)) {
            $control = $controls['movepopup'];
            $actionlink = $this->format_control($control);
            $menu->add($actionlink);
        }

        if (array_key_exists('visibility', $controls)) {
            $control = $controls['visibility'];
            $actionlink = $this->format_control($control);
            $menu->add($actionlink);
        }

        $divider = new \action_menu_filler();
        $divider->primary = false;
        $menu->add($divider);

        if (array_key_exists('highlight', $controls)) {
            $control = $controls['highlight'];
            $actionlink = $this->format_control($control);
            $menu->add($actionlink);
        }

        if (array_key_exists('unhighlight', $controls)) {
            $control = $controls['unhighlight'];
            $actionlink = $this->format_control($control);
            $menu->add($actionlink);
        }

        if (array_key_exists('duplicate', $controls)) {
            $control = $controls['duplicate'];
            $actionlink = $this->format_control($control);
            $menu->add($actionlink);
        }

        if (array_key_exists('delete', $controls)) {
            $control = $controls['delete'];
            $actionlink = $this->format_control($control);
            $menu->add($actionlink);
        }

        return $menu;
    }

    protected function format_control($value) {
        $icon = empty($value['icon']) ? '' : $value['icon'];
        $url = empty($value['url']) ? '' : $value['url'];
        $name = empty($value['name']) ? '' : $value['name'];
        $attr = empty($value['attr']) ? [] : $value['attr'];
        $class = empty($value['pixattr']['class']) ? '' : $value['pixattr']['class'];

        return new action_menu_link_secondary(
            new moodle_url($url),
            new pix_icon($icon, '', null, ['class' => "smallicon " . $class]),
            $name,
            $attr
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
