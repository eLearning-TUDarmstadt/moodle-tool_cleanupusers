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
 * The class contains a test script for the moodle tool_deprovisionuser
 *
 * @package tool_deprovisionuser
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
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
     * Function to test the locallib functions.
     */
    public function test_locallib() {
        global $DB, $CFG, $OUTPUT;
        // Require_once(dirname(__FILE__).'/user_status_checker.php');
        require_once($CFG->dirroot.'/admin/tool/deprovisionuser/user_status_checker.php');
        $data = $this->set_up();
        $myuserstatuschecker = new user_status_checker();
        $returnarray = $myuserstatuschecker->get_users_for_suspending();
        $refimgtoarchive = html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
            '/tool/deprovisionuser/archiveuser.php?userid=' . $data['user']->id . '&archived=' . $data['user']->suspended,
            html_writer::img($OUTPUT->pix_url('t/hide'), get_string('hideuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $data['user']->id)));
        $refimgtoactivate = html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
            '/tool/deprovisionuser/archiveuser.php?userid=' . $data['userlongnotloggedin']->id . '&archived=' . $data['userlongnotloggedin']->suspended,
            html_writer::img($OUTPUT->pix_url('t/hide'), get_string('hideuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $data['userlongnotloggedin']->id)));

        $arraynotsuspended = array('username' => 'user', 'lastaccess' => '2016-11-24 04:28:23', 'archived' => 'No', 'Willbe' => 'not to be archived', 'link' => $refimgtoarchive);
        $arraysuspended = array('username' => 'userlongnotloggedin', 'lastaccess' =>  '2012-11-18 10:35:42','archived' => 'No', 'Willbe' => 'to be archived', 'link' => $refimgtoactivate);
        $myexpectedarray = array($data['user']->id => $arraynotsuspended, $data['userlongnotloggedin']->id => $arraysuspended);
        $this->assertEquals($myexpectedarray[$data['user']->id], $returnarray[$data['user']->id]);
        $this->assertEquals($myexpectedarray[$data['userlongnotloggedin']->id], $returnarray[$data['userlongnotloggedin']->id]);
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