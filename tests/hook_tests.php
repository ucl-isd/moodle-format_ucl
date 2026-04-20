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

use core_renderer;
use moodle_page;

/**
 * Tests for format_ucl hooks.
 *
 * @package     format_ucl
 * @copyright   2026 Amanda Doughty <m.doughty@ucl.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \format_ucl\hooks
 */
final class hook_tests extends \advanced_testcase {
    /**
     * @var \stdClass|null
     */
    public ?\stdClass $course;

    /**
     * @return void
     */
    public function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course(['format' => 'ucl']);
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->course = null;
    }

    /**
     * @covers \format_ucl\hook\before_first_section_html
     */
    public function test_before_first_section_html_hooked(): void {
        require_once(__DIR__ . '/fixtures/format_ucl/mock_callbacks.php');

        \core\di::set(
            \core\hook\manager::class,
            \core\hook\manager::phpunit_get_instance([
                'test_plugin1' => __DIR__ . '/fixtures/format_ucl/hooks.php',
            ]),
        );

        $page = new moodle_page();
        $format = course_get_format($this->course);
        $renderer = $format->get_renderer($page);
        $outputclass = $format->get_output_classname('content');
        $widget = new $outputclass($format);

        $html = $widget->get_before_first_section_html($renderer);
        $this->assertIsString($html);
        $this->assertStringContainsString('Some before content', $html);
    }

    /**
     * @covers \format_ucl\hook\before_first_section_html
     */
    public function test_before_after_section_content_hooked(): void {
        require_once(__DIR__ . '/fixtures/format_ucl/mock_callbacks.php');

        \core\di::set(
            \core\hook\manager::class,
            \core\hook\manager::phpunit_get_instance([
                'test_plugin1' => __DIR__ . '/fixtures/format_ucl/hooks.php',
            ]),
        );

        $page = new moodle_page();
        $format = course_get_format($this->course);
        $renderer = $format->get_renderer($page);
        $outputclass = $format->get_output_classname('content');
        $widget = new $outputclass($format);

        $html = $widget->get_after_first_section_html($renderer);
        $this->assertIsString($html);
        $this->assertStringContainsString('Some after content', $html);
    }
}
