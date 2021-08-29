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
 * Manages approval
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupusers\local\manager;

use tool_cleanupusers\transaction;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages approval
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approvalmanager {

    public static function handle_transaction(int $transaction, int $queue, $users) {
        switch ($transaction) {
            case transaction::APPROVE:
                self::handle_approvestatus_update($queue, $users, true);
                break;
            case transaction::CANCEL_APPROVAL:
                self::handle_approvestatus_update($queue, $users, false);
                break;
            case transaction::DELAY_GLOBAL:
            case transaction::DELAY_LOCAL:
            case transaction::DELAY_GLOBAL_INDEF:
            case transaction::DELAY_LOCAL_INDEF:
                self::handle_rollback($transaction, $queue, $users);
                break;
        }
    }

    private static function handle_approvestatus_update(int $queue, $users, $setapproved = true) {
        global $DB;

        if (is_array($users) && count($users) == 0) {
            // Nothing to do here.
            return;
        }

        $sql = 'UPDATE {tool_cleanupusers_approve} ' .
                'SET approved = :setstatus ' .
                'WHERE approved = :isstatus ' .
                'AND action = :action ';
        $params = [
                'setstatus' => $setapproved,
                'isstatus' => !$setapproved,
                'action' => $queue,
        ];

        if ($users) {
            list ($insql, $inparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED);
            $sql .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $DB->execute($sql, $params);
    }

    private static function handle_rollback(int $transaction, $queue, $users) {
        global $DB;

        if (is_array($users) && count($users) == 0) {
            // Nothing to do here.
            return;
        }

        $indefinite = $transaction == transaction::DELAY_LOCAL_INDEF || $transaction == transaction::DELAY_GLOBAL_INDEF;
        $local = $transaction == transaction::DELAY_LOCAL || $transaction == transaction::DELAY_LOCAL_INDEF;

        $delayuntil = null;
        if (!$indefinite) {
            $delayuntil = get_config('tool_cleanupusers', 'rollbackduration') + time();
        }

        $insertsql = 'INSERT INTO {tool_cleanupusers_delay} (userid, action, delayuntil) ' .
                'SELECT a.userid, :action, :delayuntil FROM {tool_cleanupusers_approve} a ' .
                'WHERE a.approved = 0 ';
        $insertparams = [
                'action' => $local ? $queue : 0,
                'delayuntil' => $delayuntil
        ];
        $deletewhere = 'approved = 0 ';
        $deleteparams = [];


        if ($users) {
            list($insql, $inparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED);
            $insertsql .= ' AND a.userid ' . $insql;
            $insertparams = array_merge($insertparams, $inparams);
            $deletewhere .= ' AND userid ' . $insql;
            $deleteparams = array_merge($deleteparams, $inparams);
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->execute($insertsql, $insertparams);
        $DB->delete_records_select('tool_cleanupusers_approve', $deletewhere, $deleteparams);
        $transaction->allow_commit();
    }

}