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

namespace format_ucl\output\widgets;

use context_course;
use core\output\renderable;
use core\output\templatable;
use core_courseformat\base as course_format;
use format_ucl;
use moodle_url;
use section_info;
use stdClass;

/**
 * Base class to render section controls.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sectionactions implements renderable, templatable {
    /**
     * Constructor
     *
     * @param format_ucl $format
     */
    public function __construct(
        /** @var  format_ucl format */
        protected format_ucl $format,
        /** @var  stdClass section */
        protected stdClass $section,
    ) {
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return null|array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): ?stdClass {
        $menu = $this->get_section_actions();

        if (empty($menu)) {
            return new stdClass();
        }

        $data = (object)[
            'menu' => $menu,
            'hasmenu' => true,
            'id' => "menu_{$this->section->id}",
        ];

        return $data;
    }

    /**
     * Generate the edit actions of a section.
     *
     *
     * @return array
     */
    public function get_section_actions(): array {
        global $USER;

        $format = $this->format;
        $modinfo = $format->get_modinfo();
        $sectioninfo = $modinfo->get_section_info_by_id($this->section->id, MUST_EXIST);
        $course = $format->get_course();
        $sectionreturn = !is_null($format->get_sectionid()) ? $format->get_sectionnum() : null;
        $user = $USER;

        $usecomponents = $format->supports_components();
        $coursecontext = context_course::instance($course->id);
        $numsections = $format->get_last_section_number();
        $isstealth = $sectioninfo->is_orphan();

        $baseurl = course_get_url($course, $sectionreturn);
        $baseurl->param('sesskey', sesskey());

        $sectionactions = [];

        if (!$isstealth && has_capability('moodle/course:update', $coursecontext, $user)) {
            // Edit.
            $params = [
                'id' => $sectioninfo->id,
                'section' => $sectioninfo->section,
                'sectionid' => $sectioninfo->id,
            ];
            $streditsection = get_string('editsection');

            $sectionactions['edit'] = [
                'url' => new moodle_url('/course/editsection.php', $params),
                'icon' => 'fa-pen-to-square',
                'name' => $streditsection,
                'iconclass' => 'fa-regular',
            ];
        }

        // Move.
        if (has_capability('moodle/course:movesections', $coursecontext, $user)) {
            if ($usecomponents) {
                // This tool will appear only when the state is ready.
                $url = clone($baseurl);
                $url->param('movesection', 1);
                $url->param('section', $sectioninfo->section);
                $strmovesection = get_string('move');

                $sectionactions['move'] = [
                    'url' => $url,
                    'icon' => 'fa-arrows-up-down-left-right',
                    'name' => $strmovesection,
                    'iconclass' => 'fa-solid',
                    'data-action' => 'moveSection',
                    'data-id' => $sectioninfo->id,
                ];
            }
        }

        // Show / Hide.
        if ($sectioninfo->section) {
            $url = clone($baseurl);
            if (!is_null($sectionreturn)) {
                $url->param('sectionid', $format->get_sectionid());
            }
            if (!$isstealth) {
                if (has_capability('moodle/course:sectionvisibility', $coursecontext, $user)) {
                    $strhidefromothers = get_string('hidefromothers', 'format_ucl');
                    $strshowfromothers = get_string('showfromothers', 'format_ucl');
                    if ($sectioninfo->visible) { // Show the hide/show eye.
                        $url->param('hide', $sectioninfo->section);
                        $sectionactions['visibility'] = [
                            'url' => $url,
                            'icon' => 'fa-eye-slash',
                            'name' => $strhidefromothers,
                            'iconclass' => 'fa-regular',
//                            'data-sectionreturn' => $sectionreturn,
//                            'data-action' => ($usecomponents) ? 'sectionHide' : 'hide',
//                            'data-id' => $sectioninfo->id,
//                            'data-icon' => 'fa-eye-slash',
//                            'data-swapname' => $strshowfromothers,
//                            'data-swapicon' => 'fa-eye',
                        ];
                    } else {
                        $url->param('show', $sectioninfo->section);
                        $sectionactions['visibility'] = [
                            'url' => $url,
                            'icon' => 'fa-eye',
                            'name' => $strshowfromothers,
                            'iconclass' => 'fa-regular',
//                            'data-sectionreturn' => $sectionreturn,
//                            'data-action' => ($usecomponents) ? 'sectionShow' : 'show',
//                            'data-id' => $sectioninfo->id,
//                            'data-icon' => 'fa-eye',
//                            'data-swapname' => $strhidefromothers,
//                            'data-swapicon' => 'fa-eye-slash',
                        ];
                    }
                }

                // Highlight.
                $url = clone($baseurl);
                if (!is_null($sectionreturn)) {
                    $url->param('sectionid', $format->get_sectionid());
                }
                $strhighlight = get_string('highlight', 'format_ucl');
                $strunhighlight = get_string('unhighlight', 'format_ucl');
                if (!$format->is_section_current($sectioninfo->section)) { // Show the highlighter/unhighlighter.
                    $url->param('marker', 0);
                    $sectionactions['highlight'] = [
                        'url' => $url,
                        'icon' => 'fa-highlighter',
                        'name' => $strhighlight,
                        'iconclass' => 'fa-solid',
                    ];
                } else {
                    $url->param('marker', $sectioninfo->section);
                    $sectionactions['highlight'] = [
                        'url' => $url,
                        'icon' => 'fa-highlighter',
                        'name' => $strunhighlight,
                        'iconclass' => 'fa-regular',
                    ];
                }

                // Duplicate.
                if (has_capability('moodle/course:update', $coursecontext, $user)) {
                    if ($sectioninfo->section) {
                        $url = clone($baseurl);
                        $url->param('sectionid', $sectioninfo->id);
                        $url->param('duplicatesection', 1);
                        if (!is_null($sectionreturn)) {
                            $url->param('sr', $sectionreturn);
                        }
                        $strduplicatesection = get_string('duplicate', 'format_ucl');

                        $sectionactions['duplicate'] = [
                            'url' => $url,
                            'icon' => 'fa-clone',
                            'name' => $strduplicatesection,
                            'iconclass' => 'fa-solid',
                        ];
                    }
                }
            }

            // Delete.
            if (course_can_delete_section($course, $sectioninfo)) {
                $strdelete = get_string('deletesection', 'format_ucl');
                $params = [
                    'id' => $sectioninfo->id,
                    'delete' => 1,
                    'sesskey' => sesskey(),
                ];
                if (!is_null($sectionreturn)) {
                    $params['sr'] = $sectionreturn;
                }
                $url = new moodle_url('/course/editsection.php', $params);

                $sectionactions['delete'] = [
                    'url' => $url,
                    'icon' => 'fa-trash-can',
                    'name' => $strdelete,
                    'iconclass' => 'fa-regular',
//                    'data-action' => 'deleteSection',
//                    'data-id' => $sectioninfo->id,
                    'hasconfirm' => true,
                    'confirmlabel' => json_encode(['deletesection', 'format_ucl']),
                    'confirmcontent' => json_encode([
                        'deletepresetconfirm',
                        'format_ucl',
                        $format->get_section_name($sectioninfo->section)
                    ]),
                 ];
            }
        }

        return $sectionactions;
    }
}
