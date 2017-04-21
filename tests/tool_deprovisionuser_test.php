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
 * Test script for the moodle tool_deprovisionuser plugin.
 *
 * @package    tool_deprovisionuser
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use tool_deprovisionuser\task;


/**
 * Testcase class for executing phpunit test for the moodle tool_deprovisionuser plugin.
 *
 * @package    tool_deprovisionuser
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_deprovisionuser_testcase extends advanced_testcase {

    protected function set_up() {
        // Recommended in Moodle docs to always include CFG.
        global $CFG;
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_deprovisionuser');
        $data = $generator->test_create_preparation();
        $this->resetAfterTest(true);
        return $data;
    }

    /**
     * Function to test the the archiveduser class.
     *
     * @see \tool_deprovisionuser\archiveduser
     */
    public function test_archiveduser() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Users that are archived will be marked as suspended in the user table and in the tool_deprovisionuser table.
        // Additionally they will be anomynised in the user table. Firstname will be Anonym, Username anonym + id.
        // User is not suspended and did sign in.
        $neutraltosuspended = new \tool_deprovisionuser\archiveduser($data['user']->id, 0,
            $data['user']->lastaccess, $data['user']->username, $data['user']->deleted);
        $neutraltosuspended->archive_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array('id' => $data['user']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(1, $recordtooltable->archived);
        $this->assertEquals('Anonym', $recordusertable->firstname);
        $this->assertEquals('anonym' . $data['user']->id, $recordusertable->username);

        // Users that are activated will be marked as suspended=0 in the user table.
        // suspendeduser is only flagged as suspended in the user table
        $neutraltosuspended = new \tool_deprovisionuser\archiveduser($data['suspendeduser']->id, $data['suspendeduser']->suspended,
            $data['suspendeduser']->lastaccess, $data['suspendeduser']->username, $data['suspendeduser']->deleted);
        $neutraltosuspended->activate_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array('id' => $data['suspendeduser']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['suspendeduser']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);

        // Users that are deleted will be marked as deleted in the user table.
        // The entry the tool_deprovisionuser table will be deleted.
        // Suspenduser2 is marked as suspended in the user table no additional information.
        $suspendedtodelete = new \tool_deprovisionuser\archiveduser($data['suspendeduser2']->id, 0,
            $data['suspendeduser2']->lastaccess, $data['suspendeduser2']->username, $data['suspendeduser2']->deleted);
        $suspendedtodelete->delete_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array('id' => $data['suspendeduser2']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['suspendeduser2']->id));
        $this->assertEquals(1, $recordusertable->deleted);
        $this->assertNotEmpty($recordusertable);
        $this->assertEmpty($recordtooltable);

        // Users that are activated will be marked as active in the user table.
        // The entry the tool_deprovisionuser table will be deleted.
        // archivedbyplugin has entry in tool_deprovisionuser and deprovisionuser_archive was suspended one year ago.
        $suspendedtoactive = new \tool_deprovisionuser\archiveduser($data['archivedbyplugin']->id, $data['archivedbyplugin']->suspended,
            $data['archivedbyplugin']->lastaccess, $data['archivedbyplugin']->username, $data['archivedbyplugin']->deleted);
        $suspendedtoactive->activate_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array('id' => $data['archivedbyplugin']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['archivedbyplugin']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);

        $useraccount = new \tool_deprovisionuser\archiveduser($data['reactivatebyplugin']->id, 0,
            $data['reactivatebyplugin']->lastaccess, $data['reactivatebyplugin']->username, $data['reactivatebyplugin']->deleted);
        $useraccount->activate_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array('id' => $data['reactivatebyplugin']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['reactivatebyplugin']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);

        $this->resetAfterTest(true);
    }
    public function test_exception () {
        global $DB, $USER;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        $useraccount = new \tool_deprovisionuser\archiveduser($data['reactivatebypluginexception']->id, $data['reactivatebypluginexception']->suspended,
            $data['reactivatebypluginexception']->lastaccess, $data['reactivatebypluginexception']->username,
            $data['reactivatebypluginexception']->deleted);
        $this->expectException('tool_deprovisionuser\deprovisionuser_exception');
        $this->expectExceptionMessage('Not able to activate user.');
        $useraccount->activate_me();

        // When entry in deprovisionuser_archive table is deleted user can not be updated.
        $useraccount = new \tool_deprovisionuser\archiveduser($data['reactivatebyplugin']->id, $data['reactivatebyplugin']->suspended,
            $data['reactivatebyplugin']->lastaccess, $data['reactivatebyplugin']->username,
            $data['reactivatebyplugin']->deleted);
        $DB->delete_records('deprovisionuser_archive', array('id' => $data['reactivatebyplugin']->id));
        $this->expectException('tool_deprovisionuser\deprovisionuser_exception');
        $this->expectExceptionMessage('Not able to activate user.');
        $useraccount->activate_me();

        // Admin Users will not be deleted neither archived.
        $this->setAdminUser();
        $adminaccount = new \tool_deprovisionuser\archiveduser($USER->id, $USER->suspended,
            $USER->lastaccess, $USER->username, $USER->deleted);
        $this->expectException('tool_deprovisionuser\deprovisionuser_exception');
        $this->expectExceptionMessage('Not able to suspend user');
        $adminaccount->archive_me();
        $recordtooltable = $DB->get_record('moodle_deprovisionuser', array('id' => $USER->id));
        $this->assertEmpty($recordtooltable);

        $this->setAdminUser();
        $adminaccount = new \tool_deprovisionuser\archiveduser($USER->id, 0,
            $USER->lastaccess, $USER->username, $USER->deleted);
        $this->expectException('tool_deprovisionuser\deprovisionuser_exception');
        $this->expectExceptionMessage('Not able to delete user');
        $adminaccount->delete_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array($USER->id));
        $this->assertEmpty($recordtooltable);
        $this->resetAfterTest(true);
    }

    /**
     * Executes and tests the cron-job.
     *
     * @see task\archive_user_task
     */
    public function test_cronjob() {
        global $DB, $USER;
        $data = $this->set_up();
        $this->assertNotEmpty($data);
        // Set up mail configuration.
        unset_config('noemailever');
        $sink = $this->redirectEmails();

        $cronjob = new tool_deprovisionuser\task\archive_user_task();
        $name = $cronjob->get_name();
        $this->assertEquals(get_string('archive_user_task', 'tool_deprovisionuser'), $name);

        // Before cron-job is executed users are not suspended.
        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(0, $recordusertable->suspended);

        $recordusertable = $DB->get_record('user', array('id' => $data['listuser']->id));
        $this->assertEquals(0, $recordusertable->suspended);

        // Run cron-job with timechecker plugin.
        set_config('deprovisionuser_subplugin', 'timechecker', 'tool_deprovisionuser');
        $cronjob = new tool_deprovisionuser\task\archive_user_task();
        $cronjob->execute();

        // Administrator should have received an email.
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
        $expectedmessage = 'In the last cron-job 1 users were archived.In the last cron-job 2 users were deleted.In the
 last cron-job 0 users caused exception and could not be deleted.In the last cron-job 0 users caused exception and
 could not be suspended.In the last cron-job 1 users caused exception and could not be reactivated.';
        $expectedmessage = str_replace(array("\r\n", "\r", "\n"), '', $expectedmessage);
        $msg = str_replace(array("\r\n", "\r", "\n"), '', $messages[0]->body);
        $this->assertEquals($expectedmessage, $msg);

        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEquals(0, $recordusertable->deleted);

        $recordusertable = $DB->get_record('user', array('id' => $data['listuser']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEquals(0, $recordusertable->deleted);

        $recordusertable = $DB->get_record('user', array('id' => $data['suspendeduser']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(0, $recordusertable->deleted);

        // User is suspended.
        $recordusertable = $DB->get_record('user', array('id' => $data['notsuspendeduser']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(0, $recordusertable->deleted);

        // User is reactivated.
        $recordusertable = $DB->get_record('user', array('id' => $data['reactivatebyplugin']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEquals(0, $recordusertable->deleted);

        // Users that were archived will be deleted by the cron-job.
        $recordusertable = $DB->get_record('user', array('id' => $data['deleteduser']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(1, $recordusertable->deleted);

        $recordusertable = $DB->get_record('user', array('id' => $data['archivedbyplugin']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(1, $recordusertable->deleted);

        // Admin User will not be deleted, although he is suspended (only manually possible).
        $this->setAdminUser();
        $recordusertable = $DB->get_record('user', array('id' => $USER->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEquals(0, $recordusertable->deleted);
        $this->resetAfterTest();
    }

    /**
     * Test the the deprovisionuser cron-job complete event.
     *
     * @see \tool_deprovisionuser\event\deprovisionusercronjob_completed
     */
    public function test_logging() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        $dbman = $DB->get_manager();

        // Set logstore configuration.
        $this->preventResetByRollback();

        // Test all plugins are disabled by this command.
        set_config('enabled_stores', '', 'tool_log');
        $manager = get_log_manager(true);
        $stores = $manager->get_readers();
        $this->assertCount(0, $stores);

        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        set_config('buffersize', 0, 'logstore_standard');

        $this->assertTrue($dbman->table_exists('logstore_standard_log'));
        $timestamp = time();

        // Necessary to get current logs otherwise $DB get_record does not contain the event.
        $manager = get_log_manager(true);

        set_config('deprovisionuser_subplugin', 'timechecker', 'tool_deprovisionuser');
        $cronjob = new tool_deprovisionuser\task\archive_user_task();
        $cronjob->execute();

        $logstore = $DB->get_record_select('logstore_standard_log', 'timecreated >=' . $timestamp .
            'AND eventname = \'\tool_deprovisionuser\event\deprovisionusercronjob_completed\'');
        $this->assertEquals('a:2:{s:15:"numbersuspended";i:1;s:13:"numberdeleted";i:2;}', $logstore->other);

        $this->resetAfterTest();
    }

    /**
     * Test the the sub-plugin_select_form.
     *
     * @see \tool_deprovisionuser\subplugin_select_form
     */
    public function test_subpluginform() {
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Validation with existing sub-plugin returns true.
        $subpluginform = new tool_deprovisionuser\subplugin_select_form();
        $validationdata = array ("subplugin" => 'timechecker');
        $return = $subpluginform->validation($validationdata, null);
        $this->assertEquals(true, $return);

        // Validation with non-existing sub-plugin returns an array with an errormessage.
        $validationdata = array ("subplugin" => 'nosubplugin');
        $return = $subpluginform->validation($validationdata, null);
        $errorarray = array('subplugin' => new tool_deprovisionuser\deprovisionuser_subplugin_exception
            (get_string('errormessagesubplugin', 'tool_deprovisionuser')));
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
        $DB->delete_records('tool_deprovisionuser');
        $this->assertEmpty($DB->get_records('user'));
        $this->assertEmpty($DB->get_records('tool_deprovisionuser'));
    }

    /**
     * Methodes recommended by moodle to assure database is reset.
     */
    public function test_user_table_was_reset() {
        global $DB;
        $this->assertEquals(2, $DB->count_records('user', array()));
        $this->assertEquals(0, $DB->count_records('tool_deprovisionuser', array()));
    }
}