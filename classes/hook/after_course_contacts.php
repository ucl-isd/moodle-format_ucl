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
 */
final class after_course_contacts implements \core\hook\described_hook {
    /**
     * Trait to allow subscribers to edit course contacts.
     *
     * @param \renderer_base $renderer
     * @param \stdClass|array $data
     * @param array $coursecontacts
     * @param string $output Initial output
     */
    public function __construct(
        /** @var \renderer_base The page renderer object */
        public readonly \renderer_base $renderer,
        /** @var \stdClass|array The template data */
        public readonly \stdClass|array $data,
        /** @var array The course contacts */
        public array $coursecontacts,
        /** @var string The collected output */
        private string $output = '',
    ) {
    }

    /**
     * Description of hook.
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Hook dispatched after creating course contact data.';
    }

    /**
     * List of tags that describe this hook.
     *
     * @return string[]
     */
    public static function get_hook_tags(): array {
        return ['format'];
    }

    /**
     * Allows subscribers to edit the course contacts
     *
     */
    public function set_course_contacts($coursecontacts) {
        $this->coursecontacts = $coursecontacts;
    }

    /**
     * Returns the course contacts edited by the plugins
     *
     * @return array
     */
    public function get_course_contacts(): array {
        return $this->coursecontacts;
    }
}
