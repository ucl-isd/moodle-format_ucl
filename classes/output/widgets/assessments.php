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
     * Constructor.
     *
     * @param format_ucl $format The course format instance.
     */
    public function __construct(
        protected format_ucl $format,
    ) {
    }

    /**
     * Export data for the template.
     *
     * @param renderer_base $output The renderer.
     * @return array|stdClass The template data.
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $COURSE;

        if (!$course = $this->format->get_course()) {
            return [];
        }

        if ($summative = assess_type::get_assess_type_records_by_courseid($COURSE->id, "1")) {
            $modinfo = get_fast_modinfo($COURSE->id, $USER->id);
            $mods = $modinfo->get_cms(); // This is our safety map.

            // Check mods exists.
            $summative = array_filter($summative, function ($s) use ($mods) {
                return $s->cmid != 0 && isset($mods[$s->cmid]);
            });

            if (empty($summative)) {
                return [];
            }

            // Expand Turnitin assignments into individual parts.
            $summative = $this->expand_turnitin_parts($summative, $modinfo);

            $studentcount = self::get_student_count($COURSE->id);
            $template = new stdClass();
            $template->assessments = [];

            // Staff of learner display for template.
            $context = context_course::instance($COURSE->id);
            $canedit = has_capability('moodle/course:update', $context);
            $template->staff = $canedit;
            $template->learner = !$canedit;

            foreach ($summative as $s) {
                // We use isset again just in case turnitin added something weird.
                if (!isset($mods[$s->cmid])) {
                    continue;
                }

                $mod = $mods[$s->cmid];

                if ($mod->uservisible && $mod->visible) {
                    if ($mod->modname === 'turnitintooltwo' && !empty($s->turnitinpartno)) {
                        $handler = new \format_ucl\output\widgets\assessment\turnitintooltwo(
                            $mod,
                            $s->turnitinpartno
                        );
                    } else {
                        $handler = \format_ucl\output\widgets\assessment\assess_base::instance($mod);
                    }

                    $duedate = $handler->get_activity_duedate();

                    if ($duedate) {
                        $assess = new stdClass();
                        $assess->duedate = $duedate;
                        $modname = 'mod' . $mod->modname;
                        $assess->$modname = true;
                        $assess->url = new moodle_url('/mod/' . $mod->modname . '/view.php', ['id' => $mod->id]);
                        $assess->name = $s->displayname ?? $mod->name;
                        $assess->icon = $mod->get_icon_url()->out(false);

                        $sectioninfo = $mod->get_section_info();
                        $assess->section = get_section_name($COURSE, $sectioninfo);

                        if ($canedit) {
                            $isgroupmode = (bool) groups_get_activity_groupmode($mod);
                            $hasrestrictions = (!empty($mod->groupingid) || !empty($mod->availability));

                            if ($isgroupmode || $hasrestrictions) {
                                $assess->groupmode = $isgroupmode;
                                $assess->hasrestrictions = $hasrestrictions;
                            } else {
                                $markingdata = $handler->get_staff_marking();
                                $assess->hasstats = true;
                                $assess->expectedcount = $studentcount;
                                $assess->submittedcount = $markingdata->submitted ?? 0;
                                $rawpercent = ($studentcount > 0) ? ($assess->submittedcount / $studentcount) * 100 : 0;
                                $assess->percent = floatval(round($rawpercent, 2));
                                $assess->requiremarking = $markingdata->requiremarking ?? 0;
                            }
                        }

                        if (!$canedit) {
                            $markdata = $handler->get_learner_mark();
                            $assess->submitted = $markdata->submitted ?? false;

                            if (empty($markdata->mark) && !$assess->submitted) {
                                if (time() > $assess->duedate) {
                                    $assess->overdue = true;
                                }
                            }

                            if ($markdata->mark !== null) {
                                $assess->hasmark = true;
                                if (is_numeric($markdata->mark)) {
                                    $assess->mark = floatval(round($markdata->mark, 2));
                                    if (!empty($markdata->max)) {
                                        $assess->max = floatval(round($markdata->max, 2));
                                    }
                                } else {
                                    $assess->mark = $markdata->mark;
                                }
                            }
                        }
                        $template->assessments[] = $assess;
                    }
                }
            }

            usort($template->assessments, function ($a, $b) {
                return $a->duedate <=> $b->duedate;
            });

            foreach ($template->assessments as $assess) {
                $assess->duedatedate = date('jS M', $assess->duedate);
                $assess->duedatetime = userdate($assess->duedate, '%I:%M%P');
            }

            return $template;
        }
        return [];
    }

    /**
     * Expand Turnitin assignments into separate parts.
     *
     * @param array $summative The original assessment records.
     * @param \course_modinfo $modinfo The course modinfo.
     * @return array The expanded list of records.
     */
    protected function expand_turnitin_parts(array $summative, \course_modinfo $modinfo): array {
        global $DB;
        $expanded = [];
        $tiiinstances = [];
        $cms = $modinfo->get_cms();

        foreach ($summative as $s) {
            // Internal safety: skip if CMID is missing in modinfo.
            if (!isset($cms[$s->cmid])) {
                continue;
            }
            $mod = $cms[$s->cmid];
            if ($mod->modname === 'turnitintooltwo') {
                $tiiinstances[] = $mod->instance;
            }
        }

        $allparts = [];
        if (!empty($tiiinstances)) {
            [$insql, $inparams] = $DB->get_in_or_equal($tiiinstances);
            $partsrecords = $DB->get_records_select(
                'turnitintooltwo_parts',
                "turnitintooltwoid $insql",
                $inparams,
                'turnitintooltwoid ASC, partorder ASC'
            );
            foreach ($partsrecords as $part) {
                $allparts[$part->turnitintooltwoid][] = $part;
            }
        }

        foreach ($summative as $s) {
            if (!isset($cms[$s->cmid])) {
                $expanded[] = $s;
                continue;
            }

            $mod = $cms[$s->cmid];

            if ($mod->modname !== 'turnitintooltwo' || !isset($allparts[$mod->instance])) {
                $expanded[] = $s;
                continue;
            }

            $parts = $allparts[$mod->instance];
            $numparts = count($parts);

            foreach ($parts as $index => $part) {
                $partrecord = clone $s;
                $partno = $index + 1;
                $partrecord->turnitinpartno = $partno;
                $partrecord->partdtdue = (int) $part->dtdue;

                if ($numparts > 1) {
                    $partname = !empty($part->partname) ? $part->partname : get_string('part', 'mod_turnitintooltwo') . ' ' . $partno;
                    $partrecord->displayname = $mod->name . ' ' . $partname;
                } else {
                    $partrecord->displayname = $mod->name;
                }
                $expanded[] = $partrecord;
            }
        }
        return $expanded;
    }

    /**
     * Get the number of students enrolled in the course.
     *
     * @param int $courseid The course ID.
     * @return int The student count.
     */
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
}
