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

use completion_info;
use context_course;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core_course\external\course_summary_exporter;
use core_courseformat\base;
use format_ucl;
use moodle_url;
use section_info;
use stdClass;

/**
 * Table of contents for a course.
 *
 * @package    format_ucl
 * @copyright  2024 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
class toc implements renderable, templatable {
    /**
     * Constructor
     *
     * @param format_ucl $format
     */
    public function __construct(
        /** @var  format_ucl format */
        protected format_ucl $format,
    ) {
    }

    /**
     * Return data for course table of contents.
     *
     * @param renderer_base $output
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $USER;
        if (!$course = $this->format->get_course()) {
            return [];
        }

        $activesection = optional_param('id', 0, PARAM_INT);
        $format = course_get_format($course);
        $context = context_course::instance($course->id);
        $numsections = $format->get_last_section_number();
        $canviewhidden = has_capability('moodle/course:update', $context);
        $coursesections = $format->get_sections();

        $visiblecount = 0;
        $namecount = 0;
        $modcount = 0;

        $data = new stdClass();
        foreach ($coursesections as $section) {
            // Editor warning data.
            if ($canviewhidden) {
                if ($section->section) { // Don't count section 0.
                    if ($section->visible) {
                        $visiblecount++;

                        // Sections without a name.
                        if (!$section->name) {
                            $namecount++;
                        }

                        // Sections with one or less mods.
                        $modinfo = $format->get_modinfo();
                        $cmids = $modinfo->sections[$section->section] ?? [];
                        if (count($cmids) < 2) {
                            $modcount++;
                        }

                        // Sections with lots of mods, and no labels.
                        // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedIf
                        if (count($cmids) > 5) {
                            // TODO - not sure yet.
                        }
                    }
                }
            }

            if ($section->visible || $canviewhidden) {
                $s = new stdClass();
                $s->id = $section->id;
                $s->name = $format->get_section_name($section);
                $s->url = $format->get_view_url($section, ['sr' => $section->section]);
                $s->visible = $section->visible;

                // Current url.
                if ($activesection == $section->id) {
                    $s->active = true;
                }

                if ($section->section === 0) {
                    $s->class = "course-home";
                }

                // Highlighted.
                if ($course->marker) {
                    if ($section->section == $course->marker) {
                        $s->highlight = true;
                    }
                }

                // Progress.
                if ($course->enablecompletion) {
                    if (!$USER->editing) {
                        $s->progress = $this->format_ucl_section_progress($section);
                    }
                }

                // Add to template data.
                $data->coursesection[] = $s;
            }
        }

        // Editor warnings.
        $data->showwarning = false;

        // Sections names check.
        if ($namecount > 1) {
            $data->showwarning = true;
            $data->namecount = $namecount;
        }

        // Number of sections check.
        $recommendedmaxsections = format_ucl\config::instance()->get_recommended_max_sections();
        if ($visiblecount > $recommendedmaxsections) {
            $data->showwarning = true;
            $data->sectioncount = $visiblecount;
            $data->recommendedmaxsections = $recommendedmaxsections;
        }

        // Activites per section in check.
        if ($modcount > 1) {
            $data->showwarning = true;
            $data->modcount = $modcount;
        }

        // Course image check.
        if (!course_summary_exporter::get_course_image($course)) {
            $data->showwarning = true;
            $data->noimage = true;
        }

        if (has_any_capability(['moodle/course:manageactivities'], $PAGE->context)) {
            $returnurl = new moodle_url(
                '/course/format/ucl/newsectionredirect.php',
                [
                    'course' => $course->id,
                    'section' => count($coursesections),
                ]
            );

            $data->addsections = (object) [
                'url' => $format->get_update_url('section_add', [], 0, null, $returnurl),
                'title' => "Add new section",
            ];
        }
        return $data;
    }

    /**
     * Given a section, return the data for progress.
     *
     * @param section_info $section
     * @return stdClass
     */
    public function format_ucl_section_progress(section_info $section): stdClass {
        $course = $this->format->get_course();
        // Get all the Moodle things.
        $modinfo = $this->format->get_modinfo();
        $completioninfo = new completion_info($course);
        $cmids = $modinfo->sections[$section->section] ?? [];

        // Count vars.
        $total = 0;
        $complete = 0;

        // Loop through cm in this section.
        foreach ($cmids as $cmid) {
            $thismod = $modinfo->cms[$cmid];
            if ($thismod->uservisible) {
                if ($completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if (
                        $completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS
                    ) {
                        $complete++;
                    }
                }
            }
        }

        // Return data.
        $data = new stdClass();
        if ($total) {
            $data->id = $section->id;
            $data->total = $total;
            $data->complete = $complete;
            $data->percentage = round(($complete / $total) * 100);
            if ($data->percentage == 100) {
                $data->done = true;
            }
        }
        return $data;
    }
}
