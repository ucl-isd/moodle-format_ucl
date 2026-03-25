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

namespace format_ucl\output\courseformat\content;

use core\output\renderer_base;
use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section as section_base;
use section_info;
use stdClass;

/**
 * UCL content class.
 *
 * @package     format_ucl
 * @copyright   2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {
    /** @var string section navigation class name */
    protected $sectionnavigationclass;

    /** @var string section selector class name */
    protected $sectionselectorclass;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     * @param section_info $section the section info
     */
    public function __construct(course_format $format, section_info $section) {
        parent::__construct($format, $section);

        $this->sectionnavigationclass = $format->get_output_classname('content\\sectionnavigation');
        $this->sectionselectorclass = $format->get_output_classname('content\\sectionselector');
    }

    /**
     * Returns the output class template path.
     *
     * This method redirects the default template when the course content is rendered.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_ucl/local/content/section';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = parent::export_for_template($output);

        $sectionnumber = $this->section->section;
        $sectionnavigation = new $this->sectionnavigationclass($this->format, $sectionnumber);
        $data->sectionnavigation = $sectionnavigation->export_for_template($output);
        $sectionselector = new $this->sectionselectorclass($this->format, $sectionnavigation);
        $data->sectionselector = $sectionselector->export_for_template($output);

        return $data;
    }
}
