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
 * The Class archive_user_task is supposed to show the admin a page of users which will be archived and expectes a submit or
 * cancel reaction.
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_deprovisionuser\task;

defined('MOODLE_INTERNAL') || die();

use tool_deprovisionuser\db as this_db;
use tool_deprovisionuser\deprovisionuser_exception;
use userstatus_timechecker\timechecker;
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
        global $DB, $USER, $PAGE;
        $userdeleted = 0;
        $userarchived = 0;
        if (!empty(get_config('tool_deprovisionuser', 'deprovisionuser_subplugin'))) {
            $subplugin = get_config('tool_deprovisionuser', 'deprovisionuser_subplugin');
            $mysubpluginname = "\\userstatus_" . $subplugin . "\\" . $subplugin;
            $userstatuschecker = new $mysubpluginname();
        } else {
            $userstatuschecker = new userstatuswwu();
        }
        $unabletoactivate = array();

        $archivearray = $userstatuschecker->get_to_suspend();
        $suspendresult = $this->operate_user_array($archivearray, 'suspend');
        $unabletoarchive = $suspendresult['failures'];
        $userarchived = $suspendresult['numbersuccess'];

        $reactivatearray = $userstatuschecker->get_to_reactivate();
        $activateresult = $this->operate_user_array($reactivatearray, 'reactivate');
        $unableactivate = $activateresult['failures'];

        $arraytodelete = $userstatuschecker->get_to_delete();
        $deleteresult = $this->operate_user_array($arraytodelete, 'delete');
        $unabletodelete = $deleteresult['failures'];
        $userdeleted = $deleteresult['numbersuccess'];

        $admin = get_admin();
        $messagetext = get_string('e-mail-archived', 'tool_deprovisionuser', $userarchived) . "\r\n" .get_string('e-mail-deleted',
                'tool_deprovisionuser', $userdeleted);
        if (empty($unabletoactivate) and empty($unabletoarchive) and empty($unabletodelete)) {
            $messagetext .= "\r\n\r\n" . get_string('e-mail-noproblem', 'tool_deprovisionuser');
        } else {
            $messagetext .= "\r\n\r\n" . get_string('e-mail-problematic_delete', 'tool_deprovisionuser',
                    count($unabletodelete)) . "\r\n\r\n" . get_string('e-mail-problematic_suspend', 'tool_deprovisionuser',
                    count($unabletoarchive)) . "\r\n\r\n" . get_string('e-mail-problematic_reactivate', 'tool_deprovisionuser',
                    count($unabletoactivate));
        }
        $user = new \core_user();
        $sender = $user->get_user(-10);
        email_to_user($admin, $sender, 'Update Infos Cron Job tool_deprovisionuser', $messagetext);
        $context = \context_system::instance();
        $event = \tool_deprovisionuser\event\deprovisionusercronjob_completed::create_simple($context, $userarchived, $userdeleted);
        $event->trigger();

        return true;
    }

    private function operate_user_array($userarray, $intention) {
        $numbersuccess = 0;
        $failures = array();
        foreach ($userarray as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess !== 0 && !is_siteadmin($user)) {
                $changinguser = new \tool_deprovisionuser\archiveduser($user->id, $user->suspended);
                try {
                    switch ($intention) {
                        case  'suspend':
                            $changinguser->archive_me();
                            break;
                        case 'reactivate':
                            $changinguser->activate_me();
                            break;
                        case 'delete':
                            $changinguser->delete_me();
                            break;
                        default:
                            break 2;
                    }
                    $numbersuccess++;
                } catch (deprovisionuser_exception $e) {
                    $failures[$key] = $user;
                }
            }
        }
        $result = array();
        $result['numbersuccess'] = $numbersuccess;
        $result['failures'] = $failures;
        return $result;
    }
}