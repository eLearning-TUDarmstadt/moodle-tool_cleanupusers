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
 * Test for the moodle userstatus_userstatuswwu
 *
 * @package    userstatus_userstatuswwu
 * @category   test
 * @copyright  2017 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_userstatuswwu;

defined('MOODLE_INTERNAL') || die();

/**
 * The class contains a test script for the moodle userstatus_userstatuswwu
 *
 * @package    userstatus_userstatuswwu
 * @category   test
 * @group      tool_cleanupusers
 * @group      tool_cleanupusers_userstatuswwu
 * @copyright  2017 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userstatus_userstatuswwu_test extends \advanced_testcase {

    protected function set_up() {
        // Recommended in Moodle docs to always include CFG.
        global $CFG;
        $generator = $this->getDataGenerator()->get_plugin_generator('userstatus_userstatuswwu');
        $data = $generator->test_create_preparation();
        $this->resetAfterTest(true);
        return $data;
    }

    /**
     * Function to test the userstatuswwu class.
     */
    public function test_userstatuswwu() {
        global $CFG, $USER;
        $data = $this->set_up();
        $this->assertFileExists($CFG->dirroot .
            '/admin/tool/cleanupusers/userstatus/userstatuswwu/tests/fixtures/groups_excerpt_short.txt');

        $myuserstatuschecker = new userstatuswwu($CFG->dirroot .
            '/admin/tool/cleanupusers/userstatus/userstatuswwu/tests/fixtures/groups_excerpt_short.txt',
            array('member_group' => 'member_group', 'member' => 'member'));
        // Calls for plugin function to return array.
        $returnsuspend = $myuserstatuschecker->get_to_suspend();
        $returndelete = $myuserstatuschecker->get_to_delete();
        $returnneverloggedin = $myuserstatuschecker->get_never_logged_in();
        $returntoactivate = $myuserstatuschecker->get_to_reactivate();

        // Several users are generated.

        // E_user03 is an exampleuser who is member of one valid group two not valid groups.
        // Therefore he/she is not listed by the plugin.
        $this->assertNotContainsEquals($data['e_user03']->id, $returnsuspend);
        $this->assertNotContainsEquals($data['e_user03']->id, $returnneverloggedin);
        $this->assertNotContainsEquals($data['e_user03']->id, $returndelete);
        $this->assertNotContainsEquals($data['e_user03']->id, $returntoactivate);

        // S_other07 is in the .txt file member of one valid group and two not valid groups and suspended.
        // (sequence of the groups changes compared to e_user03).
        // Not in $todelete array since he/she is a valid groups member, listet as to reactivate.
        $this->assertNotContainsEquals($data['s_other07']->id, $returnsuspend);
        $this->assertNotContainsEquals($data['s_other07']->id, $returnneverloggedin);
        $this->assertNotContainsEquals($data['s_other07']->id, $returndelete);
        $this->assertContainsEquals($data['s_other07']->id, $returntoactivate);

        // Userm is in the .txt file but not member of a valid group.
        // Therefore he/she is listed in the $returntosuspend array.
        $this->assertContainsEquals($data['userm']->id, $returnsuspend);
        $this->assertNotContainsEquals($data['userm']->id, $returnneverloggedin);
        $this->assertNotContainsEquals($data['userm']->id, $returndelete);
        $this->assertNotContainsEquals($data['userm']->id, $returntoactivate);

        // R_theu9 never signed in and will not be handled, he is in a valid group.
        $this->assertNotContainsEquals($data['r_theu9']->id, $returnsuspend);
        $this->assertContainsEquals($data['r_theu9']->id, $returnneverloggedin);
        $this->assertNotContainsEquals($data['r_theu9']->id, $returndelete);
        $this->assertNotContainsEquals($data['r_theu9']->id, $returntoactivate);

        // N_loged4 never signed in and will not be handled, he is not in a valid group.
        $this->assertNotContainsEquals($data['n_loged4']->id, $returnsuspend);
        $this->assertContainsEquals($data['n_loged4']->id, $returnneverloggedin);
        $this->assertNotContainsEquals($data['n_loged4']->id, $returndelete);
        $this->assertNotContainsEquals($data['n_loged4']->id, $returntoactivate);

        // User is in the .txt file but not member of a valid group.
        // Therefore he will be in the $returntosuspend array.
        $this->assertContainsEquals($data['user']->id, $returnsuspend);
        $this->assertNotContainsEquals($data['user']->id, $returndelete);
        $this->assertNotContainsEquals($data['user']->id, $returnneverloggedin);
        $this->assertNotContainsEquals($data['user']->id, $returntoactivate);

        // D_me09 was suspended one year ninety days ago by the plugin, is not in the .txt file.
        // Therefore he is in the $returntodelete array.
        $this->assertContainsEquals($data['d_me09']->id, $returndelete);
        $this->assertNotContainsEquals($data['d_me09']->id, $returnsuspend);
        $this->assertNotContainsEquals($data['d_me09']->id, $returnneverloggedin);
        $this->assertNotContainsEquals($data['d_me09']->id, $returntoactivate);

        $this->setAdminUser();
        $this->assertNotContainsEquals($USER->id, $returnsuspend);
        $this->assertNotContainsEquals($USER->id, $returnneverloggedin);
        $this->assertNotContainsEquals($USER->id, $returndelete);
        $this->assertNotContainsEquals($USER->id, $returntoactivate);

        // Userstatuschecker uses default groups. Merely e_user03 is a valid member.
        $myuserstatuschecker = new userstatuswwu($CFG->dirroot .
            '/admin/tool/cleanupusers/userstatus/userstatuswwu/tests/fixtures/groups_excerpt_short.txt');
        $returnsuspend = $myuserstatuschecker->get_to_suspend();
        $returndelete = $myuserstatuschecker->get_to_delete();
        $returnneverloggedin = $myuserstatuschecker->get_never_logged_in();

        // Admin are still not handled.
        $this->setAdminUser();
        $this->assertNotContainsEquals($USER->id, $returnsuspend);
        $this->assertNotContainsEquals($USER->id, $returnneverloggedin);
        $this->assertNotContainsEquals($USER->id, $returndelete);
        $this->assertNotContainsEquals($USER->id, $returntoactivate);

        $this->assertNotContainsEquals($data['e_user03']->id, $returnsuspend);
        $this->assertNotContainsEquals($data['e_user03']->id, $returnneverloggedin);
        $this->assertNotContainsEquals($data['e_user03']->id, $returndelete);
        $this->assertNotContainsEquals($data['e_user03']->id, $returntoactivate);

        $this->assertContainsEquals($data['n_loged4']->id, $returnneverloggedin);
        $this->assertContainsEquals($data['user']->id, $returnsuspend);
        $this->assertContainsEquals($data['d_me09']->id, $returndelete);
        // S_other07 was previously in a valid group and listet as to reactivate is now also deleted.
        $this->assertContainsEquals($data['s_other07']->id, $returndelete);
        $this->resetAfterTest(true);

    }
    /**
     * Sets Config pathtotxt of the userstatuswwu plugin and assures the class constructor works without setting the
     * path.
     */
    public function test_set_config() {
        global $CFG;
        $data = $this->set_up();

        $this->assertFileExists($CFG->dirroot .
            '/admin/tool/cleanupusers/userstatus/userstatuswwu/tests/fixtures/groups_excerpt_short.txt');
        set_config('pathtotxt', $CFG->dirroot .
            '/admin/tool/cleanupusers/userstatus/userstatuswwu/tests/fixtures/groups_excerpt_short.txt',
            'userstatus_userstatuswwu');
        $userstatuswwu = new userstatuswwu();
        $returnsuspend = $userstatuswwu->get_to_suspend();
        $returndelete = $userstatuswwu->get_to_delete();
        $returnneverloggedin = $userstatuswwu->get_never_logged_in();

        $this->assertContainsEquals($data['d_me09']->id, $returndelete);
        $this->assertContainsEquals($data['user']->id, $returnsuspend);
        $this->assertContainsEquals($data['n_loged4']->id, $returnneverloggedin);

        // Several users are generated.
        $this->assertNotContainsEquals($data['e_user03']->id, $returnneverloggedin);
        $this->assertNotContainsEquals($data['e_user03']->id, $returnsuspend);
        $this->assertNotContainsEquals($data['e_user03']->id, $returndelete);
        $this->resetAfterTest(true);
    }

    /**
     * When the txt_path is null exception is thrown.
     */
    public function test_txtpath_null() {
        $this->expectException('userstatus_userstatuswwu\userstatuswwu_exception');
        $this->expectExceptionMessage('The path to the .txt file has to be set.');
        new userstatuswwu();

        $this->resetAfterTest(true);
    }

    /**
     * Test exception when file does not exist.
     */
    public function test_filenotexist() {
        global $CFG;
        $this->assertFileExists($CFG->dirroot .
            '/admin/tool/cleanupusers/userstatus/userstatuswwu/tests/fixtures/groups_excerpt_short.txt');

        $this->expectException('userstatus_userstatuswwu\userstatuswwu_exception');
        $this->expectExceptionMessage('The reference to the .txt could not be found.');
        new userstatuswwu($CFG->dirroot . '/somenotexistingpath.txt',
            array('member_group' => 'member_group', 'member' => 'member'));

    }

    /**
     * Function recommended by moodle to assure database and dataroot is reset.
     */
    public function test_deleting() {
        global $DB;
        $this->resetAfterTest(true);
        $DB->delete_records('user');
        $this->assertEmpty($DB->get_records('user'));
    }

    /**
     * Function recommended by moodle to assure database is reset.
     */
    public function test_user_table_was_reset() {
        global $DB;
        $this->assertEquals(2, $DB->count_records('user', array()));
    }
}