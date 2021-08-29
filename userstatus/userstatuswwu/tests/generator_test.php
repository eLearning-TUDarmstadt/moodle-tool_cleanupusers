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
 * @package    userstatus_userstatuswwu
 * @category   test
 * @copyright  2017 Nina Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_userstatuswwu;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for the PHPUnit data generator testcase
 *
 * @package    userstatus_userstatuswwu
 * @category   test
 * @group      tool_cleanupusers
 * @group      tool_cleanupusers_userstatuswwu
 * @copyright  2017 Nina Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator_test extends \advanced_testcase {
    public function test_generator() {
        $this->resetAfterTest(true);
    }
}
