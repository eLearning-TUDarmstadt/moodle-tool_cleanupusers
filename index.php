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
 * Web interface to cleanupusers.
 *
 * @package    tool_cleanupusers
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Get URL parameters.

$PAGE->set_context(context_system::instance());
$context = context_system::instance();
// Check permissions.
require_login();
require_capability('moodle/site:config', $context);

admin_externalpage_setup('cleanupusers');

$pagetitle = get_string('pluginname', 'tool_cleanupusers');
$PAGE->set_title(get_string('pluginname', 'tool_cleanupusers'));
$PAGE->set_heading(get_string('pluginname', 'tool_cleanupusers'));
$PAGE->set_pagelayout('standard');

$renderer = $PAGE->get_renderer('tool_cleanupusers');

$content = '';
echo $OUTPUT->header();
echo $renderer->get_heading();
$content = '';

$mform = new \tool_cleanupusers\subplugin_select_form();
if ($formdata = $mform->get_data()) {
    $arraydata = get_object_vars($formdata);
    if ($mform->is_validated()) {
        set_config('cleanupusers_subplugin', $arraydata['subplugin'], 'tool_cleanupusers');
        $content = 'You successfully submitted the subplugin.';
    }
}
$mform->display();

// Assures right sub-plugin is used.
$config = get_config('tool_cleanupusers', 'cleanupusers_subplugin');
if ($config) {
    $subplugin = $config;
    $mysubpluginname = "\\userstatus_" . $subplugin . "\\" . $subplugin;
    $userstatuschecker = new $mysubpluginname();
} else {
    $subplugin = 'userstatuswwu';
    $userstatuschecker = new \userstatus_userstatuswwu\userstatuswwu();
}

// Informs the user about the currently used plugin.
$content .= get_string('using-plugin', 'tool_cleanupusers', $subplugin);

// Request arrays from the sub-plugin.
$archivearray = $userstatuschecker->get_to_suspend();
$arraytodelete = $userstatuschecker->get_to_delete();
$arrayneverloggedin = $userstatuschecker->get_never_logged_in();

$content .= $renderer->render_index_page($archivearray, $arraytodelete, $arrayneverloggedin);

echo $content;
echo $OUTPUT->footer();
