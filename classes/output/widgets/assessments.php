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

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use format_ucl;
use format_ucl\output\widgets\assessment\turnitintooltwo;
use local_assess_type\assess_type;
use moodle_url;
use stdClass;

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
        /** @var format_ucl Course format instance. */
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

        // Exit early if assess_type plugin is not installed.
        if (!class_exists(assess_type::class)) {
            return [];
        }

        // 1 = summative assessments only.
        $summatives = assess_type::get_assess_type_records_by_courseid($COURSE->id, "1");
        if ($summatives) {
            $modinfo = get_fast_modinfo($COURSE->id, $USER->id);
            $mods = $modinfo->get_cms();

            if (empty($mods)) {
                return [];
            }

            $summatives = $this->expand_turnitin_parts($summatives, $mods);

            $template = new stdClass();
            $template->hasassessments = false;
            $template->assessments = [];

            foreach ($summatives as $summative) {
                $assessitem = $this->build_assess_item($summative, $mods);
                if ($assessitem) {
                    $template->assessments[] = $assessitem;
                }
            }

            if (empty($template->assessments)) {
                return [];
            }

            $template->hasassessments = true;

            usort($template->assessments, function ($a, $b) {
                return $a->duedate <=> $b->duedate;
            });

            return $template;
        }
        return [];
    }

    /**
     * Build a single assessment item for the template.
     *
     * Returns null if the mod is not visible or has no due date.
     *
     * @param stdClass $summative The summative assessment record.
     * @param array $mods The course modules map.
     * @return stdClass|null The assessment item, or null if it should be skipped.
     */
    private function build_assess_item(stdClass $summative, array $mods): ?stdClass {
        global $COURSE;

        // We use isset just in case turnitin added something weird.
        if (!isset($mods[$summative->cmid])) {
            return null;
        }

        $mod = $mods[$summative->cmid];

        if (!$mod->uservisible || !$mod->visible) {
            return null;
        }

        $duedate = $this->get_due_date($summative, $mod);
        if (!$duedate) {
            return null;
        }

        $assess = new stdClass();
        $assess->duedate = $duedate;
        $assess->duedatedate = date('jS M Y', $duedate);
        $assess->duedatetime = userdate($duedate, '%I:%M%P');
        $assess->url = new moodle_url('/mod/' . $mod->modname . '/view.php', ['id' => $mod->id]);
        $assess->name = $summative->displayname ?? $mod->name;
        $assess->icon = $mod->get_icon_url()->out(false);
        $assess->section = get_section_name($COURSE, $mod->get_section_info());

        return $assess;
    }

    /**
     * Get the due date for a summative assessment.
     * SHAME - only needed, because of turnitin parts.
     *
     * For Turnitin parts we use the due date from expand_turnitin_parts.
     * Everything else uses the activity dates API.
     *
     * @param stdClass $summative The expanded assessment record.
     * @param \cm_info $mod The course module.
     * @return int Unix timestamp, or 0 if not found.
     */
    protected function get_due_date(stdClass $summative, \cm_info $mod): int {
        if ($mod->modname === 'turnitintooltwo' && !empty($summative->partdtdue)) {
            return (int) $summative->partdtdue;
        }

        return \format_ucl\output\widgets\assessment\assess_base::instance($mod)->get_activity_duedate();
    }

    /**
     * Expand Turnitin assignments into separate parts.
     *
     * @param array $summatives The original assessment records.
     * @param array $mods The course modules map.
     * @return array The expanded list of records.
     */
    protected function expand_turnitin_parts(array $summatives, array $mods): array {
        $expanded = [];

        foreach ($summatives as $summative) {
            $mod = $mods[$summative->cmid];
            if ($mod->modname !== 'turnitintooltwo') {
                $expanded[] = $summative;
                continue;
            }

            $turnitin = new turnitintooltwo($mod);
            $expanded = array_merge($expanded, $turnitin->expand_parts($summative));
        }

        return $expanded;
    }
}
