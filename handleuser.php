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
 * Suspend, delete or reactivate user. This is called when sideadmin changes user from the deprovisionuser
 * administration page.
 *
 * @package tool_deprovision
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();
require_once($CFG->dirroot.'/user/lib.php');

$userid         = required_param('userid', PARAM_INT);
// One of: suspend, reactivate or delete.
$action         = required_param('action', PARAM_TEXT);

$PAGE->set_url('/admin/tool/deprovisionuser/handleuser.php');
$PAGE->set_context(context_system::instance());

$user = $DB->get_record('user', array('id' => $userid));
require_capability('moodle/user:update', $PAGE->context);

$url = new moodle_url('/admin/tool/deprovisionuser/index.php');

switch($action){
    // User should be suspended.
    case 'suspend':
        // Sideadmins, the current $USER and user who are already suspended can not be handeled.
        if (!is_siteadmin($user) and $user->suspended != 1 and $USER->id != $userid) {
            $deprovisionuser = new \tool_deprovisionuser\archiveduser($userid, $user->suspended, $user->lastaccess,
                $user->username, $user->deleted);
            try {
                $deprovisionuser->archive_me();
            } catch (\tool_deprovisionuser\deprovisionuser_exception $e) {
                // Notice user could not be suspended.
                notice(get_string('errormessagenoaction', 'tool_deprovisionuser'), $url);
            }
            // User was successfully suspended.
            notice(get_string('usersarchived', 'tool_deprovisionuser'), $url);
        } else {
            // Notice user could not be suspended.
            notice(get_string('errormessagenotsuspend', 'tool_deprovisionuser'), $url);
        }
        break;
    // User should be reactivated.
    case 'reactivate':
        if (!is_siteadmin($user) and $user->suspended != 0 and $USER->id != $userid) {
            $deprovisionuser = new \tool_deprovisionuser\archiveduser($userid, $user->suspended, $user->lastaccess,
                $user->username, $user->deleted);
            try {
                $deprovisionuser->activate_me();
            } catch (\tool_deprovisionuser\deprovisionuser_exception $e) {
                // Notice user could not be reactivated.
                notice(get_string('errormessagenoaction', 'tool_deprovisionuser'), $url);
            }
            // User successfully reactivated.
            notice(get_string('usersreactivated', 'tool_deprovisionuser'), $url);
        } else {
            // Notice user could not be reactivated.
            notice(get_string('errormessagenotactive', 'tool_deprovisionuser'), $url);
        }
        break;
    // User should be deleted.
    case 'delete':
        if (!is_siteadmin($user) and $user->deleted != 1 and $USER->id != $userid) {
            $deprovisionuser = new \tool_deprovisionuser\archiveduser($userid, $user->suspended, $user->lastaccess,
                $user->username, $user->deleted);
            try {
                $deprovisionuser->delete_me();
            } catch (\tool_deprovisionuser\deprovisionuser_exception $e) {
                $url = new moodle_url('/admin/tool/deprovisionuser/index.php');
                // Notice user could not be deleted.
                notice(get_string('errormessagenoaction', 'tool_deprovisionuser'), $url);
            }
            notice(get_string('usersdeleted', 'tool_deprovisionuser'), $url);
        } else {
            // Notice user could not be deleted.
            notice(get_string('errormessagenoaction', 'tool_deprovisionuser'), $url);
        }
        break;
    // Action is not valid.
    default:
        notice(get_string('errormessagenoaction', 'tool_deprovisionuser'), $url);
        break;
}
exit();