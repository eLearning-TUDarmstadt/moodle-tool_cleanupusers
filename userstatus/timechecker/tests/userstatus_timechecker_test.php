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
 * The class contains a test script for the moodle userstatus_timechecker
 *
 * @package    userstatus_timechecker
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userstatus_timechecker;
use advanced_testcase;

/**
 * The class contains a test script for the moodle userstatus_timechecker
 *
 * @package    userstatus_timechecker
 * @group      tool_cleanupusers
 * @group      tool_cleanupusers_timechecker
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \userstatus_timechecker\timechecker::get_to_suspend()
 * @covers \userstatus_timechecker\timechecker::get_never_logged_in()
 * @covers \userstatus_timechecker\timechecker::get_to_delete()
 * @covers \userstatus_timechecker\timechecker::get_to_reactivate()
 *
 */
class userstatus_timechecker_test extends advanced_testcase {
    /**
     * Create the data from the generator.
     * @return mixed
     */
    protected function set_up() {
        // Recommended in Moodle docs to always include CFG.
        global $CFG;
        $generator = $this->getDataGenerator()->get_plugin_generator('userstatus_timechecker');
        $data = $generator->test_create_preparation();
        $this->resetAfterTest(true);
        return $data;
    }
    /**
     * Function to test the class timechecker.
     *
     * @see timechecker
     */
    public function test_locallib() {
        $data = $this->set_up();
        $myuserstatuschecker = new timechecker();

        // Calls for sub-plugin functions to return arrays.
        $returnsuspend = $myuserstatuschecker->get_to_suspend();
        $returndelete = $myuserstatuschecker->get_to_delete();
        $returnneverloggedin = $myuserstatuschecker->get_never_logged_in();
        $returntoreactivate = $myuserstatuschecker->get_to_reactivate();

        $this->assertEquals($data['useroneyearnotlogedin']->id, $returnsuspend[$data['useroneyearnotlogedin']->id]->id);
        // We know from the testcase construction that only one user is deleted for this reason the user is at index 0.
        $this->assertEquals(
            $data['userarchivedoneyearnintydaysautomatically']->id,
            $returndelete[$data['userarchivedoneyearnintydaysautomatically']->id]->id
        );
        $this->assertEquals($data['neverloggedin']->id, $returnneverloggedin[$data['neverloggedin']->id]->id);
        // Merely id is compared since plugin only saves necessary data not complete user.
        $this->assertEquals($data['reactivate']->id, $returntoreactivate[$data['reactivate']->id]->id);
        $this->assertNotContains($data['user']->username, $returnsuspend);
        $this->assertNotContains($data['user']->username, $returndelete);
        $this->assertNotContains($data['user']->username, $returnneverloggedin);
        $this->assertNotContains($data['userfifteendays']->username, $returnsuspend);
        $this->assertNotContains($data['userfifteendays']->username, $returndelete);
        $this->assertNotContains($data['userfifteendays']->username, $returnneverloggedin);

        // Userarchived is not in array since time is not right.
        set_config('suspendtime', 400, 'userstatus_timechecker');
        set_config('deletetime', 730, 'userstatus_timechecker');
        $newstatuschecker = new timechecker();
        $returnsuspend = $newstatuschecker->get_to_suspend();
        $returndelete = $newstatuschecker->get_to_delete();
        $returnneverloggedin = $newstatuschecker->get_never_logged_in();

        $this->assertNotContains($data['user']->username, $returnsuspend);
        $this->assertNotContains($data['user']->username, $returndelete);
        $this->assertNotContains($data['user']->username, $returnneverloggedin);
        $this->assertNotContains($data['useroneyearnotlogedin']->username, $returnsuspend);
        $this->assertNotContains($data['useroneyearnotlogedin']->username, $returndelete);
        $this->assertNotContains($data['useroneyearnotlogedin']->username, $returnneverloggedin);
        $this->assertNotContains($data['userarchivedoneyearnintydaysautomatically']->username, $returnsuspend);
        $this->assertNotContains($data['userarchivedoneyearnintydaysautomatically']->username, $returndelete);
        $this->assertNotContains($data['userarchivedoneyearnintydaysautomatically']->username, $returnneverloggedin);
        $this->assertNotContains($data['userarchivedoneyearnintydaysmanually']->username, $returnsuspend);
        $this->assertNotContains($data['userarchivedoneyearnintydaysmanually']->username, $returndelete);
        $this->assertNotContains($data['userarchivedoneyearnintydaysmanually']->username, $returnneverloggedin);
        $this->assertEquals($data['neverloggedin']->id, $returnneverloggedin[$data['neverloggedin']->id]->id);

        set_config('suspendtime', 10, 'userstatus_timechecker');
        set_config('deletetime', 20, 'userstatus_timechecker');
        $newstatuschecker = new timechecker();
        $returnsuspend = $newstatuschecker->get_to_suspend();
        $returndelete = $newstatuschecker->get_to_delete();
        $returnneverloggedin = $newstatuschecker->get_never_logged_in();

        $this->assertEquals($data['useroneyearnotlogedin']->id, $returnsuspend[$data['useroneyearnotlogedin']->id]->id);
        $this->assertEquals($data['userfifteendays']->id, $returnsuspend[$data['userfifteendays']->id]->id);
        // We know from the testcase construction that only one user is deleted for this reason the user is at index 0.
        $this->assertEquals(
            $data['userarchivedoneyearnintydaysautomatically']->id,
            $returndelete[$data['userarchivedoneyearnintydaysautomatically']->id]->id
        );
        $this->assertNotContains($data['user']->username, $returnsuspend);
        $this->assertNotContains($data['user']->username, $returndelete);
        $this->assertNotContains($data['user']->username, $returnneverloggedin);
        $this->assertEquals($data['neverloggedin']->id, $returnneverloggedin[$data['neverloggedin']->id]->id);
        $this->resetAfterTest(true);
    }
    /**
     * Methodes recommended by moodle to assure database and dataroot is reset.
     */
    public function test_deleting() {
        global $DB;
        $this->resetAfterTest(true);
        $DB->delete_records('user');
        $DB->delete_records('tool_cleanupusers');
        $this->assertEmpty($DB->get_records('user'));
        $this->assertEmpty($DB->get_records('tool_cleanupusers'));
    }
    /**
     * Methodes recommended by moodle to assure database is reset.
     */
    public function test_user_table_was_reset() {
        global $DB;
        $this->assertEquals(2, $DB->count_records('user', []));
        $this->assertEquals(0, $DB->count_records('tool_cleanupusers', []));
    }
}
