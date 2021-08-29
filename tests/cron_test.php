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
 * Test script for the moodle tool_cleanupusers plugin.
 *
 * @package    tool_cleanupusers
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cleanupusers;

use ArrayIterator;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/testable_archive_user_task.php');

/**
 * Testcase class for executing phpunit test for the moodle tool_cleanupusers plugin.
 *
 * @package    tool_cleanupusers
 * @group      tool_cleanupusers
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_test extends \advanced_testcase {

    public function test_calculateuseractions() {
        $task = new task\testable_archive_user_task();

        $actionits = [
                useraction::REACTIVATE => new ArrayIterator([1, 4, 7, 9, 11]),
                useraction::SUSPEND => new ArrayIterator([2, 5, 7, 8, 9, 12]),
                useraction::DELETE => new ArrayIterator([3, 6, 8, 10])
        ];

        $delayits = [
                useraction::REACTIVATE => new ArrayIterator([4, 9, 12]),
                useraction::SUSPEND => new ArrayIterator([5, 11]),
                useraction::DELETE => new ArrayIterator([6, 12])
        ];

        $globalits = new ArrayIterator([10, 11]);

        $result = $task->calculate_useractions($actionits, $delayits, $globalits);
        $this->assertEquals([1, 7], $result[useraction::REACTIVATE]);
        $this->assertEquals([2, 8, 9, 12], $result[useraction::SUSPEND]);
        $this->assertEquals([3], $result[useraction::DELETE]);
    }

    public function test_updatedb() {
        $this->resetAfterTest();

        $u = [];
        for ($i = 0; $i < 10; $i++) {
            $u[] = $this->getDataGenerator()->create_user()->id;
        }

        $desiredstate = [
                useraction::REACTIVATE => [$u[0], $u[3]],
                useraction::SUSPEND => [$u[1], $u[4]],
                useraction::DELETE => [$u[2], $u[5]]
        ];

        $task = new task\testable_archive_user_task();

        $task->update_approve_db($desiredstate);
        $this->assert_desired_state_in_db($desiredstate);

        $desiredstate2 = [
                useraction::REACTIVATE => [$u[3], $u[6]],
                useraction::SUSPEND => [$u[5], $u[7]],
                useraction::DELETE => [$u[4], $u[8]]
        ];

        $task->update_approve_db($desiredstate2);
        $this->assert_desired_state_in_db($desiredstate2);

        $task->update_approve_db($desiredstate);
        $this->assert_desired_state_in_db($desiredstate);
    }

    private function assert_desired_state_in_db($desiredstate) {
        global $DB;

        $actualstate = [
                useraction::REACTIVATE => $DB->get_fieldset_select('tool_cleanupusers_approve', 'userid',
                        'action = :action ORDER BY userid ASC', ['action' => useraction::REACTIVATE]),
                useraction::SUSPEND => $DB->get_fieldset_select('tool_cleanupusers_approve', 'userid',
                        'action = :action ORDER BY userid ASC', ['action' => useraction::SUSPEND]),
                useraction::DELETE => $DB->get_fieldset_select('tool_cleanupusers_approve', 'userid',
                        'action = :action ORDER BY userid ASC', ['action' => useraction::DELETE]),
        ];

        $this->assertEquals($desiredstate, $actualstate);
    }

}
