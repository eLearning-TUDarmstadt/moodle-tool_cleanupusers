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

    public function render_index_page($userstoarchive, $usertodelete, $usersneverloggedin) {
        global $OUTPUT;
        if (empty($usertodelete)) {
            $rendertodelete = array();
        } else {
            foreach ($usertodelete as $key => $user) {
                $rendertodelete[$key] = $this->information_user_delete($user);
            }
        }
        if (empty($usersneverloggedin)) {
            $renderneverloggedin = array();
        } else {
            foreach ($usersneverloggedin as $key => $user) {
                $renderneverloggedin[$key] = $this->information_user_notloggedind($user);
            }
        }
        if (empty($userstoarchive)) {
            $rendertoarchive = array();
        } else {
            foreach ($userstoarchive as $key => $user) {
                $rendertoarchive[$key] = $this->information_user_suspend($user);

            }
        }

        $output = '';
        $output .= $this->header();
        $output .= $this->heading(get_string('plugintitel', 'tool_deprovisionuser'));
        $output .= html_writer::div(get_string('plugininfo', 'tool_deprovisionuser'));
        $output .= $this->render_table_of_users($rendertoarchive, array(get_string('oldusers', 'tool_deprovisionuser'),
            get_string('lastaccess', 'tool_deprovisionuser'),
            get_string('Archived', 'tool_deprovisionuser'), get_string('Willbe', 'tool_deprovisionuser')));
        $output .= $this->render_table_of_users($renderneverloggedin, array(get_string('Neverloggedin', 'tool_deprovisionuser'),
            get_string('lastaccess', 'tool_deprovisionuser'), get_string('Archived', 'tool_deprovisionuser'),
            get_string('Willbe', 'tool_deprovisionuser')));
        $output .= $this->render_table_of_users($rendertodelete, array(get_string('titletodelete', 'tool_deprovisionuser'),
            get_string('lastaccess', 'tool_deprovisionuser'),
            get_string('Archived', 'tool_deprovisionuser'), get_string('Willbe', 'tool_deprovisionuser')));

        $output .= $this->footer();
        return $output;
    }

    /**
     * Renders a table of all users
     * @param $arrayofusers
     * @param $arrayoftableheadings
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
        $htmltable = html_writer::table($table);
        return $htmltable;
    }

    private function information_user_delete($user) {
        global $DB, $OUTPUT, $CFG;
        $arrayofusers = array();
        if (!empty($user)) {
            $arrayofusers['username'] = $user->username;
            $arrayofusers['lastaccess'] = date('d.m.Y h:i:s', $user->lastaccess);

            $isarchivid = $DB->get_records('tool_deprovisionuser', array('id' => $user->id, 'archived' => 1));
            if (empty($isarchivid)) {
                $arrayofusers['archived'] = get_string('No', 'tool_deprovisionuser');
            } else {
                $arrayofusers['archived'] = get_string('Yes', 'tool_deprovisionuser');
            }
            $arrayofusers['Willbe'] = get_string('shouldbedelted', 'tool_deprovisionuser');
;

            $arrayofusers['link'] = \html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
                '/tool/deprovisionuser/deleteuser.php?userid=' . $user->id . '&deleted=' . $user->deleted,
                \html_writer::img($OUTPUT->pix_url('t/delete'), get_string('showuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $user->id)));
        }
        return $arrayofusers;
    }

    private function information_user_suspend($user) {
        global $DB, $OUTPUT, $CFG;
        $arrayofusers = array();
        if (!empty($user)) {
            $arrayofusers['username'] = $user->username;
            $arrayofusers['lastaccess'] = date('d.m.Y h:i:s', $user->lastaccess);

            $isarchivid = $DB->get_records('tool_deprovisionuser', array('id' => $user->id, 'archived' => 1));
            if (empty($isarchivid)) {
                $arrayofusers['archived'] = get_string('No', 'tool_deprovisionuser');
            } else {
                $arrayofusers['archived'] = get_string('Yes', 'tool_deprovisionuser');
            }
            $arrayofusers['Willbe'] = get_string('willbe_archived', 'tool_deprovisionuser');

            if ($user->suspended == 0) {
                $arrayofusers['link'] = \html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
                    '/tool/deprovisionuser/archiveuser.php?userid=' . $user->id . '&archived=' . $user->suspended,
                    \html_writer::img($OUTPUT->pix_url('t/hide'), get_string('hideuser', 'tool_deprovisionuser'),
                        array('class' => "imggroup-" . $user->id)));
            } else {
                $arrayofusers['link'] = \html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
                    '/tool/deprovisionuser/archiveuser.php?userid=' . $user->id . '&archived=' . $user->suspended,
                    \html_writer::img($OUTPUT->pix_url('t/show'), get_string('showuser', 'tool_deprovisionuser'),
                        array('class' => "imggroup-" . $user->id)));
            }
        }
        return $arrayofusers;
    }

    private function information_user_notloggedind($user) {
        global $DB, $OUTPUT, $CFG;
        $arrayofusers = array();
        if (!empty($user)) {
            $arrayofusers['username'] = $user->username;
            $arrayofusers['lastaccess'] = get_string('neverlogged', 'tool_deprovisionuser');
            $isarchivid = $DB->get_records('tool_deprovisionuser', array('id' => $user->id, 'archived' => 1));
            if (empty($isarchivid)) {
                $arrayofusers['archived'] = get_string('No', 'tool_deprovisionuser');
            } else {
                $arrayofusers['archived'] = get_string('Yes', 'tool_deprovisionuser');
            }
            $arrayofusers['Willbe'] = get_string('nothinghappens', 'tool_deprovisionuser');
            $arrayofusers['link'] = \html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
                '/tool/deprovisionuser/deleteuser.php?userid=' . $user->id . '&deleted=' . $user->deleted,
                \html_writer::img($OUTPUT->pix_url('t/delete'), get_string('showuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $user->id)));
        }
        return $arrayofusers;
    }
}