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
        $checker = new timechecker();

        // Never logged in.
        // Suspended users without archive table entry are included.
        $never = ["anonym9", "anonym10", "never_logged_in_1", "never_logged_in_2"];
        $returnnever = $checker->get_never_logged_in();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returnnever), $never);

        // To suspend.
        $suspend = ["to_suspend"];
        $returnsuspend = $checker->get_to_suspend();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returnsuspend), $suspend);

        // To reactivate.
        $reactivate = ["to_reactivate"];
        $returnreactivate = $checker->get_to_reactivate();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returnreactivate), $reactivate);

        // To delete.
        $delete = ["to_delete"];
        $returndelete = $checker->get_to_delete();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returndelete), $delete);


        set_config('suspendtime', 0.5, 'userstatus_timechecker');
        set_config('deletetime', 0.5, 'userstatus_timechecker');
        $newchecker = new timechecker();

        // To suspend.
        $suspend = ["to_suspend", "tu_id_1", "tu_id_2", "tu_id_3", "tu_id_4"];
        $returnsuspend = $newchecker->get_to_suspend();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returnsuspend), $suspend);

        // To reactivate.
        $reactivate = [];
        $returnreactivate = $newchecker->get_to_reactivate();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returnreactivate), $reactivate);

        // To delete.
        $delete = ["to_delete", "to_not_delete_one_day", "to_reactivate", "to_not_reactivate_username_taken"];
        $returndelete = $newchecker->get_to_delete();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returndelete), $delete);

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
