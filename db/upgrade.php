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
 * Upgrade for the tool_cleanupusers.
 *
 * @package tool_cleanupusers
 * @copyright 2016/17 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to upgrade for the tool_cleanupusers.
 */

function xmldb_tool_cleanupusers_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2018021401) {

        // Define field moodlenetprofile to be added to tool_cleanupusers_archive.
        $table = new xmldb_table('tool_cleanupusers_archive');
        $field = new xmldb_field('moodlenetprofile', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'alternatename');

        // Conditionally launch add field moodlenetprofile.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cleanupusers savepoint reached.
        upgrade_plugin_savepoint(true, 2018021401, 'tool', 'cleanupusers');
    }

    if ($oldversion < 2021082400) {

        // Define table tool_cleanupusers_approve to be created.
        $table = new xmldb_table('tool_cleanupusers_approve');

        // Adding fields to table tool_cleanupusers_approve.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('approved', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table tool_cleanupusers_approve.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('key_u_userid', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);

        // Conditionally launch create table for tool_cleanupusers_approve.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_cleanupusers_delay to be created.
        $table = new xmldb_table('tool_cleanupusers_delay');

        // Adding fields to table tool_cleanupusers_delay.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('delayuntil', XMLDB_TYPE_INTEGER, '11', null, null, null, null);

        // Adding keys to table tool_cleanupusers_delay.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('key_f_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('key_u_user-action', XMLDB_KEY_UNIQUE, ['userid', 'action']);

        // Conditionally launch create table for tool_cleanupusers_delay.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Cleanupusers savepoint reached.
        upgrade_plugin_savepoint(true, 2021082400, 'tool', 'cleanupusers');
    }

    return true;
}
