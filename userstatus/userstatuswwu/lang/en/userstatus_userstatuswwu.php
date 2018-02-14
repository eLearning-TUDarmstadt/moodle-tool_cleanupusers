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
 * This file contains language strings used in the userstatuswwu sub-plugin.
 *
 * @package tool_cleanupusers
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Userstatus WWU';
$string['headingintroduction'] = 'Subplugin information';
$string['introduction'] = 'Here you define a path to a file which assign usernames to usergroups.
The usernames have to be equal to the usernames in moodle. The groups are hardcoded and have to be changed in the code.
The plugin will not handle users who are deleted or suspended manually.';
$string['pathtotxt'] = 'Enter the path to the .txt file that contains information about users (e.g. groups.txt).
It assigns usernames to technical groups. Relevant groups cannot be configured here as they are hardcoded.';
$string['path'] = 'Path';
$string['zivlistnotfound'] = 'The reference to the .txt could not be found.';
$string['noconfig'] = 'The path to the .txt file has to be set.';
