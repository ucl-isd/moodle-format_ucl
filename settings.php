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
 * Plugin administration pages are defined here.
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */

use format_ucl\config;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('format_ucl_settings', new lang_string('pluginname', 'format_ucl'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // Advisory number of sections for Tip.
        $settings->add(new admin_setting_configtext(
            'format_ucl/recommendedmaxsections',
            get_string('config:recommendedmaxsections', 'format_ucl'),
            get_string('config:recommendedmaxsections:desc', 'format_ucl'),
            config::MAX_SECTIONS,
            PARAM_INT,
            2
        ));
        // TODO remove this setting when SL is happy contacts are working.
        $yesno = [
            0 => new lang_string('no'),
            1 => new lang_string('yes'),
        ];
        $settings->add(new admin_setting_configselect(
            'format_ucl/displaycontacts',
            new lang_string('config:displaycontacts', 'format_ucl'),
            new lang_string('config:displaycontacts:desc', 'format_ucl'),
            0,
            $yesno
        ));
    }
}
