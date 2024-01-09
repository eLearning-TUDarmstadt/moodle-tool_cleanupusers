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
        $users = $this->get_users_not_suspended_by_plugin();
        $admins = get_admins();
        $tosuspend = [];
        foreach ($users as $key => $user) {
            if (array_key_exists($user->id, $admins)) {
                continue;
            }

            $mytimestamp = time();
            $timenotloggedin = $mytimestamp - $user->lastaccess;
            if ($timenotloggedin > $this->timesuspend) {
                $informationuser = new archiveduser(
                    $user->id,
                    $user->suspended,
                    $user->lastaccess,
                    $user->username,
                    $user->deleted
                );
                $tosuspend[$key] = $informationuser;
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
        $neverloggedin = [];
        foreach ($arrayofuser as $key => $user) {
            if (empty($user->lastaccess) && $user->deleted == 0) {
                $informationuser = new archiveduser(
                    $user->id,
                    $user->suspended,
                    $user->lastaccess,
                    $user->username,
                    $user->deleted
                );
                $neverloggedin[$key] = $informationuser;
            }
        }
        return $neverloggedin;
    }

    /**
     * All users who should be deleted will be returned in the array.
     * The array includes merely the necessary information which comprises the userid, lastaccess, suspended, deleted
     * and the username.
     * The function checks the user table and the tool_cleanupusers_archive table. Therefore users who are suspended by
     * the tool_cleanupusers plugin and users who are suspended manually are screened.
     *
     * @return array of users who should be deleted.
     */
    public function get_to_delete() {
        global $DB;
        $mytimestamp = time();
        // The last possible date users must have logged in before they get deleted.
        $datetodelete = $mytimestamp - $this->timedelete;

        $todeleteusers = [];
        $admins = get_admins();

        // 1. Get all users automatic suspended by the plugin.
        $sql = "SELECT u.id, u.suspended, u.lastaccess, u.username, u.deleted FROM {tool_cleanupusers_archive} u
          JOIN {tool_cleanupusers} tcu ON u.id = tcu.id
          WHERE u.deleted=0
          AND u.lastaccess!=0
          AND tcu.timestamp < :dat";
        $params = ['dat' => $datetodelete];
        $usersautomaticsuspended = $DB->get_recordset_sql($sql, $params);

        foreach ($usersautomaticsuspended as $user) {
            if (array_key_exists($user->id, $admins)) {
                continue;
            } else {
                $informationuser = new archiveduser(
                    $user->id,
                    $user->suspended,
                    $user->lastaccess,
                    $user->username,
                    $user->deleted
                );
                $todeleteusers[$user->id] = $informationuser;
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
        $archived = $DB->get_records(
            'tool_cleanupusers_archive',
            null,
            '',
            'id, username, lastaccess, suspended, deleted'
        );
        $toactivate = [];
        $admins = get_admins();

        foreach ($users as $key => $user) {
            if (array_key_exists($user->id, $admins)) {
                continue;
            } else {
                $mytimestamp = time();
                // There is no entry in the shadow table, user that is supposed to be reactivated was archived manually.
                if (!array_key_exists($user->id, $archived)) {
                    $timenotloggedin = $mytimestamp - $user->lastaccess;
                    $activateuser = new archiveduser(
                        $user->id,
                        $user->suspended,
                        $user->lastaccess,
                        $user->username,
                        $user->deleted
                    );
                } else {
                    $shadowtableuser = $archived[$user->id];
                    // There is an entry in the shadowtable, data from the shadowtable is used.
                    if ($shadowtableuser->lastaccess !== 0) {
                        $timenotloggedin = $mytimestamp - $shadowtableuser->lastaccess;
                    } else {
                        // In case lastaccess is 0 it can not decided whether the user should be reactivated.
                        continue;
                    }
                    $activateuser = new archiveduser(
                        $shadowtableuser->id,
                        $shadowtableuser->suspended,
                        $shadowtableuser->lastaccess,
                        $shadowtableuser->username,
                        $shadowtableuser->deleted
                    );
                }

                // When the time not logged in is smaller than the timesuspend he/she should be activated again.
                if ($timenotloggedin < $this->timesuspend && $user->suspended == 1) {
                    $toactivate[$key] = $activateuser;
                }
            }
        }
        return $toactivate;
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
}
