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

use format_ucl\hook\after_export_for_template;
use format_ucl\hook\extend_format_ucl_settings;

/**
 * Mock hook to add HTML content after the first section.
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class mock_callbacks {
    /**
     * Listener for the format_ucl after_first_section_html hook.
     *
     * @param after_export_for_template $hook
     * @return void
     */
    public static function after_export_for_template(after_export_for_template $hook): void {
        $hook->set_property('hookdataintrohtml', '<div><p>Some after content</p></div>');
    }

    /**
     * Listener for the format_ucl extend_format_ucl_settings hook.
     *
     * @param extend_format_ucl_settings $hook
     * @return void
     */
    public static function extend_format_ucl_settings(extend_format_ucl_settings $hook): void {
        $options = self::get_options($hook->foreditform);
        $hook->add_options($options);
    }

    /**
     * Get mock course format options
     *
     * @param bool $foreditform
     * @return array[]
     */
    public static function get_options(bool $foreditform): array {
        if ($foreditform) {
            return [
                'checkbox' => [
                    'default' => 1,
                    'type' => PARAM_INT,
                    'label' => 'Test checkbox',
                    'element_type' => 'checkbox',
                ],
            ];
        }

        return [
            'checkbox' => [
                'default' => 1,
                'type' => PARAM_INT,
            ],
        ];
    }
}
