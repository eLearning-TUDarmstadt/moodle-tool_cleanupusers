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
 * Adds tool_cleanupusers link in admin tree
 *
 * @package    tool_cleanupusers
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Add own category for plugin's and subplugins' settings.
    $category = new admin_category('tool_cleanupusers', get_string('pluginname', 'tool_cleanupusers'));
    $ADMIN->add('users', $category);

    // Add entry for overview
    $ADMIN->add('tool_cleanupusers', new admin_externalpage('tool_cleanupusers_overview',
        get_string('overview', 'tool_cleanupusers'),
        new moodle_url("/admin/tool/cleanupusers/index.php")
    ));

    // General settings
    $settingpage = new admin_settingpage('tool_cleanupusers_settings', get_string('pluginsettingstitle', 'tool_cleanupusers'));

    // List choosable subplugins.
    $choices = [];
    $plugins = core_plugin_manager::instance()->get_plugins_of_type('userstatus');
    foreach ($plugins as $plugin) {
        $choices[$plugin->name] = $plugin->displayname;
    }
    $settingpage->add(new admin_setting_configselect('tool_cleanupusers/cleanupusers_subplugin',
            get_string('subplugin', 'tool_cleanupusers'),
            null,
            'userstatuswwu',
            $choices));


    $settingpage->add(new admin_setting_configduration('tool_cleanupusers/rollbackduration',
            get_string('config_delay_duration', 'tool_cleanupusers'),
            null,
            183 * 24 * 60 * 60)); // Dafault value is 180 days.

    $ADMIN->add('tool_cleanupusers', $settingpage);

    // Add entry for own settings.
    $ADMIN->add('tool_cleanupusers', new admin_externalpage('Manage never logged in',
        get_string('neverloggedin', 'tool_cleanupusers'),
        new moodle_url("/admin/tool/cleanupusers/neverloggedin.php")
    ));
    // Add entry for own settings.
    $ADMIN->add('tool_cleanupusers', new admin_externalpage('Manage to archive',
        get_string('toarchive', 'tool_cleanupusers'),
        new moodle_url("/admin/tool/cleanupusers/toarchive.php")
    ));
    // Add entry for own settings.
    $ADMIN->add('tool_cleanupusers', new admin_externalpage('Manage to delete',
        get_string('todelete', 'tool_cleanupusers'),
        new moodle_url("/admin/tool/cleanupusers/todelete.php")
    ));

    $ADMIN->add('tool_cleanupusers', new admin_category('tool_cleanupusers_subplugins', get_string('subplugins', 'tool_cleanupusers')));
    // Adds an entry for every sub-plugin with an settings.php.
    foreach (core_plugin_manager::instance()->get_plugins_of_type('userstatus') as $plugin) {
        global $CFG;
        $plugin->load_settings($ADMIN, 'tool_cleanupusers_subplugins', $hassiteconfig);
    }
}
