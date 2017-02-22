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
 * Web interface to deprovisionuser
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Get URL parameters.

$PAGE->set_context(context_system::instance());
$context = context_system::instance();
// Check permissions.
require_login();
require_capability('moodle/site:config', $context);

admin_externalpage_setup('deprovisionuser');

$pagetitle = get_string('pluginname', 'tool_deprovisionuser');
$PAGE->set_title(get_string('pluginname', 'tool_deprovisionuser'));
$PAGE->set_heading(get_string('pluginname', 'tool_deprovisionuser'));
$PAGE->set_pagelayout('standard');

$renderer = $PAGE->get_renderer('tool_deprovisionuser');

$content = '';
echo $OUTPUT->header();
echo $renderer->get_heading();
$content = '';
$mform = new \tool_deprovisionuser\subplugin_select_form();
if ($fromform = $mform->get_data()) {
    set_config('deprovisionuser_subplugin', $fromform->subplugin, 'tool_deprovisionuser');
    $content = 'You successfully submitted the Subplugin.';
    $content .= $mform->display();
    // In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
    $content .= $mform->display();
}
if (!empty(get_config('tool_deprovisionuser', 'deprovisionuser_subplugin'))) {
    $subplugin = get_config('tool_deprovisionuser', 'deprovisionuser_subplugin');
    $mysubpluginname = "\\userstatus_" . $subplugin . "\\" . $subplugin;
    $userstatuschecker = new $mysubpluginname();
} else {
    $subplugin = 'userstatuswwu';
    $userstatuschecker = new \userstatus_userstatuswwu\userstatuswwu();
}
$content .= 'You are currently using the ' . $subplugin . ' Plugin';
$archivearray = $userstatuschecker->get_to_suspend();
$arraytodelete = $userstatuschecker->get_to_delete();
$arrayneverloggedin = $userstatuschecker->get_never_logged_in();

$content .= $renderer->render_index_page($archivearray, $arraytodelete, $arrayneverloggedin);
echo $content;
echo $OUTPUT->footer();
