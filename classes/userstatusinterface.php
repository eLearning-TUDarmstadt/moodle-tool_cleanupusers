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
 * Interface for the Subplugin userstatus.
 *
 * The Plugins of the type userstatus must return values whether users should be deleted archived, reactivated or no action is required.
 * This Plugin will be used by the cron_job and manually bz the admin to determine the appropriate actions for users.
 *
 * @package   tool_deprovisionuser
 * @copyright 2016 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_deprovisionuser;

defined('MOODLE_INTERNAL') || die();

interface userstatusinterface {
    /**
     * Function which returns an array of all users to be suspended by the next cron_job.
     *
     * @return array of users that are supposed to be suspended.
     */
     public function get_users_for_suspending();
    /**
     * Function which returns an array of all users to be deleted by the next cron_job.
     *
     * @return array of users that are supposed to be deleted.
     */
     public function get_to_delete();
    /**
     * Function which returns an array of all users that never logged in.
     *
     * @return array of users that never logged in.
     */
     public function get_never_logged_in();
}