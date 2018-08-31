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
defined('MOODLE_INTERNAL') || die();

use tool_cleanupusers\task;


/**
 * Testcase class for executing phpunit test for the moodle tool_cleanupusers plugin.
 *
 * @package    tool_cleanupusers
 * @group      tool_cleanupusers
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_cleanupusers_testcase extends advanced_testcase {

    protected function set_up() {
        // Recommended in Moodle docs to always include CFG.
        global $CFG;
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_cleanupusers');
        $data = $generator->test_create_preparation();
        $this->resetAfterTest(true);
        return $data;
    }

    /**
     * Function to test the the archive_me function in the archiveduser class.
     * @see \tool_cleanupusers\archiveduser
     */
    public function test_archiveduser_archiveme() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Users that are archived will be marked as suspended in the user table and  transfer their previous suspended
        // status in the tool_cleanupusers table.
        // Additionally they will be anomynised in the user table. Firstname will be 'Anonym', Username will be 'anonym + id'.
        // User is not suspended and did sign in.
        //  Username           |   signed in   | suspended manually | suspended by plugin | deleted
        // ------------------------------------------------------------------------------------------
        //  user               | yes           | no                 | no                  | no

        $neutraltosuspended = new \tool_cleanupusers\archiveduser($data['user']->id, $data['user']->suspended,
            $data['user']->lastaccess, $data['user']->username, $data['user']->deleted);
        $neutraltosuspended->archive_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['user']->id));
        $recordshadowtable = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['user']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(0, $recordshadowtable->suspended);
        $this->assertEquals(1, $recordtooltable->archived);
        $this->assertEquals('Anonym', $recordusertable->firstname);
        $this->assertEquals('anonym' . $data['user']->id, $recordusertable->username);

        $suspendedmanually = new \tool_cleanupusers\archiveduser($data['usersuspendedmanually']->id, $data['usersuspendedmanually']->suspended,
            $data['usersuspendedmanually']->lastaccess, $data['usersuspendedmanually']->username, $data['usersuspendedmanually']->deleted);
        $suspendedmanually->archive_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['usersuspendedmanually']->id));
        $recordshadowtable = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['usersuspendedmanually']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['usersuspendedmanually']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(1, $recordshadowtable->suspended);
        $this->assertEquals(1, $recordtooltable->archived);
        $this->assertEquals('Anonym', $recordusertable->firstname);
        $this->assertEquals('anonym' . $data['usersuspendedmanually']->id, $recordusertable->username);

        $this->resetAfterTest(true);
    }

    /**
     * Function to test the the archive_me function in the archiveduser class.
     * @see \tool_cleanupusers\archiveduser
     */
    public function test_archiveduser_deleteme() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);
        // Users that are deleted will be marked as deleted in the user table.
        // The entry the tool_cleanupusers table will be deleted.
        //  Username              | signed in     | suspended manually | suspended by plugin | deleted
        // ------------------------------------------------------------------------------------------
        //  suspendedtodelete     | no            | yes                | no                  | no
        //  userdeleted           | oneyearago    | no                 | yes                 | yes

        $suspendedtodelete = new \tool_cleanupusers\archiveduser($data['usersuspendedbypluginandmanually']->id,
            $data['usersuspendedbypluginandmanually']->id, $data['usersuspendedbypluginandmanually']->lastaccess,
            $data['usersuspendedbypluginandmanually']->username, $data['usersuspendedbypluginandmanually']->deleted);
        $suspendedtodelete->delete_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['usersuspendedbypluginandmanually']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['usersuspendedbypluginandmanually']->id));
        $this->assertEquals(1, $recordusertable->deleted);
        $this->assertNotEquals($data['usersuspendedbypluginandmanually']->id, $recordusertable->username);
        $this->assertNotEmpty($recordusertable);
        $this->assertEmpty($recordtooltable);
        $this->resetAfterTest(true);
    }

    /**
     * Function to test the the activate_me function in the archiveduser class.
     * @see \tool_cleanupusers\archiveduser
     */
    public function test_archiveduser_activateme() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Users that are activated will be written with their original values to the 'user' table.
        // The records in the 'tool_cleanupuser' and 'toll_cleanupuser_archive' table will be deleted.
        //
        //  Username                         | signed in     | suspended manually | suspended by plugin | deleted
        // ----------------------------------------------------------------------------------------------------
        //  usersuspendedbypluginandmanually | tendaysago    | yes                | yes                 | no
        //  usersuspendedbyplugin            | oneyearago    | yes                | yes                 | no

        $usersuspendedbyplugin = new \tool_cleanupusers\archiveduser($data['usersuspendedbyplugin']->id,
            $data['usersuspendedbyplugin']->suspended, $data['usersuspendedbyplugin']->lastaccess,
            $data['usersuspendedbyplugin']->username, $data['usersuspendedbyplugin']->deleted);

        $usersuspendedbyplugin->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['usersuspendedbyplugin']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['usersuspendedbyplugin']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['usersuspendedbyplugin']->id));
        $this->assertEquals($data['usersuspendedbyplugin']->username, $recordusertable->username);
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);

        // Since the Shadowtable states that the user was previously suspended he/she is marked as suspended in the ...
        // ... real table.
        $usersuspendedbypluginandmanually = new \tool_cleanupusers\archiveduser($data['usersuspendedbypluginandmanually']->id,
            $data['usersuspendedbypluginandmanually']->suspended, $data['usersuspendedbypluginandmanually']->lastaccess,
            $data['usersuspendedbypluginandmanually']->username, $data['usersuspendedbypluginandmanually']->deleted);
        $usersuspendedbypluginandmanually->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['usersuspendedbypluginandmanually']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['usersuspendedbypluginandmanually']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['usersuspendedbypluginandmanually']->id));
        $this->assertEquals($data['usersuspendedbypluginandmanually']->username, $recordusertable->username);
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);

        $this->resetAfterTest(true);
    }

    /**
     * Tries to archive users which cannot be archived and therefore throws exception.
     * @throws \tool_cleanupusers\cleanupusers_exception
     * @throws dml_exception
     */
    public function test_exception_archiveme () {
        global $DB, $USER;
        $data = $this->set_up();
        $this->assertNotEmpty($data);
        $this->setAdminUser();
        // Admin Users will not be archived
        $this->setAdminUser();
        $adminaccount = new \tool_cleanupusers\archiveduser($USER->id, $USER->suspended,
            $USER->lastaccess, $USER->username, $USER->deleted);
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to suspend user');
        $adminaccount->archive_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $USER->id));
        $this->assertEmpty($recordtooltable);

        $this->resetAfterTest(true);
    }

    /**
     * Tries to delete users which cannot be deleted and therefore throws exception.
     * @throws \tool_cleanupusers\cleanupusers_exception
     * @throws dml_exception
     */
    public function test_exception_deleteme ()
    {
        global $DB, $USER;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Trying to delete a user that is already deleted will throw a exception.
        $alreadydeleted = new \tool_cleanupusers\archiveduser($data['userdeleted']->id, $data['userdeleted']->suspended,
            $data['userdeleted']->lastaccess, $data['userdeleted']->username, $data['userdeleted']->deleted);
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to delete user');
        $alreadydeleted->delete_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['userdeleted']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['userdeleted']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['userdeleted']->id));
        $this->assertEquals(1, $recordusertable->deleted);
        $this->assertEquals($data['userdeleted']->username, $recordusertable->username);
        $this->assertEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);

        // Deleting a user who was inconsistently stored by the plugin (only in one table) will throw an exception.
        $alreadydeleted = new \tool_cleanupusers\archiveduser($data['userinconsistentsuspended']->id,
            $data['userinconsistentsuspended']->suspended, $data['userinconsistentsuspended']->lastaccess,
            $data['userinconsistentsuspended']->username, $data['userinconsistentsuspended']->deleted);
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to delete user');
        $alreadydeleted->delete_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['userinconsistentsuspended']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['userinconsistentsuspended']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['userinconsistentsuspended']->id));
        $this->assertEquals(1, $recordusertable->deleted);
        $this->assertEquals($data['userinconsistentsuspended']->username, $recordusertable->username);
        $this->assertNotEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);

        // Admins can not be deleted.
        $this->setAdminUser();
        $adminaccount = new \tool_cleanupusers\archiveduser($USER->id, $USER->suspended,
            $USER->lastaccess, $USER->username, $USER->deleted);
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to delete user');
        $adminaccount->delete_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $USER->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $USER->id));
        $recordusertable = $DB->get_record('user', array('id' => $USER->id));
        $this->assertEquals(1, $recordusertable->deleted);
        $this->assertEquals($USER->username, $recordusertable->username);
        $this->assertEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);

        $this->resetAfterTest(true);
    }

    /**
     * Tries to reactivate users which cannot be reactivated and therefore throws exception.
     * @throws \tool_cleanupusers\cleanupusers_exception
     * @throws dml_exception
     */
    public function test_exception_activateme () {
        global $DB, $USER;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Admins can not be deleted.
        $this->setAdminUser();
        $adminaccount = new \tool_cleanupusers\archiveduser($USER->id, $USER->suspended,
            $USER->lastaccess, $USER->username, $USER->deleted);
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to activate user');
        $adminaccount->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $USER->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $USER->id));
        $recordusertable = $DB->get_record('user', array('id' => $USER->id));
        $this->assertEquals(1, $recordusertable->deleted);
        $this->assertEquals($USER->username, $recordusertable->username);
        $this->assertEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);
        
        // When entry in tool_cleanupusers_archive table is deleted user can not be updated.
        $useraccount = new \tool_cleanupusers\archiveduser($data['userinconsistentsuspended']->id,
            $data['userinconsistentsuspended']->suspended, $data['userinconsistentsuspended']->lastaccess,
            $data['userinconsistentsuspended']->username, $data['userinconsistentsuspended']->deleted);
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to activate user.');
        $useraccount->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['userinconsistentsuspended']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['userinconsistentsuspended']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['userinconsistentsuspended']->id));
        $this->assertEquals(1, $recordusertable->deleted);
        $this->assertEquals($data['userinconsistentsuspended']->username, $recordusertable->username);
        $this->assertNotEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);

        $this->resetAfterTest(true);
    }

    /**
     * Test the the sub-plugin_select_form.
     *
     * @see \tool_cleanupusers\subplugin_select_form
     */
    public function test_subpluginform() {
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Validation with existing sub-plugin returns true.
        $subpluginform = new tool_cleanupusers\subplugin_select_form();
        $validationdata = array ("subplugin" => 'timechecker');
        $return = $subpluginform->validation($validationdata, null);
        $this->assertEquals(true, $return);

        // Validation with non-existing sub-plugin returns an array with an errormessage.
        $validationdata = array ("subplugin" => 'nosubplugin');
        $return = $subpluginform->validation($validationdata, null);
        $errorarray = array('subplugin' => new tool_cleanupusers\cleanupusers_subplugin_exception
            (get_string('errormessagesubplugin', 'tool_cleanupusers')));
        $this->assertEquals($errorarray, $return);
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
        $this->assertEquals(2, $DB->count_records('user', array()));
        $this->assertEquals(0, $DB->count_records('tool_cleanupusers', array()));
    }
}