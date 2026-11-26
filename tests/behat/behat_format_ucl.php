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
use Behat\Gherkin\Node\TableNode;

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
     * Wait until Moodle's TinyMCE API has loaded.
     *
     * @Then /^TinyMCE should be preloaded$/
     * @return void
     */
    public function tinymce_should_be_preloaded(): void {
        $exception = new ExpectationException('TinyMCE was not preloaded', $this->getSession());
        $this->spin(
            static function ($context) {
                return $context->evaluate_script('return Boolean(window.tinyMCE);');
            },
            false,
            false,
            $exception
        );
    }

    /**
     * Add the fixture image to a course's overview files.
     *
     * @Given /^the course "([^"]*)" has an image$/
     * @param string $shortname Course short name
     */
    public function the_course_has_an_image(string $shortname): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        get_file_storage()->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'stamptown.jpg',
        ], __DIR__ . '/../fixtures/stamptown.jpg');
        cache::make('core', 'course_image')->delete($course->id);
    }

    /**
     * Create a custom contact for a course.
     *
     * @Given /^the course "([^"]*)" has the custom contact:$/
     * @param string $shortname Course short name
     * @param TableNode $data Contact properties
     */
    public function the_course_has_the_custom_contact(string $shortname, TableNode $data): void {
        global $DB;

        $courseid = $DB->get_field('course', 'id', ['shortname' => $shortname], MUST_EXIST);
        $properties = $data->getRowsHash();
        $properties['courseid'] = $courseid;
        (new \format_ucl\local\data\custom_contact(0, (object)$properties))->create();
    }

    /**
     * Set the summary of the initial course section.
     *
     * @Given /^the course "([^"]*)" has the initial section summary "([^"]*)"$/
     * @param string $shortname Course short name
     * @param string $summary Section summary
     */
    public function the_course_has_the_initial_section_summary(string $shortname, string $summary): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
        $section = course_get_format($course)->get_modinfo()->get_section_info(0, MUST_EXIST);
        course_update_section($course, $section, (object)[
            'summary' => $summary,
            'summaryformat' => FORMAT_HTML,
        ]);
    }

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

        $xpath = "//div[contains(@class, 'section-actions')]/descendant::a[@data-bs-toggle='dropdown']";
        $exception = new ExpectationException('Section "' . $section . '" was not found', $this->getSession());
        $menu = $this->find('xpath', $xpath, $exception);
        $menu->click();
    }
}
