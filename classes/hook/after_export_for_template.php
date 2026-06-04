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
final class after_export_for_template implements \core\hook\described_hook {
    /** @var array|string[] template variables which can be edited */
    private array $editableproperties = ['contactdata', 'assessments'];

    /** @var array|string[] template variables which can be augmented */
    private array $augmentableproperties = ['hookdataintrohtml'];

    /**
     * Allows subscribers to edit a subset of template variables.
     *
     * @param \renderer_base $renderer
     * @param \stdClass $data
     */
    public function __construct(
        /** @var \renderer_base The page renderer object */
        public readonly \renderer_base $renderer,
        /** @var \stdClass The template data */
        private \stdClass $data
    ) {
    }

    /**
     * Description of hook.
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Hook dispatched at the end of export_for_template function.';
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
     * Allows subscribers to edit template variables
     *
     * @param string $name
     * @param mixed $value
     * @throws \moodle_exception
     */
    public function set_property(string $name, mixed $value): void {
        $iseditable = in_array($name, $this->editableproperties, true);
        $isaugmentable = in_array($name, $this->augmentableproperties, true);
        if (!$iseditable && !$isaugmentable) {
            throw new \moodle_exception('The property "' . $name . '" is not writable.');
        }

        if (!property_exists($this->data, $name)) {
            throw new \moodle_exception('The property "' . $name . '" does not exist');
        }

        $this->data->$name = $iseditable ? $value : $this->data->$name . $value;
    }

    /**
     * Returns the template variable
     *
     * @param string $name
     * @return mixed
     */
    public function get_property(string $name): mixed {
        return property_exists($this->data, $name) ? $this->data->$name : null;
    }
}
