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
 * sql_table to approve/rollback users for ttool_cleanupusers
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cleanupusers\local\table;
use tool_cleanupusers\transaction;
use tool_cleanupusers\useraction;

defined('MOODLE_INTERNAL') || die();

class approvetable extends \table_sql {

    private $strings;
    private $useraction;
    private $approved;

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    public function __construct(int $useraction, bool $approved = false) {
        parent::__construct('tool_cleanupusers-approvetable');
        $this->useraction = $useraction;
        $this->approved = $approved;

        global $PAGE;
        $PAGE->requires->js_call_amd('tool_cleanupusers/checktable', 'init');

        // Define the list of columns to show.
        $columns = array('select', 'username', 'fullname', 'lastaccess', 'tools');
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = [
                \html_writer::checkbox('tool_cleanupusers-checkall', '', false),
                // get_string('id', 'tool_cleanupusers' ),
                get_string('username'),
                get_string('fullname'),
                get_string('lastaccess', 'tool_cleanupusers'),
                get_string('tools', 'tool_cleanupusers')
        ];
        $this->define_headers($headers);
        $this->column_nosort = ['select', 'tools'];

        $userfieldsapi = \core_user\fields::for_name();
        $ufields = $userfieldsapi->get_sql('u', false, '', $this->useridfield, false)->selects;

        $this->set_sql('a.id, a.userid, u.username, u.lastaccess, ' . $ufields,
                '{tool_cleanupusers_approve} a ' .
                'LEFT JOIN {user} u ON u.id = a.userid',
                'a.action = :useraction AND a.approved = :approved',
                ['useraction' => $this->useraction, 'approved' => $this->approved]);

        $strdata = [
                'action' => useraction::get_string_for_action_noun($useraction),
                'delay' => userdate(time() + get_config('tool_cleanupusers', 'rollbackduration'),
                        get_string('strftimedatemonthabbr', 'core_langconfig')),
            ];

        $this->strings = [
            transaction::APPROVE => get_string('approve', 'tool_cleanupusers'),
            transaction::CANCEL_APPROVAL => get_string('cancel_approval', 'tool_cleanupusers'),
            transaction::DELAY_LOCAL => get_string('rollbacklocaluntil', 'tool_cleanupusers', $strdata),
            transaction::DELAY_LOCAL_INDEF => get_string('rollbacklocalindef', 'tool_cleanupusers', $strdata),
            transaction::DELAY_GLOBAL => get_string('rollbackglobaluntil', 'tool_cleanupusers', $strdata),
            transaction::DELAY_GLOBAL_INDEF => get_string('rollbackglobalindef', 'tool_cleanupusers'),
            'rollback' => get_string('rollback', 'tool_cleanupusers'),
            'neverloggedin' => get_string('neverlogged', 'tool_cleanupusers')
        ];
    }

    public function col_tools($col) {
        global $OUTPUT;
        echo '<br>';
        $actionmenu = new \action_menu();
        if ($this->approved) {
            $actionmenu->add_primary_action(
                    new \action_menu_link_primary(
                            new \moodle_url($this->baseurl, [
                                    'sesskey' => sesskey(),
                                    'ids[]' => $col->userid,
                                    'a' => transaction::CANCEL_APPROVAL
                            ]),
                            new \pix_icon('e/cancel', $this->strings[transaction::CANCEL_APPROVAL]),
                            $this->strings[transaction::CANCEL_APPROVAL]
                    )
            );
        } else {
            $actionmenu->add_primary_action(
                    new \action_menu_link_primary(
                            new \moodle_url($this->baseurl, [
                                    'sesskey' => sesskey(),
                                    'ids[]' => $col->userid,
                                    'a' => transaction::APPROVE
                            ]),
                            new \pix_icon('e/tick', $this->strings[transaction::APPROVE]),
                            $this->strings[transaction::APPROVE]
                    )
            );
            $secondaryactions = [transaction::DELAY_LOCAL, transaction::DELAY_GLOBAL,
                    transaction::DELAY_LOCAL_INDEF, transaction::DELAY_GLOBAL_INDEF];
            foreach ($secondaryactions as $transaction) {
                $actionmenu->add_secondary_action(
                        new \action_menu_link_secondary(
                                new \moodle_url($this->baseurl, [
                                        'sesskey' => sesskey(),
                                        'ids[]' => $col->userid,
                                        'a' => $transaction
                                ]),
                                new \pix_icon('e/undo', $this->strings[$transaction]),
                                $this->strings[$transaction]
                        )
                );
            }
            $actionmenu->set_menu_trigger($this->strings['rollback']);
        }
        return $OUTPUT->render_action_menu($actionmenu);
    }

    public function col_select($col) {
        return \html_writer::checkbox('tool_cleanupusers-check', $col->userid, false);
    }

    public function col_lastaccess($col) {
        if ($col->lastaccess == 0) {
            return $this->strings['neverloggedin'];
        } else {
            return userdate($col->lastaccess);
        }
    }

    public function wrap_html_finish() {
        global $OUTPUT;
        parent::wrap_html_finish();

        foreach ([false, true] as $forall) {
            echo "<br>";

            $actionmenu = new \action_menu();
            if ($this->approved) {
                $actionmenu->add_secondary_action(
                        new \action_menu_link_secondary(
                                new \moodle_url(''),
                                new \pix_icon('e/cancel', $this->strings[transaction::CANCEL_APPROVAL]),
                                $this->strings[transaction::CANCEL_APPROVAL],
                                ['data-cleanupusers-action' => transaction::CANCEL_APPROVAL, 'data-cleanupusers-forall' => $forall]
                        )
                );
            } else {
                $actionmenu->add_secondary_action(
                        new \action_menu_link_secondary(
                                new \moodle_url(''),
                                new \pix_icon('e/tick', $this->strings[transaction::APPROVE]),
                                $this->strings[transaction::APPROVE],
                                ['data-cleanupusers-action' => transaction::APPROVE, 'data-cleanupusers-forall' => $forall]
                        )
                );
                $secondaryactions = [transaction::DELAY_LOCAL, transaction::DELAY_GLOBAL,
                        transaction::DELAY_LOCAL_INDEF, transaction::DELAY_GLOBAL_INDEF];
                foreach ($secondaryactions as $transaction) {
                    $actionmenu->add_secondary_action(
                            new \action_menu_link_secondary(
                                    new \moodle_url(''),
                                    new \pix_icon('e/undo', $this->strings[$transaction]),
                                    $this->strings[$transaction],
                                    ['data-cleanupusers-action' => $transaction, 'data-cleanupusers-forall' => $forall]
                            )
                    );
                }
            }
            $actionmenu->set_menu_trigger($forall ? get_string('forall', 'tool_cleanupusers') :
                    get_string('forselected', 'tool_cleanupusers'));
            echo $OUTPUT->render_action_menu($actionmenu);
        }
    }
}