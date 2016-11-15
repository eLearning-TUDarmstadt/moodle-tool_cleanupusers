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
 * Web interface to Show Users that never logged in.
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(dirname(__FILE__).'/user_status_checker.php');

// Get URL parameters.

$PAGE->set_context(context_system::instance());
$context = context_system::instance();
// Check permissions.
require_login();
require_capability('moodle/site:config', $context);

admin_externalpage_setup('notloggedin');

$pagetitle = get_string('pluginname', 'tool_deprovisionuser');
$PAGE->set_title(get_string('pluginname', 'tool_deprovisionuser'));
$PAGE->set_heading(get_string('pluginname', 'tool_deprovisionuser'));
$PAGE->set_pagelayout('standard');

$renderer = $PAGE->get_renderer('tool_deprovisionuser');

$userstatuschecker = new user_status_checker();
$arrayneverloggedin = $userstatuschecker->get_never_logged_in();
$content = $renderer->render_never_logged_in_page($arrayneverloggedin);

echo $content;
