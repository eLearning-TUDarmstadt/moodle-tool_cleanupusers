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
 * The Plugins of the type userstatus must return values whether users should be deleted, archived or reactivated.
 * The subplugins will be used by the cronjob and manually by the admin to determine the appropriate actions for users.
 *
 * @package   tool_deprovisionuser
 * @copyright 2016/17 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_deprovisionuser\plugininfo;

use admin_settingpage;
use core\plugininfo\base;

defined('MOODLE_INTERNAL') || die();

/**
 * The general settings for all subplugins of userstatus.
 * Defines the deinstallation settings and adds subplugins to the admin tree, if they have a settings.php.
 *
 * @package    tool_deprovisionuser
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userstatus extends base {

    /**
     * Returns true when subplugin can be deleted false when not.
     * Returns false for the userstatuswwu subplugin and for any plugin currently in usage, otherwise true.
     * @return bool
     */
    public function is_uninstall_allowed() {
        global $CFG;
        if ($this->is_standard()) {
            return false;
        }
        // Userstatuswwu is the standard subplugin and can not be uninstalled.
        if ($this->name == 'userstatuswwu') {
            return false;
        }
        // In case the subplugin is in use, subplugin can not be uninstalled.
        if (!empty(get_config('tool_deprovisionuser', 'deprovisionuser_subplugin'))) {
            $subplugin = get_config('tool_deprovisionuser', 'deprovisionuser_subplugin');
            if ($subplugin == $this->name) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks whether Subplugins have settings.php and adds them to the admin menu.
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $plugininfo = $this; // Also can be used inside settings.php.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig or !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $settings = new admin_settingpage($section, $this->name, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php')); // This may also set $settings to null.

        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }

    /**
     * Overrides function from the base class to define section name.
     * @return string
     */
    public function get_settings_section_name() {
        return 'deprovisionuser_userstatus' . $this->name;
    }
}