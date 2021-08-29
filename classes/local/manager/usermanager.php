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
 * Manages user actions
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU, based on Code by Nina Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupusers\local\manager;

use core\session\manager;
use tool_cleanupusers\cleanupusers_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages user actions
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU, based on Code by Nina Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usermanager {

    /**
     * Suspends the user.
     *
     * Therefore makes an entry in the tool_cleanupusers table. Throws an error when the user that should be
     * suspended is already suspended or is the sideadmin.
     *
     * @throws cleanupusers_exception
     */
    public static function suspend_user($userid) {
        global $DB;
        // Get the current user.
        $user = \core_user::get_user($userid);


        // Only apply to users who are not yet suspended, not admins, and to users with correct name
        if ($user->suspended == 0 and !is_siteadmin($user) and $user->username == \core_user::clean_field($user->username, 'username')) {
            $transaction = $DB->start_delegated_transaction();
            // We are already getting the shadowuser here to keep the original suspended status.
            $shadowuser = clone $user;
            // In case the user was not suspended previously he/she might be logged in we kill his/her session.
            $user->suspended = 1;
            manager::kill_user_sessions($user->id);
            user_update_user($user, false);

            $timestamp = time();
            $tooluser = $DB->get_record('tool_cleanupusers', array('id' => $user->id));

            // Document time of editing user in Database.
            // In case there is no entry in the tool table make a new one.
            if (empty($tooluser)) {
                $DB->insert_record_raw('tool_cleanupusers', array('id' => $user->id, 'archived' => 1,
                        'timestamp' => $timestamp), true, false, true);
            }

            // Insert copy of user in second DB and replace user in main table when entry was successful.
            $DB->delete_records('tool_cleanupusers_archive', array('id' => $shadowuser->id));

            $success = $DB->insert_record_raw('tool_cleanupusers_archive', $shadowuser, true, false, true);

            if ($success == true) {
                // Replaces the current user with a pseudo_user that has no reference.
                $cloneuser = self::give_suspended_pseudo_user($shadowuser->id, $timestamp);
                $cloneuser->suspended = 1;
                user_update_user($cloneuser, false);
            }
            $transaction->allow_commit();
            // No error here since user was maybe manually suspended in user table.

        } else {
            throw new cleanupusers_exception(get_string('errormessagenotsuspend', 'tool_cleanupusers'));
        }
    }

    /**
     * Reactivates the user.
     * Therefore deletes the entry in the tool_cleanupusers table and throws an exception when no entry is available.
     * In case a user with the same name exist this user is deleted and the plugin user is restored.
     * @throws cleanupusers_exception
     */
    public static function reactivate_user($userid) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $user = \core_user::get_user($userid);

        // Deletes record of plugin table tool_cleanupusers.
        if (!$DB->record_exists('tool_cleanupusers', array('id' => $user->id))) {
            throw new cleanupusers_exception(get_string('errormessagenotactive', 'tool_cleanupusers'));
        } else if (!$DB->record_exists('tool_cleanupusers_archive', array('id' => $user->id))) {
            throw new cleanupusers_exception(get_string('errormessagenotactive', 'tool_cleanupusers'));
        } else if ($DB->record_exists('user', array('id' => $userid))) {
            throw new cleanupusers_exception(get_string('errormessagenotactive', 'tool_cleanupusers'));
        } else {
            // Both record exist so we have a user which can be reactivated.
            $DB->delete_records('tool_cleanupusers', array('id' => $user->id));
            // If the user is in table replace data.
            $shadowuser = $DB->get_record('tool_cleanupusers_archive', array('id' => $user->id));

            $DB->update_record('user', $shadowuser);
            // Delete records from tool_cleanupusers_archive table.
            $DB->delete_records('tool_cleanupusers_archive', array('id' => $user->id));
        }
        // Gets the new user for additional checks.
        $transaction->allow_commit();
    }

    /**
     * Deletes the user.
     *
     * Therefore
     * (1) Deletes the entry in the tool_cleanupusers and the tool_cleanupusers_archive table.
     * (2) Hashes the username with the sha256 function.
     * (3) Calls the moodle core delete_user function..
     *
     * Throws an error when the side admin should be deleted or user is already flagged as deleted.
     *
     * @throws cleanupusers_exception
     */
    public static function delete_user($userid) {
        global $DB;

        $user = \core_user::get_user($userid);

        if ($user != false and $user->deleted == 0 and !is_siteadmin($user)) {

            $transaction = $DB->start_delegated_transaction();

            // Deletes the records in both plugin tables.
            $DB->delete_records('tool_cleanupusers', array('id' => $user->id));

            $DB->delete_records('tool_cleanupusers_archive', array('id' => $user->id));

            // To secure that plugins that reference the user table do not fail create empty user with a hash as username.
            $newusername = hash('md5', $user->username);

            // Checks whether the username already exist (possible but unlikely).
            if (!$DB->record_exists('user', array("username" => $newusername))) {
                $user->username = $newusername;
                user_update_user($user, false);
            } else {
                // In the unlikely case that hash(username) exist in the table, while loop generates new username.
                while ($DB->record_exists('user', array("username" => $newusername))) {
                    $tempname = $newusername;
                    $newusername = hash('md5', $user->username . $tempname);
                }
                $user->username = $newusername;
                user_update_user($user, false);
            }

            manager::kill_user_sessions($user->id);
            // Core Function has to be executed finally.
            // It can not be executed earlier since moodle then prevents further operations on the user.
            // The Function adds @unknownemail.invalid. and a timestamp to the username.
            // It is secured, that the username is below 100 characters since sha256 produces 64 characters and the...
            // additional string has only 32 characters.
            delete_user($user);
            $transaction->allow_commit();
        } else {
            throw new cleanupusers_exception(get_string('errormessagenotdelete', 'tool_cleanupusers'));
        }
    }

    /**
     * Creates a empty user with 'anonym + id' as username and 'Anonym' as Firstname.
     *
     * @param int $id
     * @param int $timestamp
     * @return object
     */
    private static function give_suspended_pseudo_user($id, $timestamp) {
        $cloneuser = new \stdClass();
        $cloneuser->id = $id;
        // Usernames have to be unique therefore the id is used.
        $cloneuser->username = 'anonym' . $id;
        $cloneuser->firstname = 'Anonym';
        $cloneuser->lastname = '';
        $cloneuser->suspended = 1;
        $cloneuser->email = '';
        $cloneuser->skype = '';
        $cloneuser->icq = '';
        $cloneuser->msn = '';
        $cloneuser->yahoo = '';
        $cloneuser->aim = '';
        $cloneuser->phone1 = '';
        $cloneuser->phone2 = '';
        $cloneuser->institution = '';
        $cloneuser->department = '';
        $cloneuser->address = '';
        $cloneuser->city = '';
        $cloneuser->country = '';
        $cloneuser->lang = '';
        $cloneuser->calendartype = '';
        $cloneuser->firstaccess = 0;
        $cloneuser->lastaccess = 0;
        $cloneuser->currentlogin = 0;
        $cloneuser->lastlogin = 0;
        $cloneuser->secret = '';
        $cloneuser->url = '';
        $cloneuser->picture = 0;
        $cloneuser->description = '';
        $cloneuser->timemodified = '';
        $cloneuser->timecreated = $timestamp;
        $cloneuser->imagealt = '';
        $cloneuser->lastnamephonetic = '';
        $cloneuser->firstnamephonetic = '';
        $cloneuser->middlename = '';
        $cloneuser->alternatename = '';
        $cloneuser->imagealt = '';

        return $cloneuser;
    }

}