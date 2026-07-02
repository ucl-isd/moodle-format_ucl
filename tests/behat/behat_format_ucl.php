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

declare(strict_types=1);

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use local_assess_type\assess_type;

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../tests/behat/behat_course.php');

/**
 * Behat step definitions for UCL course format
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class behat_format_ucl extends behat_base {
    /**
     * Return the list of partial named selectors
     *
     * @return behat_component_named_selector[]
     */
    public static function get_partial_named_selectors(): array {
        // TODO We will implement some once the html is finalised.
        return [];
    }

    /**
     * Opens a section edit menu if it is not already opened.
     *
     * @Given /^I open ucl section "(?P<section>(?:[^"]|\\")*)" edit menu$/
     * @param string|int $section
     */
    public function i_open_ucl_section_edit_menu($section) {
        if (!$this->running_javascript()) {
            throw new DriverException('Section edit menu not available when Javascript is disabled');
        }

        // Wait for section to be available, before clicking on the menu.
        $this->execute('behat_course::i_wait_until_section_is_available', [$section]);

        $xpath = "//div[contains(@class, 'section-actions')]/descendant::a[@data-toggle='dropdown']";
        $exception = new ExpectationException('Section "' . $section . '" was not found', $this->getSession());
        $menu = $this->find('xpath', $xpath, $exception);
        $menu->click();
    }

    /**
     * Mark an activity as summative in local_assess_type.
     *
     * @Given /^the activity with idnumber "(?P<idnumber>(?:[^"\\]|\\.)*)" in course "(?P<courseshortname>(?:[^"\\]|\\.)*)" is marked as summative$/
     * @param string $idnumber
     * @param string $courseshortname
     */
    public function the_activity_with_idnumber_in_course_is_marked_as_summative(string $idnumber, string $courseshortname): void {
        global $DB;

        if (!class_exists(assess_type::class)) {
            throw new \RuntimeException('local_assess_type plugin is required for this step.');
        }

        $course = $DB->get_record('course', ['shortname' => $courseshortname], 'id', MUST_EXIST);
        $cm = $DB->get_record('course_modules', ['course' => $course->id, 'idnumber' => $idnumber], 'id', MUST_EXIST);

        // 1 = summative (local_assess_type\assess_type::ASSESS_TYPE_SUMMATIVE).
        assess_type::update_type((int)$course->id, 1, (int)$cm->id);
    }
}
