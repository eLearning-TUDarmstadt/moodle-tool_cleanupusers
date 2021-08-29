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
 * Manages delays
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupusers\local\manager;

use tool_cleanupusers\useraction;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages delays
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delaymanager {

    public static function delete_delays($delayids) {
        global $DB;
        if (is_array($delayids) && count($delayids) == 0) {
            return;
        }

        $sql = 'TRUE ';
        $params = [];

        if ($delayids) {
            list($insql, $inparams) = $DB->get_in_or_equal($delayids, SQL_PARAMS_NAMED);
            $sql .= 'AND id ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $DB->delete_records_select('tool_cleanupusers_delay', $sql, $params);
    }

    public static function create_delays($action, $until, $userids) {
        if ($action != 0 && !in_array($action, useraction::actions)) {
            throw new \coding_exception('$action not a valid action');
        }
        global $DB;

        $notinserted = [];

        $record = new \stdClass();
        $record->action = $action;
        $record->delayuntil = $until;
        foreach ($userids as $userid) {
            $record->userid = intval($userid);

            if ($DB->record_exists('tool_cleanupusers_delay', ['userid' => $userid, 'action' => $action])) {
                $notinserted[] = $userid;
                continue;
            }

            $DB->insert_record_raw('tool_cleanupusers_delay', $record, false, true);
        }
        return $notinserted;
    }

}