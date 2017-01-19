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
$string['failedtoactivate'] = 'The tool failed to activate a user';
$string['plugininfo'] = 'This plugin deletes users to custom settings. The tables display merely users who are effected by the next cronjob and users who never logged in.';
$string['inprogress'] = 'The development of the plugin is still in progress.';
$string['oldusers'] = 'Users';
$string['lastaccess'] = 'Last Access:';
$string['archive'] = 'Archive Users';
$string['usersarchived'] = 'The Users have been archived';
$string['Yes'] = 'Yes';
$string['No'] = 'No';
$string['Archived'] = 'Archived:';
$string['Willbe'] = 'Will be:';
$string['Neverloggedin'] = 'User that never logged in:';
$string['titleneverloggedin'] = 'Never logged in Users';
$string['titletodelete'] = 'Delete Users';
$string['usersactivated'] = 'User has been activated';
$string['archiveuser'] = 'Users to archive';
$string['usersdeleted'] = 'The User has been deleted.';
$string['showuser'] = 'Activate User';
$string['hideuser'] = 'Suspend User';
$string['suspendtime'] = 'suspendtime';
$string['deletetime'] = 'deletetime';
$string['deleteuser'] = 'Delete User';
$string['archive_user_task'] = 'Archive Users';
$string['deletedin'] = 'is archived, will be deleted on the: {$a}';
$string['willbe_archived'] = 'to be archived';
$string['willbe_notchanged'] = 'not to be archived';
$string['shouldbedelted'] = 'will be deleted in the next cron_job';
$string['neverlogged'] = 'Never logged in';
$string['nothinghappens'] = 'Not handled since they never logged in';
$string['e-mail-archived'] = 'In the last cron job {$a} users were archived.';
$string['e-mail-deleted'] = 'In the last cron job {$a} users were deleted.';
$string['errormessagenotactive'] = 'Not able to activate user.';
$string['errormessagenotdelete'] = 'Not able to delete user.';
$string['errormessagenotsuspend'] = 'Not able to suspend user';

