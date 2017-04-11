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
 * @copyright 2016/17 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_timechecker;

use tool_deprovisionuser\archiveduser;
use tool_deprovisionuser\userstatusinterface;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that checks the status of different users depending on the time they logged in.
 *
 * @package    deprovisionuser_userstatus_timechecker
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class timechecker implements userstatusinterface {

    /** @var int seconds until a user should be suspended */
    private $timesuspend;
    /** @var int seconds until a user should be deleted */
    private $timedelete;

    /**
     * This constructor sets timesuspend and timedelete to the unix time.
     */
    public function __construct() {
        $config = get_config('userstatus_timechecker');
        $this->timesuspend = $config->suspendtime * 84600;
        $this->timedelete = $config->deletetime * 84600;
    }

    /**
     * Function returns the id, username and lastaccess of users who should be suspended.
     * @return array of users to suspend
     */
    public function get_to_suspend() {
        global $DB;
        $select = 'deleted=0 AND suspended=0';
        $users = $DB->get_records_select('user', $select);
        $tosuspend = array();
        foreach ($users as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $mytimestamp = time();
                $timenotloggedin = $mytimestamp - $user->lastaccess;
                if ($timenotloggedin > $this->timesuspend && $user->suspended == 0) {
                    $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username, $user->deleted);
                    $tosuspend[$key] = $informationuser;
                }
            }
        }
        return $tosuspend;
    }

    /**
     * Function returns the id, username and lastaccess of users who never logged in.
     * @return array of users who never logged in
     */
    public function get_never_logged_in() {
        global $DB;
        $select = 'lastaccess=0 AND deleted=0';
        $arrayofuser = $DB->get_records_select('user', $select);
        $neverloggedin = array();
        foreach ($arrayofuser as $key => $user) {
            if (empty($user->lastaccess) && $user->deleted == 0) {
                $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username, $user->deleted);
                $neverloggedin[$key] = $informationuser;
            }
        }
        return $neverloggedin;
    }

    /**
     * Functions returns the id, username and lastaccess of users who should be deleted.
     * @return array of users who should be deleted.
     */
    public function get_to_delete() {
        global $DB;
        $select = 'deleted=0 AND suspended=1 AND firstname!=\'Anonym\'';
        $users = $DB->get_records_select('user', $select);
        $todeleteusers = array();

        // Users who are not suspended by the plugin but are marked as suspended in the main table.
        foreach ($users as $key => $user) {
            // Pseudo-users have as lastaccess 0 therefore they will not be
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $mytimestamp = time();
                $timenotloggedin = $mytimestamp - $user->lastaccess;

                // When the user did not sign in for timesuspend+timedeleted he should be deleted.
                if ($timenotloggedin > $this->timedelete + $this->timesuspend && $user->suspended == 1) {
                    $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username, $user->deleted);
                    $todeleteusers[$key] = $informationuser;
                }
            }
        }

        // Users who are suspended by the plugin.
        $select = 'deleted=0 AND suspended=1';
        $pluginusers = $DB->get_records_select('deprovisionuser_archive', $select);
        foreach ($pluginusers as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $mytimestamp = time();
                // Need to get the record of the tool_deprovisionuser table since the user table has only pseudo information.
                $timearchived = $DB->get_record('tool_deprovisionuser', array('id' => $user->id), 'timestamp');
                $timenotloggedin = $mytimestamp - $timearchived->timestamp;
                if ($timenotloggedin > $this->timedelete && $user->suspended == 1) {
                    $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username, $user->deleted);
                    $todeleteusers[$key] = $informationuser;
                }
            }
        }
        return $todeleteusers;
    }

    /**
     * Returns an array of users that should be reactivated.
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
                if (empty($DB->get_record('deprovisionuser_archive', array('id' => $user->id)))) {
                    $timenotloggedin = $mytimestamp - $user->lastaccess;
                    $activateuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username, $user->deleted);
                } else {
                    $shadowtableuser = $DB->get_record('deprovisionuser_archive', array('id' => $user->id));
                    // There is an entry in the shadowtable, data from the shadowtable is used.
                    if ($shadowtableuser->lastaccess !== 0) {
                        $timenotloggedin = $mytimestamp - $shadowtableuser->lastaccess;
                    } else {
                        continue;
                    }
                    $activateuser = new archiveduser($shadowtableuser->id, $shadowtableuser->lastaccess,
                        $shadowtableuser->lastaccess, $shadowtableuser->username, $shadowtableuser->deleted);

                }
                // When the user signed in he/she should be activated again.
                if ($timenotloggedin < 8035200 && $user->suspended == 1) {
                    $toactivate[$key] = $activateuser;
                }
            }
        }
        return $toactivate;
    }

}