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
 * Suspend, delete or reactivate user. This is called when sideadmin changes user from the cleanupusers
 *
 * administration page.
 *
 * @package tool_cleanupusers
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();
require_once($CFG->dirroot . '/user/lib.php');

$userid         = required_param('userid', PARAM_INT);
// One of: suspend, reactivate or delete.
$action         = required_param('action', PARAM_TEXT);

$PAGE->set_url('/admin/tool/cleanupusers/handleuser.php');
$PAGE->set_context(context_system::instance());

$user = $DB->get_record('user', ['id' => $userid]);
require_capability('moodle/user:update', $PAGE->context);

$url = new moodle_url('/admin/tool/cleanupusers/index.php');

switch ($action) {
    // User should be suspended.
    case 'suspend':
        // Sideadmins, the current $USER and user who are already suspended can not be handeled.
        if (!is_siteadmin($user) && $user->suspended != 1 && $USER->id != $userid) {
            $deprovisionuser = new \tool_cleanupusers\archiveduser(
                $userid,
                $user->suspended,
                $user->lastaccess,
                $user->username,
                $user->deleted
            );
            try {
                $deprovisionuser->archive_me();
            } catch (\tool_cleanupusers\cleanupusers_exception $e) {
                // Notice user could not be suspended.
                notice(get_string('errormessagenoaction', 'tool_cleanupusers'), $url);
            }
            // User was successfully suspended.
            notice(get_string('usersarchived', 'tool_cleanupusers'), $url);
        } else {
            // Notice user could not be suspended.
            notice(get_string('errormessagenotsuspend', 'tool_cleanupusers'), $url);
        }
        break;
    // User should be reactivated.
    case 'reactivate':
        if (!is_siteadmin($user) && $user->suspended != 0 && $USER->id != $userid) {
            $deprovisionuser = new \tool_cleanupusers\archiveduser(
                $userid,
                $user->suspended,
                $user->lastaccess,
                $user->username,
                $user->deleted
            );
            try {
                $deprovisionuser->activate_me();
            } catch (\tool_cleanupusers\cleanupusers_exception $e) {
                // Notice user could not be reactivated.
                notice(get_string('errormessagenoaction', 'tool_cleanupusers'), $url);
            }
            // User successfully reactivated.
            notice(get_string('usersreactivated', 'tool_cleanupusers'), $url);
        } else {
            // Notice user could not be reactivated.
            notice(get_string('errormessagenotactive', 'tool_cleanupusers'), $url);
        }
        break;
    // User should be deleted.
    case 'delete':
        if (!is_siteadmin($user) && $user->deleted != 1 && $USER->id != $userid) {
            $deprovisionuser = new \tool_cleanupusers\archiveduser(
                $userid,
                $user->suspended,
                $user->lastaccess,
                $user->username,
                $user->deleted
            );
            try {
                $deprovisionuser->delete_me();
            } catch (\tool_cleanupusers\cleanupusers_exception $e) {
                $url = new moodle_url('/admin/tool/cleanupusers/index.php');
                // Notice user could not be deleted.
                notice(get_string('errormessagenoaction', 'tool_cleanupusers'), $url);
            }
            notice(get_string('usersdeleted', 'tool_cleanupusers'), $url);
        } else {
            // Notice user could not be deleted.
            notice(get_string('errormessagenoaction', 'tool_cleanupusers'), $url);
        }
        break;
    // Action is not valid.
    default:
        notice(get_string('errormessagenoaction', 'tool_cleanupusers'), $url);
        break;
}
exit();
