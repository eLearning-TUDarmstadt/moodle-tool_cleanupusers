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
 * A scheduled task for tool_deprovisionuser cron.
 *
 * The Class archive_user_task is supposed to show the admin a page of users which will be archived and expectes a submit or cancel reaction.
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_deprovisionuser\task;
use tool_deprovisionuser\db as this_db;
use userstatus_userstatuswwu\userstatuswwu;

class archive_user_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('archive_user_task', 'tool_deprovisionuser');
    }

    /**
     * Runs the cron job - Makes a list of all Users who will be archived.
     *
     * Only supposed to execute Logic. Admin is supposed to see the last result of the Cron-Job. We need to save the data of users
     * in Databases to display the results of the last cronjob.
     *
     * @return true
     */
    public function execute() {
        global $DB;
        $userstatuschecker = new userstatuswwu();
        $archivearray = $userstatuschecker->get_cron_to_archive();
        foreach ($archivearray as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $archiveduser = new \tool_deprovisionuser\archiveduser($user->id, $user->suspended);
                $archiveduser->archive_me();
            }
        }
        $activatearray = $userstatuschecker->get_cron_to_activate();
        foreach ($archivearray as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $archiveduser = new \tool_deprovisionuser\archiveduser($user->id, $user->suspended);
                $archiveduser->activate_me();
            }
        }
        $arraytodelete = $userstatuschecker->get_cron_to_delete();
        foreach ($arraytodelete as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $archiveduser = new \tool_deprovisionuser\archiveduser($user->id, $user->suspended);
                // TODO: prepare user to be deleted - not delete them automatically but show them in a will be delete in ... time table
                $archiveduser->delete_me();
            }
        }
        return true;
    }
}