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

namespace format_ucl\courseformat;

use core_courseformat\local\sectionactions as sectionactions_base;
use stdClass;

/**
 * Contains the core course state actions specific to ucl format.
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
class sectionactions extends sectionactions_base {
    /**
     * Creates a course section and adds it to the end
     *
     * This method returns a section record, not a section_info object. This prevents the regeneration
     * of the modinfo object each time we create a section.
     *
     * @param stdClass|null $data
     * @return stdClass created section object
     */
    public function create_from_form(?stdClass $data): stdClass {
        return $this->create_from_object($data, true);
    }
}
