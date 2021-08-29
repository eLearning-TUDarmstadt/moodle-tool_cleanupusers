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
 * Page to add delays for users
 *
 * @package    tool_cleanupusers
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_cleanupusers\local\form\blockusersform;
use tool_cleanupusers\local\manager\delaymanager;

require_once(__DIR__ . '/../../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_cleanupusers_overview');
$PAGE->set_url(new moodle_url('/admin/tool/cleanupusers/adddelays.php'));
$previousurl = new moodle_url('/admin/tool/cleanupusers/delays.php');

$mform = new blockusersform();

if ($mform->is_cancelled()) {
    redirect($previousurl);
} else if (($data = $mform->get_data()) && $mform->is_validated()) {
    $delayuntil = null;
    if (empty($data->blockedforever) || !$data->blockedforever) {
        $delayuntil = $data->blockeduntil;
    }
    if ($data->selectusersvia == 1) {
        $ids = $data->users;
    } else {
        global $DB;
        preg_match_all('/[^\s,]+/', $mform->get_file_content('usersfile'), $matches);
        if (count($matches[0]) == 0) {
            redirect($previousurl, 'No users found in file.');
        }
        list($insql, $inparams) = $DB->get_in_or_equal($matches[0]);
        $ids = $DB->get_fieldset_select('user', 'id', 'username ' . $insql, $inparams);
    }

    $notinserted = delaymanager::create_delays($data->action, $delayuntil, $ids);
    $count = count($ids) - count($notinserted);

    $message = '';
    if (count($notinserted) > 0) {
        $message .= get_string('couldnotinsertuserids', 'tool_cleanupusers', join(', ', $notinserted)) . '<br>';
    }
    $message .= get_string('addedcountdelays', 'tool_cleanupusers', $count);
    redirect($previousurl, $message);
}

$PAGE->set_heading(get_string('pluginname', 'tool_cleanupusers'));
$pagetitle = get_string('blocknewusers', 'tool_cleanupusers');

$PAGE->set_title($pagetitle);
$PAGE->navbar->add($pagetitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

$mform->display();

echo $OUTPUT->footer();
