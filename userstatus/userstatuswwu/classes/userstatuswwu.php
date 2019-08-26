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
 * Sub-plugin userstatuswwu.
 *
 * @package   userstatus_userstatuswwu
 * @copyright 2016/17 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_userstatuswwu;

use tool_cleanupusers\userstatusinterface;
use tool_cleanupusers\archiveduser;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that checks the status of different users
 *
 * @package    userstatus_userstatuswwu
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class userstatuswwu implements userstatusinterface {

    /** @var array of usernames from the university list. */
    private $zivmemberlist = array();

    /** @var array of users who never signed in. */
    private $neverloggedin = array();

    /** @var array of users who should be suspended in the next cron-job. */
    private $tosuspend = array();

    /** @var array of users who should be deleted in the next cron-job. */
    private $todelete = array();

    /** @var array of users who should be reactivated in the next cron-job. */
    private $toreactivate = array();

    /**
     * @var array of strings
     * Each string represents a usergroup a user can belong to according to the zivmemberlist
     * and all users who belong to a group should have access to the moodle instance.
     */
    private $groups = array();

    /** @var string path to the .txt file whcih identifies users and their groups. */
    private $txtpathtomemberlist = '';

    /**
     * Userstatuswwu constructor.
     *
     * @param string $txtpath path to the .txt file which assigns usernames to groups.
     * @param array $groups array of strings, each string represents a usergroup.
     */
    public function __construct($txtpath = null, $groups = null) {
        global $CFG;
        $config = get_config('userstatus_userstatuswwu');
        if (!empty($config->pathtotxt)) {
            $this->txtpathtomemberlist = $config->pathtotxt;
        } else {
            if ($txtpath === null) {
                // Used as default.
                throw new userstatuswwu_exception(get_string('noconfig', 'userstatus_userstatuswwu'));
            } else {
                $this->txtpathtomemberlist = $txtpath;
            }
        }
        if ($groups === null) {
            $this->groups = null;
        } else {
            $this->groups = $groups;
        }
        // From the .txt file the relevant users are extracted.
        $this->zivmemberlist = $this->get_all_ziv_users();

        // With this information moodle users are checked.
        $this->order_suspend();
        $this->order_delete();
        $this->order_never_logged_in();
        $this->order_to_reactivate();
    }

    /**
     * @return array of users who should be suspended in the next cron-job.
     */
    public function get_to_suspend() {
        return $this->tosuspend;
    }

    /**
     * @return array of users who never signed in.
     */
    public function get_never_logged_in() {
        return $this->neverloggedin;
    }

    /**
     * @return array of users who should be deleted in the next cron-job.
     */
    public function get_to_delete() {
        return $this->todelete;
    }

    /**
     * This function is supposed to return users who should be reactivated, by now it always returns an empty array.
     * @return array of users who should be reactivated in the next cron-job.
     */
    public function get_to_reactivate() {
        return $this->toreactivate;
    }
    /**
     * Compares the user of the groups.txt file with the users currently suspended by the plugin and return the users
     * that are listed in the file to be reactivated.
     */
    private function order_to_reactivate() {
        $users = $this->get_users_suspended_not_deleted();
        foreach ($users as $moodleuser) {
            // Adds Object of the user to the array if he/she is not a member.
            if (array_key_exists($moodleuser->username, $this->zivmemberlist)) {
                // Only necessary information is saved in the object and transmitted.
                $informationuser = new archiveduser($moodleuser->id, $moodleuser->suspended, $moodleuser->lastaccess,
                    $moodleuser->username, $moodleuser->deleted);
                $this->toreactivate[$moodleuser->id] = $informationuser;
            }
        }
    }
    /**
     * Scans a given .txt file for specific groups.
     *
     * This function uses fopen() to get a .txt file.
     * Fopen() supports other filetypes, these are not tested. Therefore the usage of a .txt file is recommended.
     *
     * This File includes specific groups for the University of Muenster.
     * When a user belongs to certain group the function adds the user to an array (as a key since array_key_exist is
     * more efficient). The return array includes all users who are allowed to sign in into the Learnweb.
     *
     * @return array of authorized users
     * @throws userstatuswwu_exception
     */
    private function get_all_ziv_users() {
        $zivuserarray = array();
        // Name of the currently identified user who is member of one of the groups.
        $currentname = '';
        // Error in case the given file does not exist.
        if (!file_exists($this->txtpathtomemberlist)) {
            throw new userstatuswwu_exception(get_string('zivlistnotfound', 'userstatus_userstatuswwu'));
        }
        $handle = @fopen($this->txtpathtomemberlist, "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                $currentstring = explode(' ', $buffer);
                // When the next line begins with the current username, there is no need for additional checks,
                // since the username was already saved as a valid user.
                if (!empty($currentname) and $currentstring['0'] === $currentname) {
                    continue;
                }
                // In case the line does not have two words, it can not be handled.
                if (count($currentstring) != 2) {
                    continue;
                }
                // All users including @ are not relevant.
                if (strpos($currentstring['0'], '@')) {
                    continue;
                }
                $currentgroup = rtrim($currentstring[1]);
                // In case no groups were determined the default is used.
                if ($this->groups === null || count($this->groups) == null) {
                    switch ($currentgroup) {
                        // If the user is member of one of the groups, he/she is a valid user.
                        case 'sys=aix-urz':
                        case 'y5lwspz':
                        case 'y5lwzfl':
                        case 'v0csalum':
                        case 'sys=ad-ka':
                        case 'y5lwext':
                        case 'y1moodle':
                        case 'b5lwmw':
                            $currentname = $currentstring[0];
                            $zivuserarray[$currentname] = true;
                            break;
                        default:
                            continue;
                    }
                } else {
                    // In case other groups are used...
                    foreach ($this->groups as $membergroup) {
                        if ($currentgroup === $membergroup) {
                            $currentname = $currentstring[0];
                            $zivuserarray[$currentname] = true;
                            break;
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
     * In case a user is not a member of the list, the user will be saved in the $tosuspend array.
     */
    private function order_suspend() {
        $users = $this->get_users_not_suspended_by_plugin();
        $admins = get_admins();
        foreach ($users as $moodleuser) {
            // Siteadmins will not be suspended.
            if (array_key_exists($moodleuser->id, $admins)) {
                continue;
            }
            // Adds Object of the user to the array if he/she is not a member.
            if (!array_key_exists($moodleuser->username, $this->zivmemberlist)) {
                // Only necessary information is saved in the object and transmitted.
                $informationuser = new archiveduser($moodleuser->id, $moodleuser->suspended, $moodleuser->lastaccess,
                    $moodleuser->username, $moodleuser->deleted);
                $this->tosuspend[$moodleuser->id] = $informationuser;
            }
        }
    }

    /**
     * Checks for all users whether they ever logged in at all.
     */
    private function order_never_logged_in() {
        global $DB;
        // Users who never logged in are collected due to the following criteria:
        // User whose access equals 0 and where not deleted previously and are not in the tool table.
        $sql = 'SELECT u.id, u.lastaccess, u.deleted, u.suspended, u.username
        FROM {user} u
        LEFT JOIN {tool_cleanupusers} t_u ON u.id = t_u.id
        WHERE t_u.id IS NULL AND u.lastaccess=0 AND u.deleted=0 AND u.firstname!=\'Anonym\'';
        $users = $DB->get_records_sql($sql);
        $admins = get_admins();

        foreach ($users as $moodleuser) {
            // In case the user is a siteadmin he/she will not be displayed, since admins are never changed by the ...
            // Plugin.
            if (array_key_exists($moodleuser->id, $admins)) {
                continue;
            }
            // Additional check for properties.
            if ($moodleuser->lastaccess == 0 && $moodleuser->deleted == 0) {
                // Add necessary data to the array.
                $datauser = new archiveduser($moodleuser->id, $moodleuser->suspended, $moodleuser->lastaccess,
                    $moodleuser->username, $moodleuser->deleted);
                $this->neverloggedin[$moodleuser->id] = $datauser;
            }
        }
    }

    /**
     * Checks for all users who are suspended the last point of time they were modified.
     * When the last modification is at least one year ago the user will be saved in the $todelete array.
     * Users who are not in the plugin table will not be handled.
     */
    private function order_delete() {
        // Returns all users from the plugin table.
        $users = $this->get_users_suspended_not_deleted();
        $admins = get_admins();

        foreach ($users as $moodleuser) {
            // Siteadmin will be ignored.
            if (array_key_exists($moodleuser->id, $admins)) {
                continue;
            }
            $timestamp = time();
            if (!empty($moodleuser->timestamp) && !array_key_exists($moodleuser->username, $this->zivmemberlist)) {
                // In case the user is not in the zivmemberlist and was suspended for longer than one year he/she ...
                // ... is supposed to be deleted.
                if ($moodleuser->timestamp < $timestamp - 31622400) {
                    $datauser = new archiveduser($moodleuser->id, $moodleuser->suspended, $moodleuser->lastaccess,
                            $moodleuser->username, $moodleuser->deleted);
                    $this->todelete[$moodleuser->id] = $datauser;
                }
            }
        }
    }

    /**
     * Executes a DB query and returns all users who are not suspended, not deleted and logged at least once in.
     * @return array of users
     */
    private function get_users_not_suspended_by_plugin() {
        global $DB;
        $sql = 'SELECT u.id, u.lastaccess, u.deleted, u.suspended, u.username
        FROM {user} u
        LEFT JOIN {tool_cleanupusers} t_u ON u.id = t_u.id
        WHERE t_u.id IS NULL AND u.lastaccess!=0 AND u.deleted=0';
        return $DB->get_records_sql($sql);
    }

    /**
     * Executes a DB query and returns the id, suspended-status, username, deleted-status, and timestamp of suspension
     * of all users who are suspended and not deleted from the plugin and the user table.
     * @return array of users
     */
    private function get_users_suspended_not_deleted() {
        global $DB;
        $sql = 'SELECT u.id, u.suspended, u.lastaccess, u.username, u.deleted, t_u.timestamp
        FROM {tool_cleanupusers_archive} u
        JOIN {tool_cleanupusers} t_u ON u.id = t_u.id';
        return $DB->get_records_sql($sql);
    }
}