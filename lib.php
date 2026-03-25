<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 *  Format base class.
 *
 * @package     format_ucl
 * @copyright   2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Defines the course format properties and behaviour.
 */
class format_ucl extends core_courseformat\base {
    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections(): bool {
        return true;
    }

    /**
     * Returns true if the format uses the legacy activity indentation.
     *
     * @return bool
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Returns true if the course format is compatible with the course index drawer.
     *
     * @return bool
     */
    public function uses_course_index(): bool {
        return true;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax(): stdClass {

        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;

        return $ajaxsupport;
    }

    /**
     * Returns true if the course is rendered using reactive UI components.
     *
     * @return bool
     */
    public function supports_components(): bool {
        return true;
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@see course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * @param int|stdClass|section_info $section Section object from database or just field course_sections.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        if (is_object($section)) {
            $sectionnum = $section->section;
        } else {
            $sectionnum = $section;
            $section = $this->get_section($sectionnum);
        }

        if ((string)$section->name !== '') {
            return format_string(
                $section->name,
                true,
                ['context' => context_course::instance($this->courseid)]
            );
        }

        if ($sectionnum == 0) {
            return get_string('section0name', 'format_ucl');
        }

        if (get_string_manager()->string_exists('sectionname', 'format_' . $this->format)) {
            return get_string('sectionname', 'format_' . $this->format) . ' ' . $sectionnum;
        }

        // Return an empty string if there's no available section name string for the given format.
        return '';
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * Please note that course view page /course/view.php?id=COURSEID is hardcoded in many
     * places in core and contributed modules. If course format wants to change the location
     * of the view script, it is not enough to change just this function. Do not forget
     * to add proper redirection.
     *
     * @param int|stdClass|section_info $section Section object from database or just field course_sections.section
     *     if null the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section not empty, the function returns section page; otherwise, it returns course page.
     *     'sr' (int) used by course formats to specify to which section to return
     *     'expanded' (bool) if true the section will be shown expanded, true by default
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = []) {
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);

        if (array_key_exists('sr', $options)) {
            $sectionno = $options['sr'];
        } else if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ((!empty($options['navigation']) || array_key_exists('sr', $options)) && (int)$sectionno > 0) {
            // Display section on separate page.
            $sectioninfo = $this->get_section($sectionno);
            return new moodle_url('/course/section.php', ['id' => $sectioninfo->id]);
        }
        return $url;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * This method is required for inplace seciton name editor.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_ucl_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'ucl'],
            MUST_EXIST
        );
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Section fragment renderer method.
 *
 * The fragment arguments are courseid, section id and sr (section return).
 *
 * @param array $args The fragment arguments.
 * @return string The rendered section.
 *
 * @throws require_login_exception
 */
function format_ucl_output_fragment_section_navigation($args): string {
    global $PAGE;

    $course = get_course($args['courseid']);
    if (!can_access_course($course, null, '', true)) {
        throw new require_login_exception('Course is not available');
    }

    $format = course_get_format($course);
    $modinfo = $format->get_modinfo();
    $section = $modinfo->get_section_info_by_id($args['id'], MUST_EXIST);
    if (!$section->uservisible) {
        throw new require_login_exception('Section is not available');
    }

    $renderer = $format->get_renderer($PAGE);
    return $renderer->course_section_navigation_updated($format, $section);
}

/**
 * Section fragment renderer method.
 *
 * The fragment arguments are courseid, section id and sr (section return).
 *
 * @param array $args The fragment arguments.
 * @return string The rendered section.
 *
 * @throws require_login_exception
 */
function format_ucl_output_fragment_section_divider($args): string {
    global $PAGE;

    $course = get_course($args['courseid']);
    if (!can_access_course($course, null, '', true)) {
        throw new require_login_exception('Course is not available');
    }

    $format = course_get_format($course);
    $modinfo = $format->get_modinfo();
    $section = $modinfo->get_section_info_by_id($args['id'], MUST_EXIST);
    if (!$section->uservisible) {
        throw new require_login_exception('Section is not available');
    }

    $renderer = $format->get_renderer($PAGE);
    return $renderer->course_section_add_cm_control($course, $section->section, $section->section);
}
