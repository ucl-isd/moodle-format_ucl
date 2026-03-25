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
 * Class for dealing with config settings relating to course format
 *
 * @package     format_ucl
 * @copyright   2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {
    /** @var \stdClass the config to use. */
    private \stdClass $config;
    /** @var int the default for the maximum number of sections suggested */
    public const MAX_SECTIONS = 16;

    /**
     * Create the config variable from the format_ucl config.
     */
    private function __construct() {
        $this->config = get_config('format_ucl');
    }

    /**
     * Create an instance of the class.
     *
     * @param bool $forcenew force new instance.
     * @return self
     */
    public static function instance($forcenew = false): self {
        static $instance;

        if (!$instance || $forcenew) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Return the maximum number of sections for the tip
     *
     * @return int
     */
    public function get_recommended_max_sections(): int {
        return (int) $this->config->recommendedmaxsections ?: self::MAX_SECTIONS;
    }
}
