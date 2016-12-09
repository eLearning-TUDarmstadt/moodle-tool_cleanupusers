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
use userstatus_userstatuswwu\userstatuswwu;

class userstatus_userstatuswwu_testcase extends advanced_testcase {

    protected function set_up() {
        // Recommended in Moodle docs to always include CFG.
        global $CFG;
        $generator = $this->getDataGenerator()->get_plugin_generator('userstatus_userstatuswwu');
        $data = $generator->test_create_preparation();
        $this->resetAfterTest(true);
        return $data;
    }
    /**
     * Function to test the locallib functions.
     */
    public function test_locallib() {
        global $DB, $CFG, $OUTPUT;
        $data = $this->set_up();
        $myuserstatuschecker = new userstatuswwu();
        // Ruft die Methode auf, die mir das array zurückgibt
        $returnarray = $myuserstatuschecker->get_users_for_suspending();
        // Erstellt mir den Link mit entweder durchgestrichenem oder normalen Auge
        $refimgtoarchive = html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
            '/tool/deprovisionuser/archiveuser.php?userid=' . $data['user']->id . '&archived=' . $data['user']->suspended,
            html_writer::img($OUTPUT->pix_url('t/hide'), get_string('hideuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $data['user']->id)));
        $refimgtoarchivelongnotloggedin = html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
            '/tool/deprovisionuser/archiveuser.php?userid=' . $data['userlongnotloggedin']->id . '&archived=' . $data['userlongnotloggedin']->suspended,
            html_writer::img($OUTPUT->pix_url('t/hide'), get_string('hideuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $data['userlongnotloggedin']->id)));
        $refimgtoactivate = html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
            '/tool/deprovisionuser/archiveuser.php?userid=' . $data['userarchived']->id . '&archived=' . $data['userarchived']->suspended,
            html_writer::img($OUTPUT->pix_url('t/show'), get_string('showuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $data['userarchived']->id)));
        $datetime = date('d.m.Y h:i:s', $data['user']->lastaccess);
        // Fügt dem Nutzer die Attribute zu.
        $useractive = array('username' => 'user', 'lastaccess' => $datetime, 'archived' => 'No', 'Willbe' => 'not to be archived', 'link' => $refimgtoarchive);
        $usertoarchive = array('username' => 'userlongnotloggedin', 'lastaccess' => '18.11.2012 10:35:42', 'archived' => 'No',
            'Willbe' => 'to be archived', 'link' => $refimgtoarchivelongnotloggedin);
        $userarchived = array('username' => 'userarchived', 'lastaccess' => '18.11.2012 10:35:42', 'archived' => 'Yes', 'Willbe' => 'will be deleted in the next cron_job',
            'link' => $refimgtoactivate);

        $this->assertEquals($useractive['Willbe'], $returnarray[$data['user']->id]['Willbe']);
        $this->assertEquals($useractive['lastaccess'], $returnarray[$data['user']->id]['lastaccess']);
        $this->assertEquals($useractive['link'], $returnarray[$data['user']->id]['link']);
        $this->assertEquals($useractive['archived'], $returnarray[$data['user']->id]['archived']);

        $this->assertEquals($usertoarchive['Willbe'], $returnarray[$data['userlongnotloggedin']->id]['Willbe']);
        $this->assertEquals($usertoarchive['lastaccess'], $returnarray[$data['userlongnotloggedin']->id]['lastaccess']);
        $this->assertEquals($usertoarchive['link'], $returnarray[$data['userlongnotloggedin']->id]['link']);
        $this->assertEquals($usertoarchive['archived'], $returnarray[$data['userlongnotloggedin']->id]['archived']);

        $this->assertEquals($userarchived['Willbe'], $returnarray[$data['userarchived']->id]['Willbe']);
        $this->assertEquals($userarchived['lastaccess'], $returnarray[$data['userarchived']->id]['lastaccess']);
        $this->assertEquals($userarchived['link'], $returnarray[$data['userarchived']->id]['link']);
        $this->assertEquals($userarchived['archived'], $returnarray[$data['userarchived']->id]['archived']);

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