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
 * @group tool_cleanupusers
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
     * Function to test the the archive_me function in the archiveduser class. User used:
     * Username           |   signed in   | suspended manually | suspended by plugin | deleted
     * ------------------------------------------------------------------------------------------
     * user               | yes           | no                 | no                  | no
     * @see \tool_cleanupusers\archiveduser
     */
    public function test_archiveduser_archiveme() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Users that are archived will be marked as suspended in the user table and transfer their previous suspended
        // status in the tool_cleanupusers table.
        // Additionally they will be anomynised in the user table. Firstname will be 'Anonym', Username will be 'anonym + id'.

        $neutraltosuspended = new \tool_cleanupusers\archiveduser($data['user']->id, $data['user']->suspended,
            $data['user']->lastaccess, $data['user']->realusername, $data['user']->deleted);
        $neutraltosuspended->archive_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['user']->id));
        $recordshadowtable = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['user']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(0, $recordshadowtable->suspended);
        $this->assertEquals(1, $recordtooltable->archived);
        $this->assertEquals('Anonym', $recordusertable->firstname);
        $this->assertEquals('anonym' . $data['user']->id, $recordusertable->username);

        $suspendedmanually = new \tool_cleanupusers\archiveduser($data['usersuspendedmanually']->id,
            $data['usersuspendedmanually']->suspended, $data['usersuspendedmanually']->lastaccess,
            $data['usersuspendedmanually']->realusername, $data['usersuspendedmanually']->deleted);
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
     * Function to test the the archive_me function in the archiveduser class. Users used:
     *  Username              | signed in     | suspended manually | suspended by plugin | deleted
     * ------------------------------------------------------------------------------------------
     *  suspendedtodelete     | no            | yes                | no                  | no
     *  userdeleted           | oneyearago    | no                 | yes                 | yes
     * @see \tool_cleanupusers\archiveduser
     */
    public function test_archiveduser_deleteme() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);
        // Users that are deleted will be marked as deleted in the user table.
        // The entry the tool_cleanupusers table will be deleted.

        $suspendedtodelete = new \tool_cleanupusers\archiveduser($data['usersuspendedbypluginandmanually']->id,
            $data['usersuspendedbypluginandmanually']->id, $data['usersuspendedbypluginandmanually']->lastaccess,
            $data['usersuspendedbypluginandmanually']->realusername, $data['usersuspendedbypluginandmanually']->deleted);
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
     * Function to test the the activate_me function in the archiveduser class. Users used:
     * Username                         | signed in     | suspended manually | suspended by plugin | deleted
     * ----------------------------------------------------------------------------------------------------
     * usersuspendedbypluginandmanually | tendaysago    | yes                | yes                 | no
     * usersuspendedbyplugin            | oneyearago    | yes                | yes                 | no
     * @see \tool_cleanupusers\archiveduser
     */
    public function test_archiveduser_activateme() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Users that are activated will be written with their original values to the 'user' table.
        // The records in the 'tool_cleanupuser' and 'toll_cleanupuser_archive' table will be deleted.

        $usersuspendedbyplugin = new \tool_cleanupusers\archiveduser($data['usersuspendedbyplugin']->id,
            $data['usersuspendedbyplugin']->suspended, $data['usersuspendedbyplugin']->lastaccess,
            $data['usersuspendedbyplugin']->realusername, $data['usersuspendedbyplugin']->deleted);

        $usersuspendedbyplugin->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['usersuspendedbyplugin']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['usersuspendedbyplugin']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['usersuspendedbyplugin']->id));
        // Name changed.
        $this->assertNotEquals($data['usersuspendedbyplugin']->username, $recordusertable->username);
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);

        // Since the Shadowtable states that the user was previously suspended he/she is marked as suspended in the ...
        // ... real table.
        $usersuspendedbypluginandmanually = new \tool_cleanupusers\archiveduser($data['usersuspendedbypluginandmanually']->id,
            $data['usersuspendedbypluginandmanually']->suspended, $data['usersuspendedbypluginandmanually']->lastaccess,
            $data['usersuspendedbypluginandmanually']->realusername, $data['usersuspendedbypluginandmanually']->deleted);
        $usersuspendedbypluginandmanually->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers',
            array('id' => $data['usersuspendedbypluginandmanually']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive',
            array('id' => $data['usersuspendedbypluginandmanually']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['usersuspendedbypluginandmanually']->id));
        $this->assertEquals('Somerealusername', $recordusertable->username);
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);

        // User has a duplicate user in the usertable with different id but same username.
        $usersuspendedbypluginandmanually = new \tool_cleanupusers\archiveduser($data['originaluser']->id,
            0, $data['originaluser']->lastaccess, $data['userduplicatedname']->realusername,
            $data['originaluser']->deleted);
        $this->expectException(\tool_cleanupusers\cleanupusers_exception::class);
        $usersuspendedbypluginandmanually->activate_me();
        $recordtooltableoriginaluser = $DB->get_record('tool_cleanupusers',
            array('id' => $data['originaluser']->id));
        $recordtooltable2originaluser = $DB->get_record('tool_cleanupusers_archive',
            array('id' => $data['originaluser']->id));
        $recordusertableoriginal = $DB->get_record('user', array('id' => $data['originaluser']->id));
        $recordusertableduplicate = $DB->get_record('user', array('id' => $data['userduplicatedname']->id));
        $this->assertEquals('duplicatedname', $recordusertableoriginal->username);
        $this->assertNotEquals('duplicatedname', $recordusertableduplicate->username);
        $this->assertEquals(0, $recordusertableoriginal->suspended);
        $this->assertEmpty($recordtooltableoriginaluser);
        $this->assertEmpty($recordtooltable2originaluser);

        $this->resetAfterTest(true);
    }

    /**
     * Tries to archive users which cannot be archived and therefore throws exception. Only uses a admin user.
     * @throws \tool_cleanupusers\cleanupusers_exception
     * @throws dml_exception
     */
    public function test_exception_archiveme () {
        global $DB, $USER;
        $data = $this->set_up();
        $this->assertNotEmpty($data);
        $this->setAdminUser();
        // Admin Users will not be archived.
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
     * Tries to delete users which cannot be deleted and therefore throws exception. Users:
     *  Username                         |   signed in   | suspended manually | suspended by plugin | deleted
     * --------------------------------------------------------------------------------------------------------
     *  userdeleted                      | oneyearago    | no                 | yes                 | yes
     *  userinconsistentsuspended        | oneyearago    | no                 | partly              | no
     *  admin                            | -             | no                 | no                  | no
     * @throws \tool_cleanupusers\cleanupusers_exception
     * @throws dml_exception
     */
    public function test_exception_deleteme () {
        global $DB, $USER;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Trying to delete a user that is already deleted will throw a exception.
        $alreadydeleted = new \tool_cleanupusers\archiveduser($data['userdeleted']->id, $data['userdeleted']->suspended,
            $data['userdeleted']->lastaccess, $data['userdeleted']->realusername, $data['userdeleted']->deleted);
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

        // Remark: There is no need to set expected exception multiple times, it is set for the whole method.
        // Deleting a user who was inconsistently stored by the plugin (only in one table) will throw an exception.
        $alreadydeleted = new \tool_cleanupusers\archiveduser($data['userinconsistentsuspended']->id,
            $data['userinconsistentsuspended']->suspended, $data['userinconsistentsuspended']->lastaccess,
            $data['userinconsistentsuspended']->realusername, $data['userinconsistentsuspended']->deleted);
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
     * Tries to reactivate users which cannot be reactivated and therefore throws exception. Users:
     *  Username                         |   signed in   | suspended manually | suspended by plugin | deleted
     * --------------------------------------------------------------------------------------------------------
     *  userinconsistentsuspended        | oneyearago    | no                 | partly              | no
     *  admin                            | -             | no                 | no                  | no
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

        // Remark: There is no need to set expected exception multiple times, it is set for the whole method.
        // When entry in tool_cleanupusers_archive table is deleted user can not be updated.
        $useraccount = new \tool_cleanupusers\archiveduser($data['userinconsistentsuspended']->id,
            $data['userinconsistentsuspended']->suspended, $data['userinconsistentsuspended']->lastaccess,
            $data['userinconsistentsuspended']->realusername, $data['userinconsistentsuspended']->deleted);
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
     * Test the sub-plugin_select_form.
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
        $errorarray = array('subplugin' => get_string('errormessagesubplugin', 'tool_cleanupusers'));
        $this->assertEquals($errorarray, $return);
        $this->resetAfterTest(true);

    }

    /**
     * Executes and tests the cron-job. The following table illustrates what will happen to the users:
     * Username                          |   signed in    | suspended manually | suspended by plugin | deleted | action
     * -----------------------------------------------------------------------------------------------------------------
     *  user                             | tendaysago    | no                 | no                  | no       |  -
     *  userdeleted                      | oneyearago    | no                 | yes                 | yes      | -
     *  userneverloggedin                | -             | no                 | no                  | no       | -
     *  usersuspendedmanually            | -             | yes                | no                  | no       | -
     *  useroneyearnotloggedin           | oneyearago    | no                 | no                  | no       | suspend
     *  usersuspendedbyplugin            | oneyearago    | yes                | yes                 | no       | delete
     *  userinconsistentsuspended        | oneyearago    | no                 | partly              | no       | suspended
     *  usersuspendedbypluginandmanually | tendaysago    | yes                | yes                 | no       | activate
     * @throws dml_exception
     * @throws coding_exception
     */
    public function test_cronjob() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);
        // Set up mail configuration.
        unset_config('noemailever');
        $sink = $this->redirectEmails();
        $cronjob = new tool_cleanupusers\task\archive_user_task();
        $name = $cronjob->get_name();
        $this->assertEquals(get_string('archive_user_task', 'tool_cleanupusers'), $name);

        $timestamponeyearago = time() - 31622600;
        // Creates entries in the tables which will result in an error in the cronjob.
        $DB->insert_record_raw('tool_cleanupusers', array('id' => 236465, 'archived' => true,
            'timestamp' => $timestamponeyearago), true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', array('id' => 236465,
            'username' => 'inconsistent', 'suspended' => 0, 'lastaccess' => $timestamponeyearago),
            true, false, true);

        // Run cron-job with timechecker plugin.
        set_config('cleanupusers_subplugin', 'timechecker', 'tool_cleanupusers');
        $cronjob = new tool_cleanupusers\task\archive_user_task();
        $cronjob->execute();
        // Administrator should have received an email.
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
        $expectedmessage = 'In the last cron-job 2 users were archived.In the last cron-job 1 users were deleted
.In the last cron-job 1 users caused exception and could not be deleted.In the last cron-job 0 users caused exception
 and could not be suspended.In the last cron-job 0 users caused exception and could not be reactivated.';
        $expectedmessage = str_replace(array("\r\n", "\r", "\n"), '', $expectedmessage);
        $msg = str_replace(array("\r\n", "\r", "\n"), '', $messages[0]->body);
        $this->assertEquals($expectedmessage, $msg);

        // Users not changed by the Cronjob.
        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assert_user_equals($data['user'], $recordusertable);

        $recordusertable = $DB->get_record('user', array('id' => $data['userdeleted']->id));
        $this->assert_user_equals($data['userdeleted'], $recordusertable);

        $recordusertable = $DB->get_record('user', array('id' => $data['userneverloggedin']->id));
        $this->assert_user_equals($data['userneverloggedin'], $recordusertable);

        $recordusertable = $DB->get_record('user', array('id' => $data['usersuspendedmanually']->id));
        $this->assert_user_equals($data['usersuspendedmanually'], $recordusertable);

        // User is suspended.
        $recordusertable = $DB->get_record('user', array('id' => $data['useroneyearnotloggedin']->id));
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['useroneyearnotloggedin']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['useroneyearnotloggedin']->id));
        $this->assertNotEmpty($recordtooltable);
        $this->assert_user_equals($data['useroneyearnotloggedin'], $recordtooltable2);
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(0, $recordtooltable2->suspended);
        $this->assertEquals('anonym' . $data['useroneyearnotloggedin']->id, $recordusertable->username);
        $this->assertEquals(0, $recordusertable->deleted);

        // User is deleted.
        $recordusertable = $DB->get_record('user', array('id' => $data['usersuspendedbyplugin']->id));
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['usersuspendedbyplugin']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['usersuspendedbyplugin']->id));
        $this->assertEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(1, $recordusertable->deleted);

        // User is suspended.
        $recordusertable = $DB->get_record('user', array('id' => $data['userinconsistentsuspended']->id));
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['userinconsistentsuspended']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive', array('id' => $data['userinconsistentsuspended']->id));
        $this->assertNotEmpty($recordtooltable2);
        $this->assertNotEmpty($recordtooltable);
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(0, $recordusertable->deleted);

        // User was reactivated.
        $recordusertable = $DB->get_record('user', array('id' => $data['usersuspendedbypluginandmanually']->id));
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['usersuspendedbypluginandmanually']->id));
        $recordtooltable2 = $DB->get_record('tool_cleanupusers_archive',
            array('id' => $data['usersuspendedbypluginandmanually']->id));
        $this->assertEmpty($recordtooltable);
        $this->assertEmpty($recordtooltable2);
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(0, $recordusertable->deleted);

        $this->resetAfterTest();
    }

    /**
     * Testing equality of userdata arrays disregarding the realusername field.
     */
    public function assert_user_equals($expected, $actual) {
        $expected = (array) $expected;
        $actual = (array) $actual;
        foreach ($expected as $k => $v) {
            if ($k != 'realusername') {
                $this->assertEquals($v, $actual[$k]);
            }
        }
        // Should contain the same amount of keys (except for realusername).
        $this->assertEquals(count($expected) - array_key_exists('realusername', $expected),
            count($actual) - array_key_exists('realusername', $actual));
    }

    /**
     * Test the the deprovisionuser cron-job complete event.
     *
     * @see \tool_cleanupusers\event\deprovisionusercronjob_completed
     */
    public function test_logging() {
        $data = $this->set_up();
        $this->assertNotEmpty($data);
        $timestamp = time();

        $eventsink = $this->redirectEvents();

        set_config('cleanupusers_subplugin', 'timechecker', 'tool_cleanupusers');
        $cronjob = new tool_cleanupusers\task\archive_user_task();
        $cronjob->execute();
        $triggered = $eventsink->get_events();
        $eventsink->close();
        $found = false;
        foreach ($triggered as $event) {
            if ($event instanceof \tool_cleanupusers\event\deprovisionusercronjob_completed) {
                $this->assertTrue(true, 'Completion event triggered.');
                $this->assertTrue($event->timecreated >= $timestamp, 'Completion event triggered correctly.');
                $found = true;
                break;
            }
        }
        if (!$found) {
            $this->fail('Completion event was not triggered.');
        }
    }

    /**
     * Methods recommended by moodle to assure database and dataroot is reset.
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
     * Methods recommended by moodle to assure database is reset.
     */
    public function test_user_table_was_reset() {
        global $DB;
        $this->assertEquals(2, $DB->count_records('user', array()));
        $this->assertEquals(0, $DB->count_records('tool_cleanupusers', array()));
    }
}