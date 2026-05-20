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
 * Specialised restore for UCL course format
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class restore_format_ucl_plugin extends \restore_format_plugin {
    /**
     * Returns the paths to be handled by the plugin at course level
     */
    protected function define_course_plugin_structure(): array {
        $paths = [];
        $paths[] = new restore_path_element(
            'customcontact',
            $this->get_pathfor('/customcontacts/customcontact')
        );

        return $paths; // And we return the interesting paths.
    }

    /**
     * Restore a custom contact
     *
     * @param array $data
     * @return void
     */
    public function process_customcontact($data): void {
        global $DB;

        $data = (object)$data;
        /* We only process this information if the course we are restoring to
           has 'ucl' format (target format can change depending of restore options). */
        $format = $DB->get_field('course', 'format', ['id' => $this->task->get_courseid()]);
        if ($format != 'ucl') {
            return;
        }

        $oldid = $data->id;
        $data->courseid = $this->task->get_courseid();
        unset($data->id);
        $customcontact = new custom_contact(0, $data);
        $customcontact->save();
        $this->set_mapping('customcontact', $oldid, $customcontact->get('id'));

        // No need to annotate anything here.
    }
}
