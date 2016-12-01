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
 *
 * @package    tool_deprovisionuser
 * @category   test
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 *
 *
 * @package    tool_deprovisionuser
 * @category   test
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_deprovisionuser_generator extends testing_block_generator {
    /**
     * Creates Course, course members, groups and groupings to test the block.
     */
    public function test_create_preparation () {
        global $DB;
        $generator = advanced_testcase::getDataGenerator();
        $data = array();
        $course = $generator->create_course(array('name' => 'Some course'));
        $data['course'] = $course;
        $mytimestamp = time();
        $user = $generator->create_user(array('username' => 'user', 'lastaccess' => $mytimestamp));
        $generator->enrol_user($user->id, $course->id);
        $data['user'] = $user;
        $userlongnotloggedin = $generator->create_user(array('username' => 'userlongnotloggedin', 'lastaccess' => 1353249342));
        $generator->enrol_user($userlongnotloggedin->id, $course->id);
        $data['userlongnotloggedin'] = $userlongnotloggedin;
        $userarchived = $generator->create_user(array('username' => 'userarchived', 'lastaccess' => 1353249342, 'suspended' => 1));
        $DB->insert_record_raw('tool_deprovisionuser', array('id' => $userarchived->id, 'archived' => true), true, false, true);
        $generator->enrol_user($userarchived->id, $course->id);
        $data['userarchived'] = $userarchived;
        return $data; // Return the user, course and group objects.
    }
}