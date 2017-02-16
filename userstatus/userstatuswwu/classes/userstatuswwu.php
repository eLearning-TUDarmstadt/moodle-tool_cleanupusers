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
 * @package   userstatus_userstatuswwu
 * @copyright 2016 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_userstatuswwu;

use tool_deprovisionuser\userstatusinterface;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that checks the status of different users
 *
 * @package    userstatus_userstatuswwu
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class userstatuswwu implements userstatusinterface {
    /**
     * @var array
     */
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
    /**
     * @var array
     */
    private $groups = array();
    /**
     * @var string
     */
    private $membertxtrout = '';

    public function __construct($txtrout = null, $groups = null) {
        global $CFG;
        if ($txtrout === null) {
            $this->membertxtrout = '/home/nina/data/groups_excerpt_short.txt';
        } else {
            $this->membertxtrout = $txtrout;
        }
        if ($groups === null) {
            $this->groups = null;
        } else {
            $this->groups = $groups;
        }
        $this->zivmemberlist = $this->get_all_ziv_users();

        $this->order_suspend();
        $this->order_delete();
        $this->order_never_logged_in();
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
        if (!file_exists($this->membertxtrout)) {
            throw new userstatuswwu_exception(get_string('zivlistnotfound', 'userstatus_userstatuswwu'));
        }
        $handle = @fopen($this->membertxtrout, "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                if (!empty($currentname) and strpos($buffer, $currentname) === 0) {
                    continue;
                }
                $currentstring = explode(' ', $buffer);
                if (count($currentstring) != 2) {
                    continue;
                }
                if (count($this->groups) == null) {
                    if (array_key_exists(1, $currentstring)) {
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
                } else {
                    foreach ($this->groups as $membergroup) {
                        $group = rtrim($currentstring[1]);
                        if ($group === $membergroup) {
                            $currentname = $currentstring[0];
                            array_push($zivuserarray, $currentname);
                            continue;
                        }
                    }
                }
            }
            fclose($handle);

        }
        return $zivuserarray;
    }

    /**
     * Checks for all users who are not suspended whether they are member of the $zivmemberlist.
     * When they are not member the user will be saved in the tosuspend array.
     */
    private function order_suspend() {
        $allusers = $this->get_users_not_suspended();
        foreach ($allusers as $moodleuser) {
            if (is_siteadmin($moodleuser)) {
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
                $datauser = (object) 0;
                $datauser->id = $moodleuser->id;
                $datauser->username = $moodleuser->username;
                $datauser->suspended = $moodleuser->suspended;
                $datauser->deleted = $moodleuser->deleted;
                $datauser->lastaccess = $moodleuser->lastaccess;
                $this->tosuspend[$moodleuser->id] = $datauser;
            }
        }
    }

    /**
     * Checks for all users whether they ever logged in at all.
     */
    private function order_never_logged_in() {
        global $DB;
        $users = $DB->get_records('user');
        foreach ($users as $moodleuser) {
            if (is_siteadmin($moodleuser) || !empty($DB->get_record('tool_deprovisionuser', array('id' => $moodleuser->id)))) {
                continue;
            }
            if ($moodleuser->lastaccess == 0) {
                $datauser = (object) 0;
                $datauser->id = $moodleuser->id;
                $datauser->username = $moodleuser->username;
                $datauser->suspended = $moodleuser->suspended;
                $datauser->deleted = $moodleuser->deleted;
                $datauser->lastaccess = $moodleuser->lastaccess;
                $this->neverloggedin[$moodleuser->id] = $datauser;
            }
        }
    }

    /**
     * Checks for all users who are suspended the last point of time they were modified.
     * When the last modification is at least one year ago the user will be saved in the todelete array.
     */
    private function order_delete() {
        global $DB;
        $allusers = $this->get_users_suspended_not_deleted();
        foreach ($allusers as $moodleuser) {
            if (is_siteadmin($moodleuser)) {
                continue;
            }
            $timestamp = time();
            $entry = $DB->get_record('tool_deprovisionuser', array('id' => $moodleuser->id));
            if (!empty($entry->timestamp)) {
                if ($entry->timestamp < $timestamp - 31622400) {
                    $datauser = (object) 0;
                    $datauser->id = $moodleuser->id;
                    $datauser->username = $moodleuser->username;
                    $datauser->suspended = $moodleuser->suspended;
                    $datauser->deleted = $moodleuser->deleted;
                    $datauser->lastaccess = $moodleuser->lastaccess;
                    $this->todelete[$moodleuser->id] = $datauser;
                }
            }
        }
    }

    /**
     * Executes a DB query and returns all users who are not suspended, not deleted and logged at least once in.
     * @return array of users
     */
    private function get_users_not_suspended() {
        global $DB;
        $select = 'deleted=0 AND suspended=0 AND lastaccess>0';
        return $DB->get_records_select('user', $select);
    }

    /**
     * Executes a DB query and returns all users who are suspended and not deleted.
     * @return array of users
     */
    private function get_users_suspended_not_deleted() {
        global $DB;
        return $DB->get_records('user', array('suspended' => 1, 'deleted' => 0));
    }
}