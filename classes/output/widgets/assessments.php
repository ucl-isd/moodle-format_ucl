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
                        $handler = \format_ucl\output\widgets\assessment\assess_base::instance($mod);
                        $duedate = $handler->get_activity_duedate();

                        // Check mod has a due date.
                        if ($duedate) {
                            $assess = new stdClass();
                            $assess->duedate = $duedate;
                            $modname = 'mod' . $mod->modname;
                            $assess->$modname = true;
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
                                    $markingdata = $handler->get_staff_marking();
                                    $assess->hasstats = true;
                                    $assess->expectedcount = $studentcount;
                                    $assess->submittedcount = $markingdata->submitted;
                                    // Calculate the raw percentage.
                                    $rawpercent = ($studentcount > 0) ? ($markingdata->submitted / $studentcount) * 100 : 0;
                                    // Remove trailing .0 or only show 2 decimal places.
                                    $assess->percent = floatval(round($rawpercent, 2));
                                    $assess->requiremarking = $markingdata->requiremarking;
                                }
                            }

                            // Student data.
                            if (!$canedit) {
                                $markdata = $handler->get_learner_mark();
                                $assess->submitted = $markdata->submitted;

                                // Overdue flag.
                                if (!$markdata->mark && !$assess->submitted) {
                                    $now = time();
                                    if (!empty($assess->duedate) && $now > $assess->duedate) {
                                        $assess->overdue = true;
                                    }
                                }

                                // Mark.
                                if ($markdata->mark !== null) {
                                    $assess->hasmark = true;
                                    if (is_numeric($markdata->mark)) {
                                        $assess->mark = floatval(round($markdata->mark, 2));
                                        if (!empty($markdata->max)) {
                                            $assess->max = floatval(round($markdata->max, 2));
                                        }
                                    } else {
                                        // Non-numeric mark (e.g., "Pass").
                                        $assess->mark = $markdata->mark;
                                    }
                                }
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
                $assess->duedatedate = date('jS M', $assess->duedate); // Leon will not like this.
                $assess->duedatetime = userdate($assess->duedate, '%I:%M%P');
            }

            // Return data.
            return $template;
        }
        // No summative assessments.
        return [];
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
    public static function get_activity_duedate(\cm_info $cm): int {
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

    /**
     * Get a due date for a user including optional overrides and extensions.
     *
     * @param \cm_info $cm
     * @param int $userid
     * @return false|int
     */
    public static function get_user_duedate(\cm_info $cm, int $userid): false|int {
        global $DB;

        $overridedate = false;

        switch ($cm->modname) {
            case 'assign':
                // Get individual override where available.
                $params = ['assignid' => $cm->instance, 'userid' => $userid];
                $overridedate = $DB->get_field('assign_overrides', 'duedate', $params);

                // If there is no individual override check for a group override date.
                if (!$overridedate) {
                    $usergroups = groups_get_user_groups($cm->course, $userid);
                    if (!empty($usergroups[0])) {
                        foreach ($usergroups[0] as $usergroupid) {
                            $params = ['assignid' => $cm->instance, 'groupid' => $usergroupid];
                            $overrideduedate = $DB->get_field('assign_overrides', 'duedate', $params);

                            if ($overrideduedate > $overridedate) {
                                $overridedate = $overrideduedate;
                            }
                        }
                    }
                }

                // Get individual extension where available.
                $params = ['assignment' => $cm->instance, 'userid' => $userid];
                $extensiondate = $DB->get_field('assign_user_flags', 'extensionduedate', $params);

                // Use the date that gives the most time to the student.
                if ($extensiondate > $overridedate) {
                    $overridedate = $extensiondate;
                }

                break;

            case 'coursework':
                // Get individual override where available.
                $params = [
                    'courseworkid' => $cm->instance,
                    'allocatableid' => $userid,
                    'allocatabletype' => 'user',
                ];
                $overridedate = $DB->get_field('coursework_person_deadlines', 'personaldeadline', $params);

                // If there is no individual override check for a group override date.
                if (!$overridedate) {
                    $usergroups = groups_get_user_groups($cm->course, $userid);
                    if (!empty($usergroups[0])) {
                        foreach ($usergroups[0] as $usergroupid) {
                            $params = [
                                'courseworkid' => $cm->instance,
                                'allocatableid' => $usergroupid,
                                'allocatabletype' => 'group',
                            ];
                            $overrideduedate = $DB->get_field('coursework_extensions', 'extended_deadline', $params);

                            if ($overrideduedate > $overridedate) {
                                $overridedate = $overrideduedate;
                            }
                        }
                    }
                }
                break;

            case 'lesson':
                $params = ['lessonid' => $cm->instance, 'userid' => $userid];
                $overridedate = $DB->get_field('lesson_overrides', 'deadline', $params);
                break;

            case 'quiz':
                $params = ['quiz' => $cm->instance, 'userid' => $userid];
                $overridedate = $DB->get_field('quiz_overrides', 'timeclose', $params);
                break;

            default:
                $overridedate = false;
                break;
        }

        return $overridedate ?: false;
    }
}
