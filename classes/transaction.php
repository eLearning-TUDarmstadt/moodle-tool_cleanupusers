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
 * Transaction enum-like class
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupusers;

defined('MOODLE_INTERNAL') || die();

/**
 * Transaction enum-like class
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transaction {

    public const APPROVE = 1;
    public const DELAY_GLOBAL = 2;
    public const DELAY_LOCAL = 3;
    public const DELAY_GLOBAL_INDEF = 4;
    public const DELAY_LOCAL_INDEF = 5;
    public const CANCEL_APPROVAL = 6;

    public const ACTIONS = [
            self::APPROVE, self::DELAY_GLOBAL, self::DELAY_LOCAL,
            self::DELAY_GLOBAL_INDEF, self::DELAY_LOCAL_INDEF, self::CANCEL_APPROVAL
    ];

    public static function is_rollback(int $transaction) {
        return in_array($transaction, [self::DELAY_GLOBAL, self::DELAY_LOCAL, self::DELAY_GLOBAL_INDEF, self::DELAY_LOCAL_INDEF]);
    }
}
