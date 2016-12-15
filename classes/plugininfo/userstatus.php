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

use core\plugininfo\base;

defined('MOODLE_INTERNAL') || die();

class userstatus extends base {
    /**
     * Function determines whether uninstalling is allowed.
     * By now returns false for a standard plugin
     *
     * @todo Extra checks for enabled plugins etc.
     * @return bool A status indicating permission or denial
     */
    public function is_uninstall_allowed() {
        if ($this->is_standard()) {
            return false;
        }
        if ($this->get_all_plugins()) {
            return true;
        }
        return false;
    }
    public function get_all_plugins(){
        global $CFG;
        $dir = $CFG->dirroot .'/admin/tool/deprovisionuser/userstatus';

        if ($this->is_dir_empty($dir) == 1) {
            return false;
        } elseif ($this->is_dir_empty($dir) > 1) {
            return true;
        }
        if ($this->is_dir_empty($dir) == true) {
            return parent::get_enabled_plugins();
        }
        return parent::get_enabled_plugins();
    }
    private function is_dir_empty($dir) {
        if (!is_readable($dir)) return null;
        $handle = opendir($dir);
        $numberofplugins = 0;
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $numberofplugins++;
            }
        }
        if($numberofplugins == 0) {
            return true;
        } else {
            return $numberofplugins;
        }
    }
}