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
 * Renderer for the Web interface of deprovisionuser
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Class of the tool_deprovisionuser renderer.
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 Nina Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_deprovisionuser_renderer extends plugin_renderer_base {

    /**
     * Renders the index page
     * @return string html for the page
     */
    public function render_index_page($myarray, $usersneverloggedin) {
        global $OUTPUT,$DB;
        $output = '';
        $output .= $this->header();
        $output .= $this->heading(get_string('plugintitel','tool_deprovisionuser'));
        $output .= html_writer::div(get_string('plugininfo', 'tool_deprovisionuser'));
        $output .= html_writer::div(get_string('inprogress', 'tool_deprovisionuser'));
        $tablearchivedusers = $this->render_table_of_users($myarray);
        $output .= html_writer::table($tablearchivedusers);
        $tableneverloggedin = $this->render_table_not_logged_in($usersneverloggedin);
        $output .= html_writer::table($tableneverloggedin);
        $href = new moodle_url('/admin/tool/deprovisionuser/archiveuser.php');
        $output .= $OUTPUT->single_button($href, get_string("archive", 'tool_deprovisionuser'), 'post' );
        $output .= $this->footer();

        return $output;
    }

    /**
     * Renders a table of all users
     * TODO Two different tables for archived users and user to delete
     * @return string html
     */
    private function render_table_of_users($myarray){
        $table = new html_table();
        $table->head = array(get_string('oldusers','tool_deprovisionuser'), get_string('lastaccess','tool_deprovisionuser'),
            get_string('Archived','tool_deprovisionuser'), get_string('Willbe','tool_deprovisionuser'));
        $table->attributes['class'] = 'admintable deprovisionuser generaltable';
        $table->data = array();
        foreach($myarray as $key => $user) {
            $table->data[$key] = $user;
        }
        return $table;
    }
    private function render_table_not_logged_in($myarray){
        $table = new html_table();
        $table->head = array(get_string('Neverloggedin','tool_deprovisionuser'));
        $table->attributes['class'] = 'admintable deprovisionuser generaltable';
        $table->data = array();
        foreach($myarray as $key => $user) {
            $table->data[$key] = $user;
        }
        return $table;
    }
}