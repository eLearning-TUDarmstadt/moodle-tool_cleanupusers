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
 * Renderer for the Web interface of tool_cleanupusers.
 *
 * @package    tool_cleanupusers
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/tablelib.php');
/**
 * Class of the tool_cleanupusers renderer.
 *
 * @package    tool_cleanupusers
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_cleanupusers_renderer extends plugin_renderer_base {

    /**
     * Function expects three arrays and renders them to three separate tables.
     *
     * @param array $userstosuspend
     * @param array $usertodelete
     * @param array $usersneverloggedin
     * @return string html
     */
    public function render_index_page($userstosuspend, $usertodelete, $usersneverloggedin) {
        global $DB;
        // Checks if one of the given arrays is empty to prevent rendering empty arrays.
        // If not empty renders the information needed.
        $cleanupusers = $DB->get_records('tool_cleanupusers', array('archived' => 1));

        if (empty($usertodelete)) {
            $rendertodelete = array();
        } else {
            $rendertodelete = $this->information_user_delete($usertodelete, $cleanupusers);
        }
        if (empty($usersneverloggedin)) {
            $renderneverloggedin = array();
        } else {
            $renderneverloggedin = $this->information_user_notloggedin($usersneverloggedin, $cleanupusers);
        }
        if (empty($userstosuspend)) {
            $rendertosuspend = array();
        } else {
            $rendertosuspend = $this->information_user_suspend($userstosuspend, $cleanupusers);
        }

        // Renders the information for each array in a separate html table.
        $output = '';
        if (!empty($renderneverloggedin)) {
            $output .= $this->render_table_of_users($renderneverloggedin, array(get_string('Neverloggedin', 'tool_cleanupusers'),
                get_string('lastaccess', 'tool_cleanupusers'), get_string('Archived', 'tool_cleanupusers'),
                get_string('Willbe', 'tool_cleanupusers')));
        }
        if (!empty($rendertosuspend)) {
            $output .= $this->render_table_of_users($rendertosuspend, array(get_string('oldusers', 'tool_cleanupusers'),
                get_string('lastaccess', 'tool_cleanupusers'),
                get_string('Archived', 'tool_cleanupusers'), get_string('Willbe', 'tool_cleanupusers')));
        }
        if (!empty($rendertodelete)) {
            $output .= $this->render_table_of_users($rendertodelete, array(get_string('titletodelete', 'tool_cleanupusers'),
                get_string('lastaccess', 'tool_cleanupusers'),
                get_string('Archived', 'tool_cleanupusers'), get_string('Willbe', 'tool_cleanupusers')));
        }

        return $output;
    }

    /**
     * Renders the table for users to suspend.
     * @param $userstosuspend
     * @return bool|string
     * @throws coding_exception
     */
    public function render_archive_page($userstosuspend) {
        if (empty($userstosuspend)) {
            return "Currently no users will be suspended by the next cronjob";
        } else {
            $table = new flexible_table('tool_cleanupuser_toarchive');
            $table->define_columns(array('oldusers', 'lastaccess', 'Archived', 'Willbe'));
            $table->define_headers(array(get_string('oldusers', 'tool_cleanupusers'),
                get_string('lastaccess', 'tool_cleanupusers'),
                get_string('Archived', 'tool_cleanupusers'), get_string('Willbe', 'tool_cleanupusers')));
            return $table->print_html();
        }
    }

    /**
     * Renders the table for users who never logged in.
     * @param $usersneverloggedin
     * @return bool|string
     * @throws coding_exception
     */
    public function render_neverloggedin_page($usersneverloggedin) {
        global $DB, $CFG;
        if (empty($usersneverloggedin)) {
            return "Currently no users never logged in by the next cronjob";
        } else {
            $idsasstring = '';
            foreach($usersneverloggedin as $user) {
                $idsasstring .= $user->id . ',';
            }
            $idsasstring = rtrim( $idsasstring , ',');
            $table = new table_sql('tool_deprovisionuser_neverloggedin');
            $table->define_columns(array('username', 'lastaccess', 'suspended'));
            $table->define_baseurl($CFG->wwwroot .'/'. $CFG->admin .'/tool/cleanupusers/neverloggedin.php');
            $table->define_headers(array(get_string('Neverloggedin', 'tool_cleanupusers'),
                get_string('lastaccess', 'tool_cleanupusers'), get_string('Archived', 'tool_cleanupusers')));
            $table->set_sql('username, lastaccess, suspended', $DB->get_prefix() . 'user', 'id in (' . $idsasstring . ')');
            $table->setup();
            $tableobject = $table->out(30, true);
            return $tableobject;
        }
    }
    /**
     * Functions returns the heading for the tool_cleanupusers.
     *
     * @return string
     */
    public function get_heading() {
        $output = '';
        $output .= $this->heading(get_string('pluginname', 'tool_cleanupusers'));
        return $output;
    }

    /**
     * Formats information for users that are identified by the sub-plugin for deletion.
     * @param array $users array of objects of the user std_class
     * @param array $cleanupusers all users that are currently archived by the plugin.
     * @return array
     */
    private function information_user_delete($users, $cleanupusers) {
        global $OUTPUT;
        $resultarray = array();
        foreach ($users as $key => $user) {
            $userinformation = array();

            if (!empty($user)) {
                $userinformation['username'] = $user->username;
                $userinformation['lastaccess'] = date('d.m.Y h:i:s', $user->lastaccess);

                $isarchivid = array_key_exists($user->id, $cleanupusers);
                if (empty($isarchivid)) {
                    $userinformation['archived'] = get_string('No', 'tool_cleanupusers');
                } else {
                    $userinformation['archived'] = get_string('Yes', 'tool_cleanupusers');
                }
                $userinformation['Willbe'] = get_string('shouldbedelted', 'tool_cleanupusers');
                $url = new moodle_url('/admin/tool/cleanupusers/handleuser.php', ['userid' => $user->id, 'action' => 'delete']);
                $userinformation['link'] = \html_writer::link($url,
                    $OUTPUT->pix_icon('t/delete', get_string('deleteuser', 'tool_cleanupusers'), 'moodle',
                        ['class' => "imggroup-" . $user->id]));
            }
            $resultarray[$key] = $userinformation;
        }
        return $resultarray;
    }

    /**
     * Safes relevant information for users that are identified by the sub-plugin for suspending.
     * @param array $users array of objects of the user std_class
     * @param array $cleanupusers all users that are currently archived by the plugin.
     * @return array
     */
    private function information_user_suspend($users, $cleanupusers) {
        global $OUTPUT;

        $result = array();
        foreach ($users as $key => $user) {
            $userinformation = array();
            if (!empty($user)) {
                $userinformation['username'] = $user->username;
                $userinformation['lastaccess'] = date('d.m.Y h:i:s', $user->lastaccess);

                $isarchivid = array_key_exists($user->id, $cleanupusers);
                if (empty($isarchivid)) {
                    $userinformation['archived'] = get_string('No', 'tool_cleanupusers');
                } else {
                    $userinformation['archived'] = get_string('Yes', 'tool_cleanupusers');
                }

                $userinformation['Willbe'] = get_string('willbe_archived', 'tool_cleanupusers');

                $url = new moodle_url('/admin/tool/cleanupusers/handleuser.php', ['userid' => $user->id, 'action' => 'suspend']);

                $userinformation['link'] = \html_writer::link($url,
                    $OUTPUT->pix_icon('t/hide', get_string('hideuser', 'tool_cleanupusers'), 'moodle',
                        ['class' => "imggroup-" . $user->id]));
            }
            $result[$key] = $userinformation;
        }
        return $result;
    }

    /**
     * Safes relevant information for users who never logged in.
     * @param array $users array of objects of the user std_class
     * @param array $cleanupusers all users that are currently archived by the plugin.
     * @return array userid as key for user information
     */
    private function information_user_notloggedin($users, $cleanupusers) {
        global $OUTPUT;
        $result = array();
        foreach ($users as $key => $user) {
            $userinformation = array();
            if (!empty($user)) {
                $userinformation['username'] = $user->username;
                $userinformation['lastaccess'] = get_string('neverlogged', 'tool_cleanupusers');
                $isarchivid = array_key_exists($user->id, $cleanupusers);
                if (empty($isarchivid)) {
                    $userinformation['archived'] = get_string('No', 'tool_cleanupusers');
                } else {
                    $userinformation['archived'] = get_string('Yes', 'tool_cleanupusers');
                }
                $userinformation['Willbe'] = get_string('nothinghappens', 'tool_cleanupusers');
                $url = new moodle_url('/admin/tool/cleanupusers/handleuser.php', ['userid' => $user->id, 'action' => 'delete']);
                $userinformation['link'] = \html_writer::link($url,
                    $OUTPUT->pix_icon('t/delete', get_string('deleteuser', 'tool_cleanupusers'), 'moodle',
                        ['class' => "imggroup-" . $user->id]));
            }
            $result[$key] = $userinformation;
        }
        return $result;
    }

    /**
     * Renders a html-table for an array of users.
     * @param array $users
     * @param array $tableheadings
     * @return string html-table
     */
    private function render_table_of_users($users, $tableheadings) {
        $table = new html_table();
        $table->head = $tableheadings;
        $table->attributes['class'] = 'generaltable admintable cleanupusers';
        $table->data = array();
        foreach ($users as $key => $user) {
            $table->data[$key] = $user;
        }
        $htmltable = html_writer::table($table);
        return $htmltable;
    }
}