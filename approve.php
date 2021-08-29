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
 * Page to manage specific queue
 *
 * @package    tool_cleanupusers
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_cleanupusers\local\manager\approvalmanager;
use tool_cleanupusers\local\table\approvetable;
use tool_cleanupusers\transaction;
use tool_cleanupusers\useraction;

require_once(__DIR__ . '/../../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir.'/adminlib.php');

$useraction = required_param('useraction', PARAM_INT);
if (!in_array($useraction, useraction::actions)) {
    throw new coding_exception('queue param invalid');
}
$approved = optional_param('approved', false, PARAM_BOOL);

admin_externalpage_setup('tool_cleanupusers_overview');
$PAGE->set_url(new moodle_url('/admin/tool/cleanupusers/approve.php', ['useraction' => $useraction, 'approved' => $approved]));

$a = optional_param('a', null, PARAM_INT);
if ($a && in_array($a, transaction::actions)) {
    require_sesskey();

    $users = null;
    $allusers = optional_param('all', false, PARAM_BOOL);
    if (!$allusers) {
        $users = required_param_array('ids', PARAM_INT);
    }

    approvalmanager::handle_transaction($a, $useraction, $users);

    redirect($PAGE->url);
}

$PAGE->set_heading(get_string('pluginname', 'tool_cleanupusers'));
$pagetitle = get_string($approved ? 'usersapprovedfor' : 'usersneedingapprovalfor', 'tool_cleanupusers',
        useraction::get_string_for_action_noun($useraction));

$PAGE->set_title($pagetitle);
$PAGE->navbar->add($pagetitle);

$table = new approvetable($useraction, $approved);
$table->define_baseurl($PAGE->url);

/** @var tool_cleanupusers_renderer $renderer */
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

$table->out(96, true);

echo $OUTPUT->footer();
