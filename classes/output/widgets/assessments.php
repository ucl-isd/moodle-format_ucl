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
            $summative = $this->expand_turnitin_parts($summative, $mods);

            $context = context_course::instance($COURSE->id);
            $students = self::get_students($context);
            $template = new stdClass();
            $template->assessments = [];

            // Staff of learner display for template.
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
                        // Turinin specific handler.
                        $handler = new \format_ucl\output\widgets\assessment\turnitintooltwo(
                            $mod,
                            $s->turnitinpartno
                        );
                    } else {
                        // Other mods.
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
                            $readonly = $context->is_locked();
                            if ($isgroupmode || $readonly) {
                                $assess->groupmode = $isgroupmode;
                                $assess->readonly = $readonly;
                            } else {
                                $markingdata = $handler->get_staff_marking();
                                $handler->set_participants(users: $students);
                                $assess->hasstats = true;
                                $assess->expectedcount = $handler->get_participant_count();
                                $assess->submittedcount = $markingdata->submitted ?? 0;
                                $rawpercent = ($assess->expectedcount > 0) ? ($assess->submittedcount / $assess->expectedcount) * 100 : 0;
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
     * Get the list of students for the course.
     *
     * @param context $context
     * @return array List of user objects (IDs only).
     */
    protected static function get_students(\context $context): array {
        // We fetch the IDs for anyone with the student-level 'submit' capability.
        return get_enrolled_users(
            context: $context,
            withcapability: 'mod/assign:submit',
            userfields: 'u.id',
            onlyactive: true
        );
    }

    /**
     * Expand Turnitin assignments into separate parts.
     *
     * @param array $summative The original assessment records.
     * @param array $mods The course modules map.
     * @return array The expanded list of records.
     */
    protected function expand_turnitin_parts(array $summative, array $mods): array {
        global $DB;

        // Get Turnitin instance ids.
        $turnitinids = array_filter(array_map(function ($s) use ($mods) {
            return ($mods[$s->cmid]->modname === 'turnitintooltwo') ? $mods[$s->cmid]->instance : null;
        }, $summative));

        $allparts = [];
        if ($turnitinids) {
            [$insql, $params] = $DB->get_in_or_equal($turnitinids);
            $records = $DB->get_records_select(
                table: 'turnitintooltwo_parts',
                select: "turnitintooltwoid $insql",
                params: $params,
                sort: 'id ASC'
            );
            foreach ($records as $part) {
                $allparts[$part->turnitintooltwoid][] = $part;
            }
        }

        $expanded = [];
        foreach ($summative as $s) {
            $mod = $mods[$s->cmid] ?? null;
            $parts = ($mod && $mod->modname === 'turnitintooltwo') ? ($allparts[$mod->instance] ?? []) : [];

            // If no parts to expand, just keep the original and move on.
            if (empty($parts)) {
                $expanded[] = $s;
                continue;
            }

            // Expand multi-part Turnitin things.
            foreach ($parts as $index => $part) {
                $newpart = clone $s;
                $partno = $index + 1;

                $newpart->turnitinpartno = $partno;
                $newpart->partdtdue = (int) $part->dtdue;
                $newpart->displayname = (count($parts) > 1)
                ? ($mod->name . ' ' . ($part->partname ?: get_string('part', 'mod_turnitintooltwo') . " $partno"))
                : $mod->name;

                $expanded[] = $newpart;
            }
        }

        return $expanded;
    }
}
