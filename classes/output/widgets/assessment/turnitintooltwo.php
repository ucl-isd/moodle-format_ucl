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

namespace format_ucl\output\widgets\assessment;

/**
 * Turnitin assignment handler for UCL Course Format.
 *
 * @package    format_ucl
 * @copyright  2026 onwards University College London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class turnitintooltwo extends assess_base {
    /** @var array All sub-parts for this Turnitin instance. */
    protected array $parts = [];

    /**
     * Constructor override to preload Turnitin parts.
     *
     * @param \cm_info $cm
     * @param int|null $partno
     */
    public function __construct(
        \cm_info $cm,
        /** @var int|null Specific part number filter. */
        protected ?int $partno = null,
    ) {
        global $DB;
        parent::__construct($cm);

        // Simple request-level memoization.
        static $partscache = [];

        if (!isset($partscache[$this->cm->instance])) {
            $partscache[$this->cm->instance] = $DB->get_records(
                'turnitintooltwo_parts',
                ['turnitintooltwoid' => $this->cm->instance],
                'id ASC'
            );
        }

        $this->parts = $partscache[$this->cm->instance];
    }

    /**
     * Get the due date for the activity.
     *
     * @return int
     */
    public function get_activity_duedate(): int {
        if (!$this->parts) {
            return 0;
        }

        // Re-index to 0..N because DB IDs are unpredictable.
        $parts = array_values($this->parts);
        $index = ($this->partno !== null) ? ($this->partno - 1) : 0;

        return (int) ($parts[$index]->dtdue ?? 0);
    }

    /**
     * Expand a Turnitin parts for one record per part.
     *
     * @param \stdClass $summative The source summative record.
     * @return array
     */
    public function expand_parts(\stdClass $summative): array {
        $parts = array_values($this->parts);

        if (empty($parts)) {
            return [$summative];
        }

        $expanded = [];

        foreach ($parts as $index => $part) {
            $newpart = clone $summative;
            $partno = $index + 1;

            $newpart->turnitinpartno = $partno;
            $newpart->partdtdue = (int) $part->dtdue;
            $newpart->displayname = ($part->partname === '')
                ? $this->cm->name
                : $this->cm->name . ' - ' . $part->partname;

            $expanded[] = $newpart;
        }

        return $expanded;
    }
}
