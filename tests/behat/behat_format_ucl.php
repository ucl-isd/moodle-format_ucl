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

use Behat\Mink\Exception\ExpectationException;

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../tests/behat/behat_course.php');

/**
 * Behat step definitions for UCL course format
 *
 * @package     format_ucl
 * @copyright   2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

        // If it is already opened we do nothing.
        $xpath = "//div[contains(@class, 'section-actions')]/descendant::a[@data-bs-toggle='dropdown']";

        $exception = new ExpectationException('Section "' . $section . '" was not found', $this->getSession());
        $menu = $this->find('xpath', $xpath, $exception);
        $menu->click();
        $this->execute('behat_course::i_wait_until_section_is_available', [$section]);
    }
}
