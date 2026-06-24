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
 * Hook to allow subscribers to edit a subset of template variables.
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
final class extend_format_ucl_settings implements \core\hook\described_hook {
    /** @var array $options */
    private array $options;

    /**
     * Allows subscribers to edit a subset of template variables.
     *
     * @param bool|array $courseformatoptions
     * @param bool $foreditform
     */
    public function __construct(
        /** @var bool|array $courseformatoptions */
        public readonly bool|array $courseformatoptions,
        /** @var bool|array $foreditform */
        public readonly bool $foreditform,
    ) {
        $this->options = $this->courseformatoptions ?: [];
    }

    /**
     * Description of hook.
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Hook to add settings to UCL format.';
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
     * Add course format settings
     *
     * @param array $options
     * @return void
     */
    public function add_options(array $options): void {
        $this->options = $this->options ? array_merge($this->options, $options) : $options;
    }

    /**
     * Get course format_settings
     *
     * @return array
     */
    public function course_format_options() {
        return $this->options;
    }
}
