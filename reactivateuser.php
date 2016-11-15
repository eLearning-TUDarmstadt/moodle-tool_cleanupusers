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
 * This file contains the class for reactivate users
 *
 * @package tool_deprovision
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class reactivateuser {
    public function remove_user_from_table($userid) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
            $success = $DB->delete_records('tool_deprovisionuser_inactive', array('id' => $userid));
        $transaction->allow_commit();
        if ($success === false) {
            throwException(get_string('failedtoactivate', 'tool_deprovisionuser'));
            // TODO more action retry etc.?
        }
    }
}