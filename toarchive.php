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
 * Site to manage users who will be archived in the next cronjob.
 *
 * @package    tool_cleanupusers
 * @copyright  2018 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');

// Get URL parameters.

$PAGE->set_context(context_system::instance());
$context = context_system::instance();
// Check permissions.
require_login();
require_capability('moodle/site:config', $context);

admin_externalpage_setup('cleanupusers');

$pagetitle = get_string('toarchive', 'tool_cleanupusers');
$PAGE->set_title(get_string('toarchive', 'tool_cleanupusers'));
$PAGE->set_heading(get_string('toarchive', 'tool_cleanupusers'));
$PAGE->set_pagelayout('standard');
$PAGE->set_url(new moodle_url('/admin/tool/cleanupusers/toarchive.php'));

$renderer = $PAGE->get_renderer('tool_cleanupusers');

$content = '';
echo $OUTPUT->header();
echo $renderer->get_heading();

$config = get_config('tool_cleanupusers', 'cleanupusers_subplugin');
if ($config) {
    $subplugin = $config;
    $mysubpluginname = "\\userstatus_" . $subplugin . "\\" . $subplugin;
    $userstatuschecker = new $mysubpluginname();
} else {
    $subplugin = 'userstatuswwu';
    $userstatuschecker = new \userstatus_userstatuswwu\userstatuswwu();
}

// Request arrays from the sub-plugin.
$archivearray = $userstatuschecker->get_to_suspend();

if (empty($archivearray)) {
    echo "Currently no users will be suspended by the next cronjob";
} else {
    $userfilter = new user_filtering();
    $userfilter->display_add();
    $userfilter->display_active();
    list($sql, $param) = $userfilter->get_sql_filter();
    $archivetable = new \tool_cleanupusers\table\users_table('tool_cleanupusers_toarchive_table', $archivearray, $sql, $param);
    $archivetable->define_baseurl($PAGE->url);
    $archivetable->out(20, false);
}

echo $content;
echo $OUTPUT->footer();