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
 * Settings.php
 * @package deprovisionuser_userstatus_timechecker
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    $url = $CFG->wwwroot . '/' . $CFG->admin . '/tool/deprovisionuser/timechecker/index.php';
    $settings->add(new admin_setting_configtext('deprovisionuser_timechecker_suspend_user', get_string('hideuser', 'tool_deprovisionuser'),
        get_string('timechecker_time_to_archive', 'userstatus_timechecker'), 90, PARAM_INT));
    $settings->add(new admin_setting_configtext('deprovisionuser_timechecker_delete_user', get_string('deleteuser', 'tool_deprovisionuser'),
        get_string('timechecker_time_to_delete', 'userstatus_timechecker'), 365, PARAM_INT));
// TODO look for get_config tp get values tabel mdl_config name=suspend_user bzw delete_user
}