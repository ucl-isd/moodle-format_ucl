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

namespace format_ucl\fixtures\format_ucl;

use format_ucl\hook\after_first_section_html;
use format_ucl\hook\before_first_section_html;

/**
 * Mock hook to add HTML content after the first section.
 *
 * @package   format_ucl
 * @copyright 2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class mock_callbacks {
    public static function before_first_section_html(before_first_section_html $hook): void {
        $hook->add_html('<div><p>Some before content</p></div>');
    }

    public static function after_first_section_html(after_first_section_html $hook): void {
        $hook->add_html('<div><p>Some after content</p></div>');
    }
}