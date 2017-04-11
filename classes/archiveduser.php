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
 * Class archive user.
 *
 * @package   tool_deprovisionuser
 * @copyright 2017 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_deprovisionuser;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/lib/moodlelib.php');

/**
 * The class collects the necessary information to suspend, delete and activate users.
 *
 * @package   tool_deprovisionuser
 * @copyright 2017 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class archiveduser {

    // The id of the user.
    private $id;

    // Integer 1 if the user is suspended 0 otherwise.
    private $archived;

    /**
     * Archiveduser constructor.
     * @param int $id
     * @param int $archived
     */
    public function __construct($id, $archived) {
        $this->id = $id;
        $this->archived = $archived;
    }

    /**
     * Suspends the user.
     *
     * Therefore makes an entry in the tool_deprovisionuser table and throws an error when user that should be suspended has an
     * entry in the table.
     *
     * @throws deprovisionuser_exception
     */
    public function archive_me() {
        global $DB;
        // Get the current user.
        $thiscoreuser = new \core_user();
        $user = $thiscoreuser->get_user($this->id);

        if ($user->suspended == 0 and !is_siteadmin($user)) {
            $transaction = $DB->start_delegated_transaction();

            // Suspend user and kill session.
            $user->suspended = 1;
            \core\session\manager::kill_user_sessions($user->id);
            user_update_user($user, false);

            $timestamp = time();
            $tooluser = $DB->get_record('tool_deprovisionuser', array('id' => $user->id));

            // Document time of editing user in Database.
            if (empty($tooluser)) {
                $DB->insert_record_raw('tool_deprovisionuser', array('id' => $user->id, 'archived' => $user->suspended,
                    'timestamp' => $timestamp), true, false, true);
            } else {
                // In case an record already exist the timestamp is updated.
                $tooluser->timestamp = $timestamp;
                $DB->update_record('tool_deprovisionuser', $tooluser);
            }

            // Insert copy of user in second DB and replace user in main table when entry was successful.
            $shadowuser = clone $user;
            $success = $DB->insert_record_raw('deprovisionuser_archive', $shadowuser, true, false, true);
            if ($success == true) {
                $cloneuser = $this->give_pseudo_user($shadowuser->id, $timestamp);
                user_update_user($cloneuser, false);
            }
            $transaction->allow_commit();
            // No error here since user was maybe manually suspended in user table.
        } else {
            throw new deprovisionuser_exception(get_string('errormessagenotsuspend', 'tool_deprovisionuser'));
        }
    }

    /**
     * Reactivates the user.
     *
     * Therefore deletes the entry in the tool_deprovisionuser table and throws an error when no entry is available.
     *
     * @throws deprovisionuser_exception
     */
    public function activate_me() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $thiscoreuser = new \core_user();
        $user = $thiscoreuser->get_user($this->id);

        // Is user suspended in main table?
        if ($user->suspended == 1) {
            $user->suspended = 0;
            user_update_user($user, false);
        }
        // The User to activate was not archived by this plugin.
        if ($user->firstname !== 'Anonym') {
            $transaction->allow_commit();
            return;
        } else {

            // Deletes record of plugin table.
            if (!empty($DB->get_records('tool_deprovisionuser', array('id' => $user->id)))) {
                $DB->delete_records('tool_deprovisionuser', array('id' => $user->id));
            }

            // Is user in the shadow table?
            if (empty($DB->get_record('deprovisionuser_archive', array('id' => $user->id)))) {
                // If there is no user, the main table can not be updated.
                throw new deprovisionuser_exception(get_string('errormessagenotactive', 'tool_deprovisionuser'));
            } else {
                // If user is in table replace data.
                $shadowuser = $DB->get_record('deprovisionuser_archive', array('id' => $user->id));
                $shadowuser->suspended = 0;
                // TODO catch dml exception?
                $DB->update_record('user', $shadowuser);
                // Delete records from deprovisionuser_archive table.
                $DB->delete_records('deprovisionuser_archive', array('id' => $user->id));
            }

            // Delete records from deprovisionuser_archive table.
            $transaction->allow_commit();
            $user = $thiscoreuser->get_user($this->id);
            // When Name is still Anonym something went wrong.
            if ($user->firstname == 'Anonym') {
                throw new deprovisionuser_exception(get_string('errormessagenotactive', 'tool_deprovisionuser'));
            }
        }
    }

    /**
     * Deletes the user.
     *
     * Therefore deletes the entry in the tool_deprovisionuser table and call the moodle core delete_user function.
     * Throws an error when the side admin should be deleted or user is already flagged as deleted.
     *
     * @throws deprovisionuser_exception
     */
    public function delete_me() {
        global $DB;
        $thiscoreuser = new \core_user();
        $user = $thiscoreuser->get_user($this->id);
        if ($user->deleted == 0 and !is_siteadmin($user)) {
            $transaction = $DB->start_delegated_transaction();
            if (!empty($DB->get_records('tool_deprovisionuser', array('id' => $user->id)))) {
                // DML Exception is thrown for any failures.
                $DB->delete_records('tool_deprovisionuser', array('id' => $user->id));
                $DB->delete_records('deprovisionuser_archive', array('id' => $user->id));

            }

            // DML Exception is thrown for any failures. TODO:securenamenottolong
            // To secure that plugins that reference the user table do not fail create empty user with a hash as username.
            $newusername = hash('sha256', $user->username);
            if (empty($DB->get_record('user', array("username" => $newusername)))) {
                $user->username = $newusername;
                user_update_user($user, false);
            } else {
                // In the unlikely case that hash(username) exist in the table, while loop generates new usernames.
                while (!empty($DB->get_record('user', array("username" => $newusername)))) {
                    $tempname = $newusername;
                    $newusername = hash('sha384', $user->username . $tempname);
                }
                $user->username = $newusername;
                user_update_user($user, false);
            }
            \core\session\manager::kill_user_sessions($user->id);
            delete_user($user);
            $transaction->allow_commit();
        } else {
            throw new deprovisionuser_exception(get_string('errormessagenotdelete', 'tool_deprovisionuser'));
        }
    }

    /**
     * Creates a empty user with anonym as username and Anonym as Firstname.
     * @param $id int
     * @param $timestamp int
     * @return object
     */
    private function give_pseudo_user($id, $timestamp) {
        $cloneuser = (object) 0;
        $cloneuser->id = $id;
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
