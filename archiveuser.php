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
 * File to archive users.
 *
 * @package tool_deprovision
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('../../../config.php');
require_login();

$PAGE->set_url('/admin/tool/deprovisionuser/index.php');
$PAGE->set_context(context_system::instance());

notice(get_string('usersarchived', 'tool_deprovisionuser'),
    $CFG->wwwroot . '/admin/tool/deprovisionuser/index.php');
exit();
/**
 * Class archiveusers to make users anonymous.
 *
 * @package tool_deprovision
 * @copyright 2016 N Herrmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class archiveuser {
    private $id;
    private $archived;

    public function make_archived_user($userid) {
        global $DB;
        $thisuser = new archiveuser();
        $DB->insert_record('tool_deprovisionuser_inactive', $thisuser);
    }
}
