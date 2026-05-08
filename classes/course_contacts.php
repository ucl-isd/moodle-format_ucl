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
namespace format_ucl;


/**
 * Stores course contact visibility
 *
 * @package    format_ucl
 * @copyright  2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_contacts {
    private const GROUP_IDNUMBER = 'format_ucl_course_contacts';

    private const GROUP_NAME = 'Course contacts';
    /**
     * Constructor
     *
     * @param int $courseid
     * @param int $userid
     */
    public function __construct(
        /** @var  int courseid */
        protected int $courseid,
        /** @var  int userid */
        protected int $userid,
        /** @var  string action */
        protected string $action,
    ) {
    }

    public function toggle_contact_visibility() {
        switch ($this->action) {
            case 'show':
                return $this->add_to_group();
            case 'hide':
                return $this->remove_from_group();
        }
    }

    protected function remove_from_group(): bool {
        // If no group exists do nothing.
        if (!$groupid = $this->get_group_id()) {
            return true;
        }

        return groups_remove_member($groupid, $this->userid);
    }

    protected function add_to_group(): bool {
        if (!$groupid = $this->get_group_id()) {
            return false;
        }

        return groups_add_member($groupid, $this->userid);
    }

    protected function create_group(): int {
        $group = new \stdClass();
        $group->name = self::GROUP_NAME;
        $group->idnumber = self::GROUP_IDNUMBER;
        $group->courseid = $this->courseid;
        $group->visibility = GROUPS_VISIBILITY_NONE;

        return groups_create_group($group);
    }

    protected function get_group_id(): int {
        // If there is no existing group then create one.
        $groupid = groups_get_group_by_idnumber($this->courseid, self::GROUP_IDNUMBER);

        if (!$groupid) {
            $this->create_group();
        }

        return $groupid;
    }
}