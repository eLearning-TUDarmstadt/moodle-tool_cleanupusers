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
 * Checks the status of the Users
 * TODO later calls for subplugins to let them check the status
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Class that checks the status of different users
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_status_checker {

    public function get_users_for_suspending() {
        $arrayofuser = $this->get_all_users();
        $arrayofoldusers = array();
        foreach ($arrayofuser as $key => $user) {
            // Merley users who are not deleted and not suspended are shown.
            // TODO Show Admin or not?
            // LastAccess checks for lastlogin although $user has an extra attribute lastlogin which points at the second last login.
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $arrayofoldusers[$key] = $this->relevant_information($user, 'toarchive');
            }
        }
        return $arrayofoldusers;
    }
    private function get_all_users() {
        global $DB;
        // TODO for Performance reasons only get neccessary record
        return $DB->get_records('user');
    }
    /**
     * Methode to get users to suspend for cron.
     *
     * @return array
     */
    public function get_to_suspend_for_cron() {
        $arrayofuser = $this->get_all_users();
        foreach ($arrayofuser as $key => $user) {
            if ($user->deleted == 0 && $user->lastaccess != 0 && !is_siteadmin($user)) {
                $mytimestamp = time();
                $timenotloggedin = $mytimestamp - $user->lastaccess;
                if ($this->check_suspend($user->id, $timenotloggedin)) {
                    $arrayofoldusers[$user->id] = $user;
                }
            }
        }
        return $arrayofoldusers;
    }

    /**
     * Methode to return archived true or false, later checks for subplugins.
     *
     * @param $id
     * @param $time
     * @return bool
     */
    private function check_suspend($suspend, $timenotloggedin) {
        if ($suspend == 1) {
            if($timenotloggedin < 31536000) {
                $additionaltime = 31536000 - $timenotloggedin;
                $mytimestamp = time();
                $deletedinunixtime = $mytimestamp + $additionaltime;
                $deletedinrealtime = date('d.m.Y h:i:s', $deletedinunixtime);
                return get_string('deletedin', 'tool_deprovisionuser', $deletedinrealtime);
            } else {
                return get_string('shouldbedelted', 'tool_deprovisionuser');
            }
        }
        if ($suspend == 0) {
            if ($timenotloggedin > 8035200) {
                return get_string('willbe_archived', 'tool_deprovisionuser');
            } else {
                return get_string('willbe_notchanged', 'tool_deprovisionuser');
            }
        }
    }

    private function relevant_information($user, $intention) {
        global $DB, $OUTPUT, $CFG;
        $mytimestamp = time();
        $arrayofusers = array();
        if (!empty($user)) {
            // Minutes a user was not logged in.
            $timenotloggedin = $mytimestamp - $user->lastaccess;

            $arrayofusers['username'] = $user->username;
            $arrayofusers['lastaccess'] = date('d.m.Y h:i:s', $user->lastaccess);
            $isarchivid = $DB->get_records('tool_deprovisionuser', array('id' => $user->id, 'archived' => 1));
            // double checks for archived table Maybe removed later?

            if (empty($isarchivid)) {
                $arrayofusers['archived'] = get_string('No', 'tool_deprovisionuser');
            } else {
                $arrayofusers['archived'] = get_string('Yes', 'tool_deprovisionuser');
            }

            $arrayofusers['Willbe'] = $this->check_suspend($user->suspended, $timenotloggedin);

            // Link to Picture is rendered to suspend users if neccessary.
            // TODO better put in other function?
            if ($intention == 'toarchive') {
                if ($user->suspended == 0) {
                    $arrayofusers['link'] = html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
                        '/tool/deprovisionuser/archiveuser.php?userid=' . $user->id . '&archived=' . $user->suspended,
                        html_writer::img($OUTPUT->pix_url('t/hide'), get_string('hideuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $user->id)));
                } else {
                    $arrayofusers['link'] = html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
                        '/tool/deprovisionuser/archiveuser.php?userid=' . $user->id . '&archived=' . $user->suspended,
                        html_writer::img($OUTPUT->pix_url('t/show'), get_string('showuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $user->id)));
                }
            }
            if ($intention == 'todelete' || $intention == 'neverloggedin') {
                $arrayofusers['link'] = html_writer::link($CFG->wwwroot . '/' . $CFG->admin .
                    '/tool/deprovisionuser/deleteuser.php?userid=' . $user->id . '&deleted=' . $user->deleted,
                    html_writer::img($OUTPUT->pix_url('t/delete'), get_string('showuser', 'tool_deprovisionuser'), array('class' => "imggroup-" . $user->id)));
            }
        }
        return $arrayofusers;
    }

    public function get_never_logged_in() {
        global $DB;
        $arrayofuser = $this->get_all_users();
        $arrayofoldusers = array();
        foreach ($arrayofuser as $key => $user) {
            if (empty($user->lastaccess) && $user->deleted == 0) {
                $fulluser = $DB->get_record('user', array('id' => $user->id));
                $arrayofoldusers[$key] = $this->relevant_information($fulluser, 'neverloggedin');
            }
        }
        return $arrayofoldusers;
    }
    public function get_to_delete() {
        global $DB;
        $arrayofarchivedusers = $DB->get_records('tool_deprovisionuser');
        $relevantarrayofusers = array();
        foreach ($arrayofarchivedusers as $key => $user) {
            $fulluser = $DB->get_record('user', array('id' => $user->id));
            $relevantarrayofusers[$key] = $this->relevant_information($fulluser, 'todelete');
        }
        return $relevantarrayofusers;
    }
}
