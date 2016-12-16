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
 * Subplugin userstatuswwu.
 *
 * The Plugins of the type userstatus must return values whether users should be deleted archived or reactivated.
 * This Plugin will be used by the cron_job and manually bz the admin to determine the appropriate actions for users.
 *
 * @package   tool_deprovisionuser
 * @copyright 2016 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_deprovisionuser\plugininfo;

use admin_settingpage;
use core\plugininfo\base;

defined('MOODLE_INTERNAL') || die();

class userstatus extends base {

    /**
     * Returns true when subplugin can be deleted false when not
     *
     * By now returns false when only one plugin avaiulable otherwise all plugins can be uninstalled if they are not standard
     * @todo have different uninstall values for each plugin?
     * @return bool
     */
    public function is_uninstall_allowed() {
        global $CFG;
        if ($this->is_standard()) {
            return false;
        }
        $pluginmanager = \core_plugin_manager::instance();
        $type = $pluginmanager->get_plugins_of_type('userstatus');
        if (empty($type)) {
            return false;
        }

        if (count($type) == 1) {
            return false;
               } else if (count($type) > 1) {
            return true;
        }
        return false;
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
    public function get_settings_section_name() {
        return 'deprovisionuser_userstatus' . $this->name;
    }
}