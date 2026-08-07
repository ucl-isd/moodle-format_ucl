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
use format_ucl;
use moodle_url;
use section_info;
use stdClass;

/**
 * Table of contents for a course.
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
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
        global $USER, $CFG;
        if (!$course = $this->format->get_course()) {
            return [];
        }

        $activesection = optional_param('id', 0, PARAM_INT);
        $context = context_course::instance($course->id);
        $canviewhidden = has_capability('moodle/course:update', $context);
        $coursesections = $this->format->get_sections();
        $currentsectionnum = $this->format->get_sectionnum();

        $visiblecount = 0;
        $namecount = 0;
        $modcount = 0;

        $data = new stdClass();
        foreach ($coursesections as $section) {
            // Editor warning data.
            // Don't count section 0.
            if ($canviewhidden && $section->section && $section->visible) {
                $visiblecount++;

                // Sections without a name.
                if (!$section->name) {
                    $namecount++;
                }

                // Sections with one or less mods.
                $modinfo = $this->format->get_modinfo();
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

            if ($section->uservisible || $canviewhidden) {
                $s = new stdClass();
                $s->id = $section->id;
                $s->section = $section->section;
                $s->name = $this->format->get_section_name($section);
                $s->url = $this->format->get_view_url($section, ['sr' => $section->section]);
                $s->visible = $section->visible;

                // Active section - either the section in the URL, or section 0 if on the course home page.
                if ($activesection == $section->id || ($currentsectionnum === 0 && $section->section === 0)) {
                    $s->active = true;
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
                        $s->progress = self::format_ucl_section_progress($section, $course);
                    }
                }

                // Add to template data.
                $data->coursesection[] = $s;
            }
        }

        // Editor warnings.
        $data->showwarning = false;
        $data->showguidance = false;

        // Sections names check.
        if ($namecount > 0) {
            $data->showwarning = true;
            $data->showguidance = true;
            $data->shownunnamedsections = true;
            $data->namecount = $namecount;
        }

        // Number of sections check.
        $recommendedmaxsections = format_ucl\config::instance()->get_recommended_max_sections();
        if ($visiblecount > $recommendedmaxsections) {
            $data->showwarning = true;
            $data->showguidance = true;
            $data->showtoomanysections = true;
            $data->visiblecount = $visiblecount;
            $data->recommendedmaxsections = '<span class="behat-sectioncount">' . $recommendedmaxsections . '</span>';
        }

        // Activites per section in check.
        if ($modcount > 0) {
            $data->showwarning = true;
            $data->showguidance = true;
            $data->showtoofewmods = true;
            $data->modcount = $modcount;
        }

        // Only show if link is set.
        $data->linktoguidance = format_ucl\config::instance()->get_link_to_guidance();
        $data->showguidance = $data->showguidance && $data->linktoguidance;

        // Course image check.
        if (!course_summary_exporter::get_course_image($course)) {
            $data->showwarning = true;
            $data->shownocourseimg = true;
            $url = new moodle_url($CFG->wwwroot . '/course/edit.php', ['id' => $course->id]);
            $url->set_anchor('fitem_id_overviewfiles_filemanager');
            $data->url = $url;
        }

        $returnurl = new moodle_url(
            '/course/format/ucl/newsectionredirect.php',
            [
                'course' => $course->id,
                'section' => count($coursesections),
            ]
        );

        $params = [
            'courseid' => $course->id,
            'insertsection' => 0,
            'sesskey' => sesskey(),
            'returnurl' => $returnurl,
        ];

        $data->addsections = (object)[
            'url' => new moodle_url('/course/changenumsections.php', $params),
            'title' => "Add new section",
        ];

        return $data;
    }

    /**
     * Given a section, return the data for progress.
     *
     * @param section_info $section
     * @param stdClass $course
     * @param int|null $userid
     * @return stdClass|null
     */
    public static function format_ucl_section_progress(
        section_info $section,
        stdClass $course,
        ?int $userid = null
    ): ?stdClass {
        global $USER;

        // Make sure we continue with a valid userid.
        if (empty($userid)) {
            $userid = $USER->id;
        }

        // Get all the Moodle things.
        $cmids = explode(',', $section->sequence);
        $completion = new \completion_info($course);

        // First, let's make sure completion is enabled.
        if (!$completion->is_enabled()) {
            return null;
        }

        if (!$completion->is_tracked_user($userid)) {
            return null;
        }

        static $activitieswithcompletion = null;

        // Get the modules that support completion.
        if (is_null($activitieswithcompletion)) {
            $activitieswithcompletion = $completion->get_activities();
        }

        // Only include the modules in this section.
        $modules = array_intersect_key($activitieswithcompletion, array_flip($cmids));

        if (!$total = count($modules)) {
            return null;
        }

        // Get the number of modules that have been completed.
        $complete = self::count_modules_completed_in_section($userid, $section->id);

        // Return data.
        $data = new stdClass();
        $data->id = $section->id;
        $data->total = $total;
        $data->complete = $complete;
        $data->percentage = round(($complete / $total) * 100);
        if ($data->percentage == 100) {
            $data->done = true;
        }
        return $data;
    }

    /**
     * Return the number of modules completed by a user in one specific course.
     *
     * @param int $userid The User ID.
     * @param int $sectionid
     * @return int Total number of modules completed by a user in the section
     */
    public static function count_modules_completed_in_section(int $userid, int $sectionid): int {
        global $DB;

        $sql = "SELECT COUNT(1)
                  FROM {course_modules} cm
                  JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                 WHERE cm.section = :sectionid
                   AND cmc.userid = :userid
                   AND (cmc.completionstate = " . COMPLETION_COMPLETE . "
                    OR cmc.completionstate = " . COMPLETION_COMPLETE_PASS . ")";
        $params = ['sectionid' => $sectionid, 'userid' => $userid];

        return $DB->count_records_sql($sql, $params);
    }
}
