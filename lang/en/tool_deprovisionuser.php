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
 * This file contains language strings used in the deprovisionuser admin tool.
 *
 * @package tool_deprovisionuser
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'deprovisionuser';
$string['plugintitel'] = 'Deprovision of Users';
$string['subplugintype_userstatus'] = 'Returns the Status of Students';
$string['subplugintype_userstatus_plural'] = 'Returns the Status of Students';
$string['oldusers'] = 'Users';
$string['lastaccess'] = 'Last Access:';
$string['usersarchived'] = 'The Users have been archived';
$string['Yes'] = 'Yes';
$string['No'] = 'No';
$string['Archived'] = 'Archived:';
$string['Willbe'] = 'Will be:';
$string['Neverloggedin'] = 'User that never logged in:';
$string['titletodelete'] = 'Delete Users';
$string['usersdeleted'] = 'The User has been deleted.';
$string['usersreactivated'] = 'The User has been reactivated.';
$string['showuser'] = 'Activate User';
$string['hideuser'] = 'Suspend User';
$string['deleteuser'] = 'Delete User';
$string['archive_user_task'] = 'Archive Users';
$string['willbe_archived'] = 'will be archived in the next cron_job';
$string['shouldbedelted'] = 'will be deleted in the next cron_job';
$string['neverlogged'] = 'Never logged in';
$string['nothinghappens'] = 'Not handled since they never logged in';
$string['e-mail-archived'] = 'In the last cron job {$a} users were archived.';
$string['e-mail-deleted'] = 'In the last cron job {$a} users were deleted.';
$string['errormessagenotactive'] = 'Not able to activate user.';
$string['errormessagenotdelete'] = 'Not able to delete user.';
$string['errormessagenotsuspend'] = 'Not able to suspend user';
$string['errormessagenoaction'] = 'The requested action could not be executed.';
$string['errormessagesubplugin'] = 'The Subplugin you selected is not available. The default will be used.';
$string['e-mail-problematic_delete'] = 'In the last cron job {$a} users caused exception and could not be deleted.';
$string['e-mail-problematic_suspend'] = 'In the last cron job {$a} users caused exception and could not be suspended.';
$string['e-mail-problematic_reactivate'] = 'In the last cron job {$a} users caused exception and could not be reactivated.';
$string['e-mail-noproblem'] = 'No problems occured in plugin tool_deprovisionuser in the last run.';
$string['cronjobcomplete'] = 'Deprovisionuser-cronjob complete';
$string['cronjobwasrunning'] = 'The tool_deprovisionuser cron_job was running.';
$string['subpluginsof'] = 'Subplugins of deprovisionuser';
