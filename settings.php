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
 * Adds tool_deprovisionuser link in admin tree
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    $url = $CFG->wwwroot . '/' . $CFG->admin . '/tool/deprovisionuser/index.php';
    $ADMIN->add('users', new admin_externalpage(
        'deprovisionuser',
        get_string('plugintitel', 'tool_deprovisionuser'),
        "$CFG->wwwroot/$CFG->admin/tool/deprovisionuser/index.php"
    ));
    $ADMIN->add('users', new admin_category('subplugins', get_string('subpluginsof', 'tool_deprovisionuser')));
    foreach (core_plugin_manager::instance()->get_plugins_of_type('userstatus') as $plugin) {
        global $CFG;
        $plugin->load_settings($ADMIN, 'subplugins', $hassiteconfig);
    }
}
