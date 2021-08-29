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
 * Sub-plugin test.
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupusers;

defined('MOODLE_INTERNAL') || die;

/**
 * Sub-plugin test.
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_userstatus implements userstatusinterface {

    public function get_to_suspend() {
        $c = get_config('tool_cleanupusers', 'test_tosuspend');
        $a = explode(',', $c ? $c : '');
        return array_map('intval', $a);
    }

    public function get_never_logged_in() {
        $c = get_config('tool_cleanupusers', 'test_neverloggedin');
        $a = explode(',', $c ? $c : '');
        return array_map('intval', $a);
    }

    public function get_to_delete() {
        $c = get_config('tool_cleanupusers', 'test_todelete');
        $a = explode(',', $c ? $c : '');
        return array_map('intval', $a);
    }

    public function get_to_reactivate() {
        $c = get_config('tool_cleanupusers', 'test_toreactivate');
        $a = explode(',', $c ? $c : '');
        return array_map('intval', $a);
    }

    public static function set_to_suspend(array $users) {
        set_config('test_tosuspend', implode(',', $users), 'tool_cleanupusers');
    }

    public static function set_never_logged_in(array $users) {
        set_config('test_neverloggedin', implode(',', $users), 'tool_cleanupusers');
    }

    public static function set_to_delete(array $users) {
        set_config('test_todelete', implode(',', $users), 'tool_cleanupusers');
    }

    public static function set_to_reactivate(array $users) {
        set_config('test_toreactivate', implode(',', $users), 'tool_cleanupusers');
    }
}
