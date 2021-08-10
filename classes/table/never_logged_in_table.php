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
 * Create an Custom sql_table for the tool_cleanupusers
 *
 * @package   tool_cleanupusers
 * @copyright 2018 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cleanupusers\table;
defined('MOODLE_INTERNAL') || die();

class never_logged_in_table extends \table_sql
{

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    public function __construct($users, $sqlwhere, $param) {
        parent::__construct('tool_cleanupusers_never_logged_in_table');
        // Define the list of columns to show.
        $columns = array('id', 'username', 'fullname', 'suspended', 'deleted');
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array(get_string('id', 'tool_cleanupusers'), get_string('Neverloggedin', 'tool_cleanupusers'),
            get_string('fullname'), get_string('Archived', 'tool_cleanupusers'), 'Archive');
        $this->define_headers($headers);

        $idsasstring = '';
        foreach ($users as $user) {
            $idsasstring .= $user->id . ',';
        }
        $idsasstring = rtrim($idsasstring, ',');
        $where = 'id IN (' . $idsasstring . ')';

        if ($sqlwhere != null && $sqlwhere != '') {
            $where .= ' AND ' . $sqlwhere;
        }

        $this->set_sql('id, username, lastaccess, suspended, ' . get_all_user_name_fields(true), '{user}', $where, $param);
    }

    /**
     * Renders the suspended column.
     *
     * @param $row
     * @return string
     * @throws \coding_exception
     */
    public function col_suspended($row) {
        return $row->suspended ? get_string('yes') : get_string('no');
    }

    /**
     * This function is called for each data row to allow processing of the
     * possible actions
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return link for reactivation or suspension
     */
    public function col_deleted($values) {
        global $OUTPUT;
        // If the data is being downloaded than we don't want to show HTML.
        if ($values->suspended == 0) {
            $url = new \moodle_url('/admin/tool/cleanupusers/handleuser.php', ['userid' => $values->id, 'action' => 'suspend']);

            return \html_writer::link($url,
                $OUTPUT->pix_icon('t/removecontact', get_string('hideuser', 'tool_cleanupusers'), 'moodle',
                    ['class' => "imggroup-" . $values->id]));
        } else {
            $url = new \moodle_url('/admin/tool/cleanupusers/handleuser.php', ['userid' => $values->id, 'action' => 'reactivate']);

            return \html_writer::link($url,
                $OUTPUT->pix_icon('t/reload', get_string('hideuser', 'tool_cleanupusers'), 'moodle',
                    ['class' => "imggroup-" . $values->id]));
        }
    }
}