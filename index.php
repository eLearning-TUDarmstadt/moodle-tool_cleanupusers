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
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_cleanupusers_overview');

$pagetitle = get_string('pluginname', 'tool_cleanupusers');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle, 1);

// Assures right sub-plugin is used.
$config = get_config('tool_cleanupusers', 'cleanupusers_subplugin');
if (!$config) {
    $config = 'userstatuswwu';
}

// Informs the user about the currently used plugin.
echo html_writer::tag('p', get_string('using-plugin', 'tool_cleanupusers', $config));

echo '<br>';
echo $OUTPUT->heading(get_string('approvingusers', 'tool_cleanupusers'), 3);
$renderer = $PAGE->get_renderer('tool_cleanupusers');
$renderer->print_approve_overview();

echo '<br>';
echo $OUTPUT->heading(get_string('blockedusers', 'tool_cleanupusers'), 3);
echo html_writer::link(new moodle_url('/admin/tool/cleanupusers/delays.php'),
        get_string('manageblockedusers', 'tool_cleanupusers'));
echo $renderer->footer();
