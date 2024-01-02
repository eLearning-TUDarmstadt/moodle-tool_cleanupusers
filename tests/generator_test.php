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
/**
 * PHPUnit data generator tests
 *
 * @package    tool_cleanupusers
 * @copyright  2016/17 Nina Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cleanupusers;
use advanced_testcase;

/**
 * PHPUnit data class generator testcase
 *
 * @package    tool_cleanupusers
 * @group      tool_cleanupusers
 * @copyright  2016/17 Nina Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator_test extends advanced_testcase {
    /**
     * In the future might relly test the generator...
     * @return void
     * @covers \tool_cleanupusers_generator::test_create_preparation
     */
    public function test_generator() {
        $this->resetAfterTest(true);
    }
}
