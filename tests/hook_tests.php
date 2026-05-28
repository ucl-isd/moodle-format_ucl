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

use core\di;
use core\hook\manager;
use format_ucl\fixtures\format_ucl\mock_callbacks;
use moodle_page;

/**
 * Tests for format_ucl hooks.
 *
 * @package     format_ucl
 * @copyright   2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Stuart Lamour <s.lamour@ucl.ac.uk>
 * @author      Amanda Doughty <m.doughty@ucl.ac.uk>
 */
final class hook_tests extends \advanced_testcase {
    /**
     * @var \stdClass|null
     */
    public ?\stdClass $course;

    /**
     * Called before each test.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course(['format' => 'ucl']);
    }

    /**
     * Called after each test.
     *
     * @return void
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->course = null;
    }

    /**
     * Tests the after_first_section_html hook.
     *
     */
    public function test_after_export_for_template_hooked(): void {
        require_once(__DIR__ . '/fixtures/format_ucl/mock_callbacks.php');

        di::set(
            manager::class,
            manager::phpunit_get_instance([
                'test_plugin1' => __DIR__ . '/fixtures/format_ucl/hooks.php',
            ]),
        );

        $page = new moodle_page();
        $format = course_get_format($this->course);

        $renderer = $format->get_renderer($page);
        $data = (object)['hookdataintrohtml' => ''];
        $outputclass = $format->get_output_classname('content');
        $widget = new $outputclass($format);
        $widget->after_export_for_template($renderer, $data);
        $this->assertStringContainsString('Some after content', $data->hookdataintrohtml);
    }

    /**
     * Tests the extend_format_ucl_settings hook.
     *
     */
    public function test_extend_format_ucl_settings_hooked(): void {
        require_once(__DIR__ . '/fixtures/format_ucl/mock_callbacks.php');

        di::set(
            manager::class,
            manager::phpunit_get_instance([
                'test_plugin1' => __DIR__ . '/fixtures/format_ucl/hooks.php',
            ]),
        );

        $format = course_get_format($this->course);
        $expected = mock_callbacks::get_options(true);
        $options = $format->course_format_options(true);
        $this->assertEqualsCanonicalizing($expected, $options);

        $expected = mock_callbacks::get_options(false);
        $options = $format->course_format_options();
        $this->assertEqualsCanonicalizing($expected, $options);
    }
}
