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
 * Subplugin userstatuswwu.
 *
 * @package   tool_deprovisionuser
 * @copyright 2016 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_userstatuswwu;

use tool_deprovisionuser\userstatusinterface;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that checks the status of different users
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class userstatuswwu implements userstatusinterface {

    private $zivmemberlist = array();
    /**
     * @var array
     */
    private $neverloggedin = array();
    /**
     * @var array
     */
    private $tosuspend = array();
    /**
     * @var array
     */
    private $todelete = array();
    /**
     * @var array
     */
    private $toreactivate = array();

    public function __construct() {
        $this->zivmemberlist = $this->get_all_ziv_users();
        $this->order_to_arrays();
    }

    /**
     * @return array
     */
    public function get_to_suspend() {
        return $this->tosuspend;
    }

    /**
     * @return array
     */
    public function get_never_logged_in() {
        return $this->neverloggedin;
    }

    /**
     * @return array
     */
    public function get_to_delete() {
        return $this->todelete;
    }

    /**
     * @return array
     */
    public function get_to_reactivate() {
        return $this->toreactivate;
    }

    /**
     * Scans a given txt file for specific groups.
     *
     * This function uses fopen() to get a .txt file. This File includes specific groups for the University of Münster.
     * When a user belongs to certain group the function adds the user to an array. Therefore the return array includes
     * all users who are allowed to log in into the Learnweb.
     *
     * @return array of authorized users
     */
    private function get_all_ziv_users() {
        $zivuserarray = array();
        $currentname = '';
        // TODO: Later right .txt file
        $handle = @fopen("groups_excerpt_short.txt", "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                if (strpos($buffer, $currentname) === 0) {
                    continue;
                } else {
                    $currentstring = explode(' ', $buffer);
                    $group = rtrim($currentstring[1]);
                    switch ($group) {
                        case 'sys=aix-urz':
                        case 'y5lwspz':
                        case 'y5lwzfl':
                        case 'v0csalum':
                        case 'sys=ad-ka':
                        case 'y5lwext':
                        case 'y1moodle':
                        case 'b5lwmw':
                            $currentname = $currentstring[0];
                            array_push($zivuserarray, $currentname);
                            break;
                        default:
                            continue;
                    }
                }
            }
        }
        fclose($handle);
        return $zivuserarray;
    }

    private function order_to_arrays() {
        $this->order_suspend();
        $this->order_delete();
        $this->order_never_logged_in();
    }
    private function order_suspend() {
        $allusers = $this->get_users_not_suspended();
        foreach ($allusers as $moodleuser) {
            if($admin = get_admin() == $moodleuser) {
                continue;
            }
            $ismember = false;
            foreach ($this->zivmemberlist as $zivmember) {
                if ($zivmember == $moodleuser->username) {
                    $ismember = true;
                    continue;
                }
            }
            if ($ismember == false) {
                array_push($this->tosuspend, $moodleuser);
            }
        }
    }
    private function order_never_logged_in() {
        global $DB;
        $users = $DB->get_records('user');
        foreach ($users as $moodleuser) {
            if ($admin = get_admin() == $moodleuser) {
                continue;
            }
            if ($moodleuser->lastaccess == 0) {
                array_push($this->neverloggedin, $moodleuser);
            }
        }
    }

    private function order_delete() {
        $allusers = $this->get_users_suspended_not_deleted();
        foreach ($allusers as $moodleuser) {
            if ($admin = get_admin() == $moodleuser) {
                continue;
            }
            $timestamp = time();
            if ($moodleuser->timemodified < $timestamp - 31622400) {
                array_push($this->todelete, $moodleuser);
            }
        }
    }

    private function get_users_not_suspended() {
        global $DB;
        $select = 'suspended=0 AND deleted=0 AND lastaccess>0';
        return $DB->get_records_select('user', $select);
    }
    private function get_users_suspended_not_deleted() {
        global $DB;
        return $DB->get_records('user', array('suspended' => 1, 'deleted' => 0));
    }
}