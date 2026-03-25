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

namespace format_ucl\local;

use core\hook\output\before_http_headers;
use core\hook\output\extend_url;
use moodle_url;

/**
 * Hook callbacks for format_ucl
 *
 * @package   format_ucl
 * @copyright 2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class hook_callbacks {
    /**
     * Override setting in course/section.php
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $CFG, $PAGE, $COURSE;

        require_once($CFG->dirroot . '/course/format/lib.php');

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            return;
        }

        /** @var \format_ucl $format */
        $format = course_get_format($COURSE);
        if ($format->get_format() !== 'ucl') {
            return;
        }

        $newsectionredirect = new \moodle_url('/course/format/ucl/newsectionredirect.php');
        if ($PAGE->has_set_url() && $PAGE->url->compare($newsectionredirect, URL_MATCH_BASE)) {
            $sectionnum = $PAGE->url->get_param('section');
            $courseid = $PAGE->url->get_param('courseid');
            $modinfo = get_fast_modinfo($courseid);
            if (!$sectioninfo = $modinfo->get_section_info($sectionnum)) {
                redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
            }
            redirect(new moodle_url('/course/editsection.php', ['id' => $sectioninfo->id]));
        }

        $sectionurl = new \moodle_url('/course/section.php');
        if ($PAGE->has_set_url() && $PAGE->url->compare($sectionurl, URL_MATCH_BASE)) {
            $PAGE->set_heading('');
        }
    }
}