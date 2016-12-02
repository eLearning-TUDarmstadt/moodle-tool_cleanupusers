<?php
/**
 * Created by IntelliJ IDEA.
 * User: nina
 * Date: 15.11.16
 * Time: 17:46
 */
class archiveduser {

    public $id, $archived;
    public function __construct($id, $archived) {
        $this->id = $id;
        $this->archived = $archived;
    }

    public function archive_me() {
        global $DB, $CFG;
        $user = $DB->get_record('user', array('id' => $this->id));
        if ($this->archived == 0) {
            if (!is_siteadmin($user) and $user->suspended != 1) {
                $user->suspended = 1;
                // Force logout.
                \core\session\manager::kill_user_sessions($user->id);
                $transaction = $DB->start_delegated_transaction();
                // TODO inserts not a binary but \x31 for true
                $DB->insert_record_raw('tool_deprovisionuser', array('id' => $this->id, 'archived' => true), true, false, true);
                $transaction->allow_commit();
                user_update_user($user, false);
            } else {
                throwException('Something went wrong');
                // Adequat exception
            }
            // Return Statement
        } else {
            throwException('Something went wrong');
            // Insert User already archived exception
        }
        exit();
    }
    public function activate_me() {
        global $DB, $CFG;
        $user = $DB->get_record('user', array('id' => $this->id));
        if ($this->archived == 1) {
            if (!is_siteadmin($user) and $user->suspended != 0) {
                $user->suspended = 0;
                $transaction = $DB->start_delegated_transaction();
                $DB->delete_records('tool_deprovisionuser', array('id' => $this->id));
                $transaction->allow_commit();
                user_update_user($user, false);
            } else {
                throwException('Something went wrong');
                // Throw adequat exception
            }
        } else {
            throwException('Something went wrong');
            // Insert User already archived exception
        }
        exit();
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
                throwException('Something went wrong');
                // Throw Exception
            }
            // Success
        } else {
            throwException('Something went wrong');
            // Throw Exception
        }
        exit();
    }
}