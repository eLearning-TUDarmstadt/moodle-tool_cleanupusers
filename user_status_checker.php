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
 * Checks the status of the Users
 * TODO later calls for subplugins to let them check the status
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Class that checks the status of different users
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_status_checker {
    public function get_last_login(){
        global $USER, $DB;
        $arrayofuser = $this->get_all_users();
        $arrayofoldusers = array();
        $mytimestamp = time();
        foreach($arrayofuser as $key => $user){
            if (!empty($user) && !empty($user->lastaccess)){
                $timenotloggedin = $mytimestamp - $user->lastaccess;
//              Minutes a user was not logged in
                $timeinnotunixformat = $timenotloggedin ;
                $arrayofoldusers[$key]['username'] = $user->username;
                $arrayofoldusers[$key]['lastaccess'] = date('Y-m-d h:i:s',$user->lastaccess);
                $isarchivid = $DB->get_records('tool_deprovisionuser', array('id' => $user->id, 'archived' => 1));
                if(empty($isarchivid)) {
                    $arrayofoldusers[$key]['archived'] = get_string('No', 'tool_deprovisionuser');
                } else {
                    $arrayofoldusers[$key]['archived'] = get_string('Yes', 'tool_deprovisionuser');
                }
                if($timeinnotunixformat> 130000){
                    $arrayofoldusers[$key]['Willbe'] = 'to be archived';
                } else {
                    $arrayofoldusers[$key]['Willbe'] = 'not to be archived';
                }
            } else {}
        }
        return $arrayofoldusers;
    }
    public function get_all_users(){
        global $DB;
        //TODO for Performance reasons only get neccessary record
        return $DB->get_records('user');
    }
    public function get_never_logged_in(){
        global $USER, $DB;
        $arrayofuser = $this->get_all_users();
        $arrayofoldusers = array();
        foreach($arrayofuser as $key => $user){
            if (empty($user->lastaccess)){
                $arrayofoldusers[$key]['username'] = $user->username;
            }
        }
        return $arrayofoldusers;
    }
}
