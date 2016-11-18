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
 * File to delete users.
 *
 * @package tool_deprovision
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_login();
require_once($CFG->dirroot.'/user/lib.php');
require_once(dirname(__FILE__).'/classes/archiveduser.php');
require_once($CFG->libdir.'/moodlelib.php');

$userid         = required_param('userid', PARAM_INT);
$deleted       = required_param('deleted', PARAM_INT);

$PAGE->set_url('/admin/tool/deprovisionuser/archiveuser.php');
$PAGE->set_context(context_system::instance());

global $USER;
$user = $DB->get_record('user', array('id' => $userid));
if ($deleted == 0) {
    // TODO require_capability('moodle/user:update', $sitecontext);
    if (true) {
        // TODO check if user is the same person
        if (!is_siteadmin($user) and $user->deleted != 1 and $USER->id != $userid) {
            // Force logout.
            $transaction = $DB->start_delegated_transaction();
            $DB->delete_records('tool_deprovisionuser',array('id' => $userid));
            $transaction->allow_commit();
            \core\session\manager::kill_user_sessions($user->id);
            delete_user($user);
        } else {
            notice('notworking',
                $CFG->wwwroot . '/admin/tool/deprovisionuser/index.php');
        }
    } else {
        notice('notworking',
            $CFG->wwwroot . '/admin/tool/deprovisionuser/index.php');
    }
    notice(get_string('usersdeleted', 'tool_deprovisionuser'),
    $CFG->wwwroot . '/admin/tool/deprovisionuser/index.php');
} else {
    notice('notworking',
    $CFG->wwwroot . '/admin/tool/deprovisionuser/index.php');
}
exit();
