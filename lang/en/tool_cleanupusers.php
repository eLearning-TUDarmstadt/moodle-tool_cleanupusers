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
 * This file contains language strings used in the cleanupusers admin tool.
 *
 * @package tool_cleanupusers
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Clean up users';
$string['pluginsettingstitle'] = 'General settings';
$string['subplugintype_userstatus'] = 'Returns the status of users';
$string['subplugintype_userstatus_plural'] = 'Returns the status of users';
$string['neverloggedin'] = 'Manage users who never logged in';
$string['toarchive'] = 'Manage users who will be archived';
$string['todelete'] = 'Manage users who will be deleted';
$string['oldusers'] = 'Users';
$string['lastaccess'] = 'Last access:';
$string['usersarchived'] = 'The Users have been archived';
$string['Yes'] = 'Yes';
$string['id'] = 'ID';
$string['No'] = 'No';
$string['Archived'] = 'Archived:';
$string['Willbe'] = 'Will be:';
$string['Neverloggedin'] = 'User that never logged in:';
$string['titletodelete'] = 'Delete Users';
$string['usersdeleted'] = 'The user has been deleted.';
$string['usersreactivated'] = 'The user has been reactivated.';
$string['showuser'] = 'Activate User';
$string['hideuser'] = 'Suspend User';
$string['deleteuser'] = 'Delete User';
$string['aresuspended'] = 'Users currently suspended:';
$string['archive_user_task'] = 'Archive Users';
$string['willbe_archived'] = 'will be archived in the next cron-job';
$string['shouldbedelted'] = 'will be deleted in the next cron-job';
$string['neverlogged'] = 'Never logged in';
$string['nothinghappens'] = 'Not handled since the user never logged in';
$string['waittodelete'] = 'The user is suspended and will not be deleted in the next cron-job.';
$string['e-mail-archived'] = 'In the last cron-job {$a} users were archived.';
$string['e-mail-deleted'] = 'In the last cron-job {$a} users were deleted.';
$string['errormessagenotactive'] = 'Not able to activate user.';
$string['errormessagenotdelete'] = 'Not able to delete user.';
$string['errormessagenotsuspend'] = 'Not able to suspend user';
$string['errormessagenoaction'] = 'The requested action could not be executed.';
$string['errormessagesubplugin'] = 'The sub-plugin you selected is not available. The default will be used.';
$string['e-mail-problematic_delete'] = 'In the last cron-job {$a} users caused exception and could not be deleted.';
$string['e-mail-problematic_suspend'] = 'In the last cron-job {$a} users caused exception and could not be suspended.';
$string['e-mail-problematic_reactivate'] = 'In the last cron-job {$a} users caused exception and could not be reactivated.';
$string['e-mail-noproblem'] = 'No problems occurred in plugin tool_cleanupusers in the last run.';
$string['cronjobcomplete'] = 'tool_cleanupusers cron job complete';
$string['cronjobwasrunning'] = 'The tool_cleanupusers cron job was running. No user was suspended or deleted.';
$string['using-plugin'] = 'You are currently using the <b>{$a}</b> Plugin';

$string['delete'] = 'Delete';
$string['suspend'] = 'Suspend';
$string['reactivate'] = 'Reactivate';
$string['action'] = 'Action';
$string['usersneedingapproval'] = 'Users needing approval';
$string['approvedusers'] = 'Approved users';
$string['xusersneedingapproval'] = '{$a} users needing approval';
$string['xapprovedusers'] = '{$a} approved users';
$string['subplugin'] = 'Subplugin';
$string['subplugins'] = 'Subplugins';
$string['overview'] = 'Overview';
$string['config_delay_duration'] = 'Default duration of a user rollback delay';
$string['tools'] = 'Tools';
$string['approve'] = 'Approve';
$string['rollback'] = 'Rollback';
$string['cancel_approval'] = 'Cancel approval';
$string['rollbacklocaluntil'] = 'Block from {$a->action} until {$a->delay}';
$string['rollbacklocalindef'] = 'Block from {$a->action} indefinitely';
$string['rollbackglobaluntil'] = 'Block from all actions until {$a->delay}';
$string['rollbackglobalindef'] = 'Block from all actions indefinitely';
$string['deletion'] = 'deletion';
$string['suspension'] = 'suspension';
$string['reactivation'] = 'reactivation';
$string['forall'] = 'For all';
$string['forselected'] = 'For selected';
$string['usersneedingapprovalfor'] = 'Users needing approval for {$a}';
$string['usersapprovedfor'] = 'Users approved for {$a}';
$string['blockedusers'] = 'Blocked users';
$string['indefinitely'] = 'Indefinitely';
$string['forallwarning'] = 'This will execute the desired action for all users, regardless of username filters and page of this table! Do you want to continue?';
$string['delayeduntil'] = 'Blocked until';
$string['allactions'] = 'All actions';
$string['approvingusers'] = 'Approving users';
$string['blockedusers'] = 'Blocked users';
$string['manageblockedusers'] = 'Manage blocked users';
$string['blocknewusers'] = 'Block new users';
$string['blockedforever'] = 'Blocked forever';
$string['selectusersvia'] = 'Select users via';
$string['inputfield'] = 'Input field';
$string['usersfile'] = 'Upload file';
$string['usersfile_help'] = 'Upload a file containing usernames seperated by whitespaces, commas or linebreaks';
$string['addedcountdelays'] = 'Added {$a} delays';
$string['couldnotinsertuserids'] = 'Could not add delays for userids {$a}, because they are already blocked for the specified action!';