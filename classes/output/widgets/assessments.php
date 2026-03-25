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
use format_ucl;
use local_assess_type\assess_type;
use moodle_url;
use stdClass;


require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');

/**
 * Assessment data for a course.
 *
 * @package    format_ucl
 * @copyright  2024 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
class assessments implements renderable, templatable {
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
     * Return data for assessments.
     *
     * @return stdClass|array the assessment data
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $COURSE;

        if (!$course = $this->format->get_course()) {
            return [];
        }

        // Check we have summative assessments.
        if ($summative = assess_type::get_assess_type_records_by_courseid($COURSE->id, "1")) {
            $modinfo = get_fast_modinfo($COURSE->id, $USER->id);
            $mods = $modinfo->get_cms();
            // Mod ids array to check cmid exists.
            $cmids = [];
            foreach ($mods as $mod) {
                $cmids[] = $mod->id;
            }
            // Number of students on the course.
            $studentcount = self::get_student_count($COURSE->id);

            $template = new stdClass();
            $template->assessments = [];

            // Staff of learner display for template.
            $context = context_course::instance($COURSE->id);
            $canedit = has_capability('moodle/course:update', $context);
            $template->staff = $canedit;
            $template->learner = !$canedit;

            foreach ($summative as $assess) {
                // Check this is a course mod.
                if ($assess->cmid != 0) {
                    // Skip mods where cmid is not in the course.
                    if (!in_array($assess->cmid, $cmids)) {
                        continue;
                    }

                    $cmid = $assess->cmid;
                    $mod = $modinfo->get_cm($cmid);

                    // Check mod is visible.
                    if ($mod->uservisible && $mod->visible) {
                        // Due date field.
                        $duedate = self::get_duedate($mod);

                        // Check mod has a due date.
                        if ($duedate) {
                            $assess = new stdClass();
                            // Duedate.
                            $assess->duedate = $duedate;
                            $assess->url = new moodle_url('/mod/' . $mod->modname . '/view.php', [ 'id' => $cmid]);
                            $assess->name = $mod->name;
                            $assess->icon = $mod->get_icon_url()->out(false);

                            // Section name.
                            $sectioninfo = $mod->get_section_info();
                            $assess->section = get_section_name($COURSE, $sectioninfo);

                            // Staff data.
                            if ($canedit) {
                                // Check if we can get accurate stats, or bail.
                                $isgroupmode = (bool) groups_get_activity_groupmode($mod);
                                $hasrestrictions = (!empty($mod->groupingid) || !empty($mod->availability));

                                if ($isgroupmode || $hasrestrictions) {
                                    // Set flags to message user that stats are not available.

                                    $assess->groupmode = $isgroupmode;
                                    $assess->hasrestrictions = $hasrestrictions;
                                } else {
                                    // Marking stats.
                                    $assess->hasstats = true;
                                    $assess->expectedcount = $studentcount;
                                    $stats = self::get_assessment_stats($mod);
                                    $assess->submittedcount = $stats->submitted;
                                    $assess->marked = $stats->marked;
                                    $assess->requiremarking = $stats->requiremarking;
                                }
                            }

                            // Student data.
                            if ($canedit) {
                                // Get student mark.
                                $markdata = self::get_mark($mod, $USER->id);
                                $assess->mark = $markdata->mark;
                                $assess->submitted = $markdata->submitted;
                            }

                            $template->assessments[] = $assess;
                        }
                    }
                }
            }

            // Sort.
            usort($template->assessments, function ($a, $b) {
                return $a->duedate <=> $b->duedate;
            });

            // Format dates.
            foreach ($template->assessments as $assess) {
                $assess->duedate = date('jS M y · g:ia', $assess->duedate);
            }

            // Return data.
            return $template;
        }
        // No summative assessments.
        return [];
    }

    /**
     * Get the mark and submission status for a student.
     *
     * @param \cm_info $mod
     * @param int $userid
     * @return \stdClass
     */
    public static function get_mark(\cm_info $mod, int $userid): \stdClass {
        $result = new \stdClass();
        $result->mark = null;
        $result->submitted = false;

        $gradeitems = grade_get_grade_items_for_activity($mod, true);
        if (empty($gradeitems)) {
            return $result;
        }

        $gradeitem = reset($gradeitems);
        $grade = \grade_grade::fetch([
            'itemid' => $gradeitem->id,
            'userid' => $userid,
        ]);

        if ($grade) {
            // 1. Check if the final mark is released and not hidden.
            if (!is_null($grade->finalgrade) && !$grade->is_hidden()) {
                $result->mark = grade_format_gradevalue($grade->finalgrade, $gradeitem);
            } else if (!is_null($grade->rawgrade)) {
                // 2. Only show submitted if a raw grade exists (meaning they've done the work).
                $result->submitted = true;
            }
        }

        return $result;
    }

    /**
     * Get submission stats for editors.
     *
     * @param \cm_info $mod
     * @return \stdClass
     */
    public static function get_assessment_stats(\cm_info $mod): \stdClass {
        global $DB;

        $stats = new \stdClass();
        $stats->submitted = 0;
        $stats->marked = 0;
        $stats->requiremarking = 0;

        $gradeitems = grade_get_grade_items_for_activity($mod, true);
        if (empty($gradeitems)) {
            return $stats;
        }
        $gradeitem = reset($gradeitems);
        $itemid = $gradeitem->id;

        // Count Marked (Unique users with a final grade).
        $stats->marked = $DB->count_records_select(
            'grade_grades',
            "itemid = :itemid AND userid > 0 AND finalgrade IS NOT NULL",
            ['itemid' => $itemid],
            "COUNT(DISTINCT userid)"
        );

        // Count Submitted (Unique users with EITHER a raw OR final grade).
        $stats->submitted = $DB->count_records_select(
            'grade_grades',
            "itemid = :itemid AND userid > 0 AND (rawgrade IS NOT NULL OR finalgrade IS NOT NULL)",
            ['itemid' => $itemid],
            "COUNT(DISTINCT userid)"
        );

        $stats->requiremarking = max(0, $stats->submitted - $stats->marked);

        return $stats;
    }

    protected static function get_student_count(int $courseid): int {
        global $DB;
        static $counts = [];

        if (isset($counts[$courseid])) {
            return $counts[$courseid];
        }

        $context = \context_course::instance($courseid);
        $sql = "SELECT COUNT(DISTINCT ra.userid)
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE r.archetype = :archetype
                   AND ra.contextid = :ctxid";

        $counts[$courseid] = (int) $DB->count_records_sql($sql, [
            'archetype' => 'student',
            'ctxid'     => $context->id,
        ]);

        return $counts[$courseid];
    }

    /**
     * Return due date for a mod.
     *
     * @param cm_info $cm
     * @return int
     */
    public static function get_duedate(\cm_info $cm): int {
        global $DB;
        $customdata = (array) $cm->customdata;

        switch ($cm->modname) {
            case 'assign':
                $index = 'duedate';
                break;

            case 'coursework':
                // Check Coursework record has deadline.
                $deadline = $DB->get_field(
                    'coursework',
                    'deadline',
                    ['id' => $cm->instance]
                );
                return !empty($deadline) ? (int) $deadline : 0;

            case 'lesson':
                $index = 'deadline';
                break;

            case 'lti':
                // Check LTI record has enddatetime.
                $enddatetime = $DB->get_field(
                    'report_feedback_tracker_lti',
                    'enddatetime',
                    ['instanceid' => $cm->instance]
                );
                return !empty($enddatetime) ? (int) $enddatetime : 0;

            case 'quiz':
                $index = 'timeclose';
                break;

            case 'workshop':
                $index = 'submissionend';
                break;

            default:
                return 0;
        }

        // Return custom data where available.
        return (int) ($customdata[$index] ?? 0);
    }
}
