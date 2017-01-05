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
 * Subplugin timechecker.
 *
 * @package   deprovisionuser_userstatus_timechecker
 * @copyright 2016 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_timechecker;

use tool_deprovisionuser\userstatusinterface;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that checks the status of different users
 *
 * @package    deprovisionuser_userstatus_timechecker
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class timechecker implements userstatusinterface {

    private $timesuspend;
    private $timedelete;

    public function __construct($timesuspend=null, $timedelete=null) {
        $config = get_config('userstatus_timechecker');
        if($timesuspend === null){
            $this->timesuspend = $config->suspendtime * 84600;
        } else {
            $this->timesuspend = $timesuspend * 84600;
        }
        if($timedelete === null){
            $this->timedelete = $config->deletetime * 84600;
        } else {
            $this->timedelete = $timedelete * 84600;
        }
    }


    public function get_to_suspend() {
        $users = $this->get_all_users();
        $tosuspend = array();
        foreach ($users as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $mytimestamp = time();
                $timenotloggedin = $mytimestamp - $user->lastaccess;
                if ($timenotloggedin > $this->timesuspend && $user->suspended == 0) {
                    $tosuspend[$key] = $user;
                }
                if ($timenotloggedin < $this->timesuspend && $user->suspended == 1) {
                    $toaactivate[$key] = $user;
                }
            }
        }
        return $tosuspend;
    }

    public function get_never_logged_in() {
        global $DB;
        $arrayofuser = $this->get_all_users();
        $arrayofoldusers = array();
        foreach ($arrayofuser as $key => $user) {
            if (empty($user->lastaccess) && $user->deleted == 0) {
                $fulluser = $DB->get_record('user', array('id' => $user->id));
                $arrayofoldusers[$key] = $fulluser;
            }
        }
        return $arrayofoldusers;
    }
    public function get_to_delete() {
        $users = $this->get_all_users();
        $todeleteusers = array();
        foreach ($users as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $mytimestamp = time();
                $timenotloggedin = $mytimestamp - $user->lastaccess;
                // TODO: prepare user to be deleted - not delete them automatically but show them in a will be delete in ... time table
                if ($timenotloggedin > $this->timedelete + $this->timesuspend && $user->suspended == 1) {
                    $todeleteusers[$key] = $user;
                }
            }
        }
        return $todeleteusers;
    }
    public function get_to_reactivate() {
        $users = $this->get_all_users();
        $toactivate = array();
        foreach ($users as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $mytimestamp = time();
                $timenotloggedin = $mytimestamp - $user->lastaccess;
                if ($timenotloggedin < 8035200 && $user->suspended == 1) {
                    $toaactivate[$key] = $user;
                }
            }
        }
        return $toactivate;
    }
    private function get_all_users() {
        global $DB;
        return $DB->get_records('user');
    }
}