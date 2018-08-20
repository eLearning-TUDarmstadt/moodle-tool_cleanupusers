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
     * Function to test the the archiveduser class.
     *
     * @see \tool_cleanupusers\archiveduser
     */
    public function test_archiveduser() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Users that are archived will be marked as suspended in the user table and in the tool_cleanupusers table.
        // Additionally they will be anomynised in the user table. Firstname will be Anonym, Username anonym + id.
        // User is not suspended and did sign in.
        $neutraltosuspended = new \tool_cleanupusers\archiveduser($data['user']->id, 0,
            $data['user']->lastaccess, $data['user']->username, $data['user']->deleted);
        $neutraltosuspended->archive_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['user']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(0, $recordtooltable->archived);
        $this->assertEquals('Anonym', $recordusertable->firstname);
        $this->assertEquals('anonym' . $data['user']->id, $recordusertable->username);

        // Users that are activated will be marked as suspended=0 in the user table.
        // suspendeduser is only flagged as suspended in the user table.
        $neutraltosuspended = new \tool_cleanupusers\archiveduser($data['suspendeduser']->id, $data['suspendeduser']->suspended,
            $data['suspendeduser']->lastaccess, $data['suspendeduser']->username, $data['suspendeduser']->deleted);
        $neutraltosuspended->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['suspendeduser']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['suspendeduser']->id));
        // Since the Shadowtable states that the user was previously suspended he/she is marked as suspended in the ...
        // ... real table.
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);

        // Users that are deleted will be marked as deleted in the user table.
        // The entry the tool_cleanupusers table will be deleted.
        // Suspenduser2 is marked as suspended in the user table no additional information.
        $suspendedtodelete = new \tool_cleanupusers\archiveduser($data['suspendeduser2']->id, 0,
            $data['suspendeduser2']->lastaccess, $data['suspendeduser2']->username, $data['suspendeduser2']->deleted);
        $suspendedtodelete->delete_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['suspendeduser2']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['suspendeduser2']->id));
        $this->assertEquals(1, $recordusertable->deleted);
        $this->assertNotEmpty($recordusertable);
        $this->assertEmpty($recordtooltable);

        // Users that are activated will transfer their previous suspended status from the shadow table.
        // The entry the tool_cleanupusers table will be deleted.
        // archivedbyplugin has entry in tool_cleanupusers and tool_cleanupusers_archive was suspended one year ago.
        // 1. User that was previously suspended.
        $suspendedtoactive = new \tool_cleanupusers\archiveduser($data['archivedbyplugin']->id,
            $data['archivedbyplugin']->suspended, $data['archivedbyplugin']->lastaccess, $data['archivedbyplugin']->username,
            $data['archivedbyplugin']->deleted);
        $suspendedtoactive->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['archivedbyplugin']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['archivedbyplugin']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);

        // 2. User that was previously not suspended.
        $suspendedtoactive = new \tool_cleanupusers\archiveduser($data['archivedbyplugin2']->id,
            $data['archivedbyplugin2']->suspended, $data['archivedbyplugin2']->lastaccess, $data['archivedbyplugin2']->username,
            $data['archivedbyplugin2']->deleted);
        $suspendedtoactive->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['archivedbyplugin2']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['archivedbyplugin2']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);

        $useraccount = new \tool_cleanupusers\archiveduser($data['reactivatebyplugin']->id, 0,
            $data['reactivatebyplugin']->lastaccess, $data['reactivatebyplugin']->username, $data['reactivatebyplugin']->deleted);
        $useraccount->activate_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $data['reactivatebyplugin']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['reactivatebyplugin']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);

        $this->resetAfterTest(true);
    }
    public function test_exception () {
        global $DB, $USER;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        $useraccount = new \tool_cleanupusers\archiveduser($data['reactivatebypluginexception']->id,
            $data['reactivatebypluginexception']->suspended, $data['reactivatebypluginexception']->lastaccess,
            $data['reactivatebypluginexception']->username, $data['reactivatebypluginexception']->deleted);
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to activate user.');
        $useraccount->activate_me();

        // When entry in tool_cleanupusers_archive table is deleted user can not be updated.
        $useraccount = new \tool_cleanupusers\archiveduser($data['reactivatebyplugin']->id, $data['reactivatebyplugin']->suspended,
            $data['reactivatebyplugin']->lastaccess, $data['reactivatebyplugin']->username,
            $data['reactivatebyplugin']->deleted);
        $DB->delete_records('tool_cleanupusers_archive', array('id' => $data['reactivatebyplugin']->id));
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to activate user.');
        $useraccount->activate_me();

        // Admin Users will not be deleted neither archived.
        $this->setAdminUser();
        $adminaccount = new \tool_cleanupusers\archiveduser($USER->id, $USER->suspended,
            $USER->lastaccess, $USER->username, $USER->deleted);
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to suspend user');
        $adminaccount->archive_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array('id' => $USER->id));
        $this->assertEmpty($recordtooltable);

        $this->setAdminUser();
        $adminaccount = new \tool_cleanupusers\archiveduser($USER->id, 0,
            $USER->lastaccess, $USER->username, $USER->deleted);
        $this->expectException('tool_cleanupusers\cleanupusers_exception');
        $this->expectExceptionMessage('Not able to delete user');
        $adminaccount->delete_me();
        $recordtooltable = $DB->get_record('tool_cleanupusers', array($USER->id));
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

        $cronjob = new tool_cleanupusers\task\archive_user_task();
        $name = $cronjob->get_name();
        $this->assertEquals(get_string('archive_user_task', 'tool_cleanupusers'), $name);

        // Before cron-job is executed users are not suspended.
        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(0, $recordusertable->suspended);

        $recordusertable = $DB->get_record('user', array('id' => $data['listuser']->id));
        $this->assertEquals(0, $recordusertable->suspended);

        // Run cron-job with timechecker plugin.
        set_config('cleanupusers_subplugin', 'timechecker', 'tool_cleanupusers');
        $cronjob = new tool_cleanupusers\task\archive_user_task();
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
     * @see \tool_cleanupusers\event\deprovisionusercronjob_completed
     */
    public function test_logging() {
        $this->resetAfterTest();
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