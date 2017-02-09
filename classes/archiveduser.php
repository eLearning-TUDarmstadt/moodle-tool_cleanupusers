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
 * @copyright 2016 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_deprovisionuser;
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/lib/moodlelib.php');

class archiveduser {

    public $id, $archived;

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
        $user = $DB->get_record('user', array('id' => $this->id));
        if ($user->suspended == 0 and !is_siteadmin($user)) {
            $user->suspended = 1;
            \core\session\manager::kill_user_sessions($user->id);
            user_update_user($user, false);
            $timestamp = time();
            $tooluser = $DB->get_record('tool_deprovisionuser', array('id' => $user->id));
            if (empty($tooluser)) {
                $transaction = $DB->start_delegated_transaction();
                $DB->insert_record_raw('tool_deprovisionuser', array('id' => $user->id, 'archived' => $user->suspended, 'timestamp' => $timestamp), true, false, true);
                $shadowuser = $DB->get_record('user', array('id' => $user->id));
                $success = $DB->insert_record_raw('deprovisionuser_archive', $shadowuser, true, false, true);
                if ($success == true) {
                    $shadowuser->username = 'anonym' . $user->id;
                    $shadowuser->firstname = 'Anonym';
                    $shadowuser->lastname = 'Anonym';
                    $DB->update_record('user', $shadowuser);
                }
                // No else case since delegated transaction does revoke all actions in case of failure.
                $transaction->allow_commit();
            } else {
                // In case an record already exist the timestamp is updated.
                $tooluser->timestamp = $timestamp;
                $DB->update_record('tool_deprovisionuser', $tooluser);
            }
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
        $user = $DB->get_record('user', array('id' => $this->id));
        if ($user->suspended == 1) {
            $user->suspended = 0;
            if (!empty($DB->get_records('tool_deprovisionuser', array('id' => $user->id)))) {
                $this->delete_record_table($this->id);
            }
            // In the else case the user is not in the table still the user should be reactivated.
            user_update_user($user, false);
        } else {
            throw new deprovisionuser_exception(get_string('errormessagenotactive', 'tool_deprovisionuser'));
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
        $user = $DB->get_record('user', array('id' => $this->id));
        if ($user->deleted == 0 and !is_siteadmin($user)) {
            if (!empty($DB->get_records('tool_deprovisionuser', array('id' => $user->id)))) {
                $this->delete_record_table($this->id);
            }
            \core\session\manager::kill_user_sessions($user->id);
            delete_user($user);
        } else {
            throw new deprovisionuser_exception(get_string('errormessagenotdelete', 'tool_deprovisionuser'));
        }
    }

    private function delete_record_table($userid) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        // DML Exception is thrown for any failures.
        $DB->delete_records('tool_deprovisionuser', array('id' => $userid));
        $DB->delete_records('deprovisionuser_archive', array('id' => $userid));
        $transaction->allow_commit();
    }
}