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
 * Page to manage delays
 *
 * @package    tool_cleanupusers
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_cleanupusers\local\manager\delaymanager;
use tool_cleanupusers\local\table\delaytable;

require_once(__DIR__ . '/../../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_cleanupusers_overview');
$PAGE->set_url(new moodle_url('/admin/tool/cleanupusers/delays.php'));

$a = optional_param('a', null, PARAM_INT);

if ($a == 1) {
    require_sesskey();
    global $DB;

    $users = null;
    $allusers = optional_param('all', false, PARAM_BOOL);
    if (!$allusers) {
        $users = required_param_array('ids', PARAM_INT);
    }

    delaymanager::delete_delays($users);

    redirect($PAGE->url);
}

$PAGE->set_heading(get_string('pluginname', 'tool_cleanupusers'));
$pagetitle = get_string('blockedusers', 'tool_cleanupusers');

$PAGE->set_title($pagetitle);
$PAGE->navbar->add($pagetitle);

$table = new delaytable();
$table->define_baseurl($PAGE->url);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

echo $OUTPUT->single_button(new moodle_url('adddelays.php'), get_string('blocknewusers', 'tool_cleanupusers'));
echo '<br><br>';

$table->out(96, true);

echo $OUTPUT->footer();
