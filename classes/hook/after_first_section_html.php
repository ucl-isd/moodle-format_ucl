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

namespace format_ucl\hook;

/**
 * Hook to allow subscribers to add HTML content before the first section.
 *
 * @package   format_ucl
 * @copyright 2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Amanda Doughty <m.doughty@ucl.ac.uk>
 * @TODO use refactor to use a trait
 */
final class after_first_section_html implements \core\hook\described_hook {
    /**
     * Hook to allow subscribers to add HTML content before the first section.
     *
     * @param \renderer_base $renderer
     * @param string $output Initial output
     */
    public function __construct(
        /** @var \renderer_base The page renderer object */
        public readonly \renderer_base $renderer,
        /** @var string The collected output */
        private string $output = '',
    ) {
    }

    public static function get_hook_description(): string {
        return 'Hook dispatched at the end of rendering the first section in a course.';
    }

    public static function get_hook_tags(): array {
        return ['format'];
    }

    /**
     * Plugins implementing callback can add any HTML to the bottom of the first section.
     *
     * Must be a string containing valid html content.
     *
     * @param null|string $output
     */
    public function add_html(?string $output): void {
        if ($output) {
            $this->output .= $output;
        }
    }

    /**
     * Returns all HTML added by the plugins
     *
     * @return string
     */
    public function get_output(): string {
        return $this->output;
    }
}