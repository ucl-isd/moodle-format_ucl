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

use format_ucl\local\data\custom_contact;

/**
 * Specialised backup for UCL course format
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class backup_format_ucl_plugin extends \backup_format_plugin {
    /**
     * Add custom contacts to the backup structure
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure(): backup_plugin_element {
        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'ucl');

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new \backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        // Custom contacts.
        $customcontacts = new \backup_nested_element('customcontacts');
        $pluginwrapper->add_child($customcontacts);

        // The courseid is not required as populated on restore.
        $customcontact = new \backup_nested_element('customcontact', ['id'], [
            'role', 'name', 'email', 'description',
        ]);
        $customcontacts->add_child($customcontact);

        $customcontact->set_source_table(
            custom_contact::TABLE,
            ['courseid'  => \backup::VAR_COURSEID]
        );

        return $plugin;
    }
}
