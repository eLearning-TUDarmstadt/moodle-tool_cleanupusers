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
 * useraction enum-like clas
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupusers;

defined('MOODLE_INTERNAL') || die();

/**
 * useraction enum-like clas
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class useraction {

    public const DELETE = 1;
    public const SUSPEND = 2;
    public const REACTIVATE = 3;

    public const ACTIONS = [self::DELETE, self::SUSPEND, self::REACTIVATE];

    public static function get_string_for_action_verb(int $action) {
        switch ($action) {
            case self::DELETE:
                return get_string('delete', 'tool_cleanupusers');
            case self::SUSPEND:
                return get_string('suspend', 'tool_cleanupusers');
            case self::REACTIVATE:
                return get_string('reactivate', 'tool_cleanupusers');
            default:
                return '';
        }
    }

    public static function get_string_for_action_noun(int $action) {
        switch ($action) {
            case self::DELETE:
                return get_string('deletion', 'tool_cleanupusers');
            case self::SUSPEND:
                return get_string('suspension', 'tool_cleanupusers');
            case self::REACTIVATE:
                return get_string('reactivation', 'tool_cleanupusers');
            default:
                return '';
        }
    }



}
