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

    public function archive_me() {
        global $DB;
        $user = $DB->get_record('user', array('id' => $this->id));
        if ($user->suspended == 0 and !is_siteadmin($user)) {
            $user->suspended = 1;
            if (empty($DB->get_records('tool_deprovisionuser', array('id' => $user->id)))) {
                $transaction = $DB->start_delegated_transaction();
                $DB->insert_record_raw('tool_deprovisionuser', array('id' => $user->id, 'archived' => $user->suspended), true, false, true);
                $transaction->allow_commit();
            } else {
                throw new deprovisionuser_exception('Insert User already archived');
            }
            \core\session\manager::kill_user_sessions($user->id);
            user_update_user($user, false);
        } else {
                throw new deprovisionuser_exception('Not able to archive user');
        }
    }

    public function activate_me() {
        global $DB;
        $user = $DB->get_record('user', array('id' => $this->id));
        if ($user->suspended == 1) {
            $user->suspended = 0;
            if (!empty($DB->get_records('tool_deprovisionuser', array('id' => $user->id)))) {
                $transaction = $DB->start_delegated_transaction();
                $DB->delete_records('tool_deprovisionuser', array('id' => $this->id));
                $transaction->allow_commit();
            } else {
                throw new deprovisionuser_exception('Not able to activate user');
            }
            user_update_user($user, false);
        } else {
            throw new deprovisionuser_exception('Not able to activate user');
        }
    }

    public function delete_me() {
        global $DB;
        $user = $DB->get_record('user', array('id' => $this->id));
        if ($user->deleted == 0) {
            if (!is_siteadmin($user) and $user->deleted != 1) {
                // Force logout.
                $transaction = $DB->start_delegated_transaction();
                $DB->delete_records('tool_deprovisionuser', array('id' => $this->id));
                $transaction->allow_commit();
                \core\session\manager::kill_user_sessions($user->id);
                delete_user($user);
            } else {
                throw new deprovisionuser_exception('Not able to delete user');
            }
            // Success.
        } else {
            throw new deprovisionuser_exception('Not able to delete user');
        }
    }
}