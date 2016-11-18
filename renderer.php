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
    public function render_user_archive($myarray) {
        global $OUTPUT;
        $output = '';
        // TODO find a better way to set header and footer
        $output .= $this->header();
        $output .= $this->heading(get_string('archiveuser', 'tool_deprovisionuser'));
        $output .= html_writer::div(get_string('plugininfo', 'tool_deprovisionuser'));
        $output .= html_writer::div(get_string('inprogress', 'tool_deprovisionuser'));
        $tablearchivedusers = $this->render_table_of_users($myarray, array(get_string('oldusers', 'tool_deprovisionuser'),
            get_string('lastaccess', 'tool_deprovisionuser'),
            get_string('Archived', 'tool_deprovisionuser'), get_string('Willbe', 'tool_deprovisionuser')));
        $output .= html_writer::table($tablearchivedusers);
        $href = new moodle_url('/admin/tool/deprovisionuser/archiveuser.php');
        // TODO supposed to archive all users manually that are supposed to be archived
        $output .= $OUTPUT->single_button($href, "notworking", 'post' );
        return $output;
    }

    public function render_never_logged_in_page($usersneverloggedin) {
        $output = '';
        $tableneverloggedin = $this->render_table_of_users($usersneverloggedin, array(get_string('Neverloggedin', 'tool_deprovisionuser')));
        $output .= html_writer::table($tableneverloggedin);
        $output .= $this->footer();

        return $output;

    }
    public function render_to_delete_page($arraytodelete) {
        $output = '';
        $tabletodelete = $this->render_table_of_users($arraytodelete, array(get_string('titletodelete', 'tool_deprovisionuser'),
            get_string('lastaccess', 'tool_deprovisionuser'),
            get_string('Archived', 'tool_deprovisionuser'), get_string('Willbe', 'tool_deprovisionuser')));
        $output .= html_writer::table($tabletodelete);
        return $output;
    }
    /**
     * Renders a table of all users
     * TODO Two different tables for archived users and user to delete
     * @return string html
     */
    private function render_table_of_users($arrayofusers, $arrayoftableheadings) {
        $table = new html_table();
        $table->head = $arrayoftableheadings;
        $table->attributes['class'] = 'admintable deprovisionuser generaltable';
        $table->data = array();
        foreach ($arrayofusers as $key => $user) {
            $table->data[$key] = $user;
        }
        return $table;
    }


}