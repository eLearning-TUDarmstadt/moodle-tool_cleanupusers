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
 * The class contains a test script for the moodle userstatus_userstatuswwu
 *
 * @package userstatus_userstatuswwu
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use tool_deprovisionuser\task;

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
     */
    public function test_archiveduser() {
        global $DB, $CFG, $OUTPUT;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // Users that are archived will be marked as suspended in the user table and in the tool_deprovisionuser table.
        $neutraltosuspended = new \tool_deprovisionuser\archiveduser($data['user']->id, 0);
        $neutraltosuspended->archive_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array('id' => $data['user']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(1, $recordusertable->suspended);
        $this->assertEquals(1, $recordtooltable->archived);

        // Users that are deleted will be marked as deleted in the user table.
        // The entry the tool_deprovisionuser table will be deleted.
        $suspendedtodelete = new \tool_deprovisionuser\archiveduser($data['suspendeduser2']->id, 0);
        $suspendedtodelete->delete_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array('id' => $data['suspendeduser2']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['suspendeduser2']->id));
        $this->assertNotEmpty($recordusertable);
        $this->assertEmpty($recordtooltable);

        // Users that are activated will be marked as active in the user table.
        // The entry the tool_deprovisionuser table will be deleted.
        $suspendedtoactive = new \tool_deprovisionuser\archiveduser($data['suspendeduser']->id, 0);
        $DB->insert_record_raw('tool_deprovisionuser', array('id' => $data['suspendeduser']->id, 'archived' => 1),
            true, false, true);
        $DB->insert_record_raw('deprovisionuser_archive', $data['suspendeduser'], true, false, true);
        $cloneuser = clone $data['suspendeduser'];
        $cloneuser->username = 'anonym' . $data['suspendeduser']->id;
        $cloneuser->firstname = 'Anonym';
        $cloneuser->lastname = '';
        $DB->update_record('user', $cloneuser);

        $suspendedtoactive->activate_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array('id' => $data['suspendeduser']->id));
        $recordusertable = $DB->get_record('user', array('id' => $data['suspendeduser']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        $this->assertEmpty($recordtooltable);

        // Admin Users will not be deleted neither archived.
        $this->setAdminUser($data['adminuser']);
        $adminaccount = new \tool_deprovisionuser\archiveduser($data['adminuser']->id, 0);
        // Todo: deprecated replace later
        $this->setExpectedException('tool_deprovisionuser\deprovisionuser_exception', 'Not able to suspend user');
        $adminaccount->archive_me();
        $recordtooltable = $DB->get_record('moodle_deprovisionuser', array('id' => $data['adminuser']->id));
        $this->assertEmpty($recordtooltable);

        $this->setAdminUser($data['adminuser']);
        $adminaccount = new \tool_deprovisionuser\archiveduser($data['adminuser']->id, 0);
        $this->setexpectedException('tool_deprovisionuser\deprovisionuser_exception', 'Not able to delete user');
        $adminaccount->delete_me();
        $recordtooltable = $DB->get_record('tool_deprovisionuser', array('id' => $data['adminuser']->id));
        $this->assertEmpty($recordtooltable);
        $this->resetAfterTest(true);

    }

    /**
     * Function which executes anf tests the cronjob.
     */
    public function test_cronjob() {
        global $DB;
        $data = $this->set_up();
        $this->assertNotEmpty($data);

        // TODO: data for ziv file.
        $cronjob = new tool_deprovisionuser\task\archive_user_task();
        $name = $cronjob->get_name();
        $this->assertEquals(get_string('archive_user_task', 'tool_deprovisionuser'), $name);

        // Before cronjob is executed users are not suspended.
        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(0, $recordusertable->suspended);

        $recordusertable = $DB->get_record('user', array('id' => $data['listuser']->id));
        $this->assertEquals(0, $recordusertable->suspended);
        // The default plugin is the userstatuswwu plugin therefore all users that are not listet in the
        // Groups_excerpt_short.txt are suspended
        $cronjob->execute();

        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        $this->assertEquals(1, $recordusertable->suspended);

        $record = $DB->get_record('user', array('id' => $data['archivedbyplugin']->id));
        $this->assertnotEquals($data['archivedbyplugin']->username, $record->username);

        $recordusertable = $DB->get_record('user', array('id' => $data['listuser']->id));
        $this->assertEquals(0, $recordusertable->suspended);

        // Cronjob is executed with different subplugin.
        set_config('deprovisionuser_subplugin', 'timechecker', 'tool_deprovisionuser');
        $cronjob = new tool_deprovisionuser\task\archive_user_task();
        $cronjob->execute();

        $recordusertable = $DB->get_record('user', array('id' => $data['user']->id));
        // User is reactivated.
        $this->assertEquals(0, $recordusertable->suspended);

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