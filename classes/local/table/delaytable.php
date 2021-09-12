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
use tool_cleanupusers\useraction;

defined('MOODLE_INTERNAL') || die();

class delaytable extends \table_sql {

    private $strings;

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    public function __construct() {
        parent::__construct('tool_cleanupusers-approvetable');

        global $PAGE, $CFG;
        $PAGE->requires->js_call_amd('tool_cleanupusers/checktable', 'init');

        // Define the list of columns to show.
        $columns = array('select', 'username', 'fullname', 'lastaccess', 'action', 'delayuntil', 'tools');
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = [
                \html_writer::checkbox('tool_cleanupusers-checkall', '', false),
                // get_string('id', 'tool_cleanupusers' ),
                get_string('username'),
                get_string('fullname'),
                get_string('lastaccess', 'tool_cleanupusers'),
                get_string('action', 'tool_cleanupusers'),
                get_string('delayeduntil', 'tool_cleanupusers'),
                get_string('tools', 'tool_cleanupusers')
        ];
        $this->define_headers($headers);
        $this->column_nosort = ['select', 'tools'];

        if ($CFG->branch >= 311) {
            $userfieldsapi = \core_user\fields::for_name();
            $ufields = $userfieldsapi->get_sql('u', false, '', $this->useridfield, false)->selects;
        } else {
            $ufields = get_all_user_name_fields(true, 'u');
        }

        $this->set_sql('a.id, a.userid, a.action, a.delayuntil, u.username, u.lastaccess, ' . $ufields,
                '{tool_cleanupusers_delay} a ' .
                'LEFT JOIN {user} u ON u.id = a.userid',
                'true',
                []);

        $this->strings = [
            'rollback' => get_string('rollback', 'tool_cleanupusers'),
            'neverloggedin' => get_string('neverlogged', 'tool_cleanupusers'),
            'infinite' => get_string('indefinitely', 'tool_cleanupusers'),
            'delete' => get_string('delete'),
            'timeformat' => get_string('strftimedatetimeshort', 'langconfig'),
            'everything' => get_string('allactions', 'tool_cleanupusers'),
            'deletion' => useraction::get_string_for_action_verb(useraction::DELETE),
            'suspend' => useraction::get_string_for_action_verb(useraction::SUSPEND),
            'reactivate' => useraction::get_string_for_action_verb(useraction::REACTIVATE),
        ];
    }

    public function col_tools($col) {
        global $OUTPUT;
        $actionmenu = new \action_menu();
        $actionmenu->add_primary_action(
                new \action_menu_link_primary(
                        new \moodle_url($this->baseurl, [
                                'sesskey' => sesskey(),
                                'ids[]' => $col->id,
                                'a' => 1
                        ]),
                        new \pix_icon('e/cancel', $this->strings['delete']),
                        $this->strings['delete']
                )
        );
        return $OUTPUT->render_action_menu($actionmenu);
    }

    public function col_select($col) {
        return \html_writer::checkbox('tool_cleanupusers-check', $col->id, false);
    }

    public function col_lastaccess($col) {
        if ($col->lastaccess == 0) {
            return $this->strings['neverloggedin'];
        } else {
            return userdate($col->lastaccess, $this->strings['timeformat']);
        }
    }

    public function col_action($col) {
        switch($col->action) {
            case 0:
                return $this->strings['everything'];
            case useraction::DELETE:
                return $this->strings['deletion'];
            case useraction::SUSPEND:
                return $this->strings['suspend'];
            case useraction::REACTIVATE:
                return $this->strings['reactivate'];
        }
        return 'Non supported value!';
    }

    public function col_delayuntil($col) {
        if ($col->delayuntil == null) {
            return $this->strings['infinite'];
        } else {
            return userdate($col->delayuntil, $this->strings['timeformat']);
        }
    }

    public function wrap_html_finish() {
        global $OUTPUT;
        parent::wrap_html_finish();
        foreach ([false, true] as $forall) {
            echo "<br>";
            $actionmenu = new \action_menu();
            $actionmenu->add_secondary_action(
                    new \action_menu_link_secondary(
                            new \moodle_url(''),
                            new \pix_icon('e/cancel', $this->strings['delete']),
                            $this->strings['delete'],
                            ['data-cleanupusers-action' => 1, 'data-cleanupusers-forall' => $forall]
                    )
            );
            $actionmenu->set_menu_trigger($forall ? get_string('forall', 'tool_cleanupusers') :
                    get_string('forselected', 'tool_cleanupusers'));
            echo $OUTPUT->render_action_menu($actionmenu);
        }
    }
}
