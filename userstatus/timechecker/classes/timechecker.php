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
 * Sub-plugin timechecker.
 *
 * @package   userstatus_timechecker
 * @copyright 2016/17 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_timechecker;

use tool_cleanupusers\archiveduser;
use tool_cleanupusers\userstatusinterface;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that checks the status of different users depending on the time they did not signed in.
 *
 * @package    userstatus_timechecker
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class timechecker implements userstatusinterface {

    /** @var int seconds until a user should be suspended */
    private $timesuspend;
    /** @var int seconds until a user should be deleted */
    private $timedelete;

    /**
     * This constructor sets timesuspend and timedelete from days to seconds.
     */
    public function __construct() {
        $config = get_config('userstatus_timechecker');
        // Calculates days to seconds.
        $this->timesuspend = $config->suspendtime * 86400;
        $this->timedelete = $config->deletetime * 86400;
    }

    /**
     * All users who are not suspended and not deleted are selected. If a user did not sign in for the hitherto
     * determined suspendtime he/she will be returned.
     * The array includes merely the necessary information which comprises the userid, lastaccess, suspended, deleted
     * and the username.
     *
     * @return array of users to suspend
     */
    public function get_to_suspend() {
        global $DB;
        $select = 'deleted=0 AND suspended=0 AND lastaccess!=0';
        $users = $DB->get_records_select('user', $select);
        $tosuspend = array();
        foreach ($users as $key => $user) {
            if (!is_siteadmin($user)) {
                $mytimestamp = time();
                $timenotloggedin = $mytimestamp - $user->lastaccess;
                if ($timenotloggedin > $this->timesuspend && $user->suspended == 0) {
                    $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username,
                        $user->deleted);
                    $tosuspend[$key] = $informationuser;
                }
            }
        }
        return $tosuspend;
    }

    /**
     * All users who never logged in will be returned in the array.
     * The array includes merely the necessary information which comprises the userid, lastaccess, suspended, deleted
     * and the username.
     *
     * @return array of users who never logged in
     */
    public function get_never_logged_in() {
        global $DB;
        $select = 'lastaccess=0 AND deleted=0 AND firstname!=\'Anonym\'';
        $arrayofuser = $DB->get_records_select('user', $select);
        $neverloggedin = array();
        foreach ($arrayofuser as $key => $user) {
            if (empty($user->lastaccess) && $user->deleted == 0) {
                $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username,
                    $user->deleted);
                $neverloggedin[$key] = $informationuser;
            }
        }
        return $neverloggedin;
    }

    /**
     * All users who should be deleted will be returned in the array.
     * The array includes merely the necessary information which comprises the userid, lastaccess, suspended, deleted
     * and the username.
     * The function checks the user table and the cleanupusers_archive table. Therefore users who are suspended by
     * the tool_cleanupusers plugin and users who are suspended manually are screened.
     *
     * @return array of users who should be deleted.
     */
    public function get_to_delete() {
        global $DB;

        // Select clause for users who are suspended.
        $select = 'deleted=0 AND suspended=1 AND (lastaccess!=0 OR firstname=\'Anonym\')';
        $users = $DB->get_records_select('user', $select);
        $todeleteusers = array();

        // Users who are not suspended by the plugin but are marked as suspended in the main table.
        foreach ($users as $key => $user) {
            // Additional check for deletion, lastaccess and admin.
            if ($user->deleted == 0 && !is_siteadmin($user)) {
                $mytimestamp = time();

                // User was suspended by the plugin.
                if ($user->firstname == 'Anonym' && $user->lastaccess == 0) {
                    $select = 'id=' . $user->id;

                    $record = $DB->get_records_select('tool_cleanupusers', $select);
                    if (!empty($record) && $record[$user->id]->timestamp != 0) {
                        $suspendedbyplugin = true;
                        $timearchived = $DB->get_record('tool_cleanupusers', array('id' => $user->id), 'timestamp');
                        $timenotloggedin = $mytimestamp - $timearchived->timestamp;
                    } else {
                        // Users firstname is Anonym although he is not in the plugin table. It can not be determined
                        // when the user was suspended therefore he/she can not be handled.
                        continue;
                    }
                } else if ($user->lastaccess != 0) {
                    // User was suspended manually.
                    $suspendedbyplugin = false;
                    $timenotloggedin = $mytimestamp - $user->lastaccess;
                } else {
                    // The user was not suspended by the plugin but does not have an last access, therefore he/she is
                    // not handled. This should not happen due to the select clause.
                    continue;
                }
                // When the user did not sign in for the timedeleted he/she should be deleted.
                if ($timenotloggedin > $this->timedelete && $user->suspended == 1) {
                    if ($suspendedbyplugin) {
                        // Users who are suspended by the plugin, therefore the plugin table is used.
                        $select = 'id=' . $user->id;
                        $pluginuser = $DB->get_record_select('cleanupusers_archive', $select);
                        $informationuser = new archiveduser($pluginuser->id, $pluginuser->suspended,
                            $pluginuser->lastaccess, $pluginuser->username, $pluginuser->deleted);
                    } else {
                        $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess,
                            $user->username, $user->deleted);
                    }
                    $todeleteusers[$key] = $informationuser;
                }
            }
        }

        return $todeleteusers;
    }

    /**
     * All user that should be reactivated will be returned.
     *
     * User should be reactivated when their lastaccess is smaller then the timesuspend variable. Although users are
     * not able to sign in when they are flagged as suspended, this is necessary to react when the timesuspended setting
     * is changed.
     *
     * @return array of objects
     */
    public function get_to_reactivate() {
        global $DB;
        // Only users who are currently suspended are relevant.
        $select = 'deleted=0 AND suspended=1';
        $users = $DB->get_records_select('user', $select);
        $toactivate = array();

        foreach ($users as $key => $user) {
            if ($user->suspended == 1 && $user->deleted == 0 && !is_siteadmin($user)) {
                $mytimestamp = time();

                // There is no entry in the shadow table, user that is supposed to be reactivated was archived manually.
                if (empty($DB->get_record('cleanupusers_archive', array('id' => $user->id)))) {
                    $timenotloggedin = $mytimestamp - $user->lastaccess;
                    $activateuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username,
                        $user->deleted);
                } else {
                    $shadowtableuser = $DB->get_record('cleanupusers_archive', array('id' => $user->id));
                    // There is an entry in the shadowtable, data from the shadowtable is used.
                    if ($shadowtableuser->lastaccess !== 0) {
                        $timenotloggedin = $mytimestamp - $shadowtableuser->lastaccess;
                    } else {
                        // In case lastaccess is 0 it can not decided whether the user should be reactivated.
                        continue;
                    }
                    $activateuser = new archiveduser($shadowtableuser->id, $shadowtableuser->suspended,
                        $shadowtableuser->lastaccess, $shadowtableuser->username, $shadowtableuser->deleted);

                }

                // When the time not logged in is smaller than the timesuspend he/she should be activated again.
                if ($timenotloggedin < $this->timesuspend && $user->suspended == 1) {
                    $toactivate[$key] = $activateuser;
                }
            }
        }
        return $toactivate;
    }

}