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
 * Interface for the sub-plugin userstatus.
 *
 * The Plugins of the type userstatus must return values whether users should be deleted, suspended, reactivated
 * or never logged in.
 * Each array has to include at least the following information:
 * (1) userid
 * (2) username
 * (3) lastaccess
 * (3) suspended
 * (3) deleted
 * You can assure that the information is given when you use the tool_cleanupusers_archiveuser class.
 *
 * This Plugin will be used by the cron_job and manually by the admin to determine the appropriate actions for users.
 *
 * @see       \tool_cleanupusers\archiveduser
 * @package   tool_cleanupusers
 * @copyright 2016/17 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupusers;

defined('MOODLE_INTERNAL') || die();

interface userstatusinterface {
    /**
     * Function which returns an array of all users to be suspended by the next cron-job.
     *
     * @return array of users that are supposed to be suspended.
     */
    public function get_to_suspend();
    /**
     * Function which returns an array of all users to be deleted by the next cron-job.
     *
     * @return array of users that are supposed to be deleted.
     */
    public function get_to_delete();
    /**
     * Function which returns an array of all users that never signed in.
     *
     * @return array of users that never signed in.
     */
    public function get_never_logged_in();
    /**
     * Function which returns an array of all users that should be reactivated in the next cron-job.
     *
     * @return array of users to reactivate.
     */
    public function get_to_reactivate();
}
