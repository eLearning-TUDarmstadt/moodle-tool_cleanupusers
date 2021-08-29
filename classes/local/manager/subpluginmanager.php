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
 * Manages subplugins
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupusers\local\manager;

use tool_cleanupusers\userstatusinterface;
use tool_cleanupusers\test_userstatus;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages subplugins
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subpluginmanager {

    public static function get_userstatus_plugin(): userstatusinterface {
        // In case the admin did not submit a sub-plugin, the cronjob is aborted.
        // This is very unlikely to happen since when installing the plugin a default is defined.
        // It could happen when sub-plugin is deleted manually (Uninstalling sub-plugins that are active is not allowed).
        if (!($subplugin = get_config('tool_cleanupusers', 'cleanupusers_subplugin'))) {
            throw new \coding_exception('No subplugin defined!');
        }

        if ($subplugin === 'test') {
            return new test_userstatus();
        }

        $mysubpluginname = "\\userstatus_" . $subplugin . "\\" . $subplugin;
        return new $mysubpluginname();
    }

}
