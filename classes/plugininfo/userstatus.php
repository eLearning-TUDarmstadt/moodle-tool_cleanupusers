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
     * @todo Return false when there is only one plugin
     * @return bool A status indicating success or failure
     */
    public function is_uninstall_allowed() {
        if ($this->is_standard()) {
            return false;
        }
        return true;
    }
}