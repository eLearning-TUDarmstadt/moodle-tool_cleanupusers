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
 * Data Generator for the tool_cleanupusers plugin.
 *
 * @package    tool_cleanupusers
 * @category   test
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Data Generator class for the tool_cleanupusers plugin.
 *
 * @package    tool_cleanupusers
 * @category   test
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_cleanupusers_generator extends testing_data_generator {

    /**
     * Creates User to test the tool_cleanupusers plugin.
     * Username                          |   signed in    | suspended manually | suspended by plugin | deleted
     * --------------------------------------------------------------------------------------------------------
     *  user                             | tendaysago    | no                 | no                  | no
     *  userdeleted                      | oneyearago    | no                 | yes                 | yes
     *  originaluser                     | oneyearago    | no                 | yes                 | no
     *  userneverloggedin                | -             | no                 | no                  | no
     *  userduplicatedname               | -             | no                 | no                  | no
     *  usersuspendedmanually            | -             | yes                | no                  | no
     *  useroneyearnotloggedin           | oneyearago    | no                 | no                  | no
     *  usersuspendedbyplugin            | oneyearago    | yes                | yes                 | no
     *  userinconsistentsuspended        | oneyearago    | no                 | partly              | no
     *  usersuspendedbypluginandmanually | tendaysago    | yes                | yes                 | no
     * @return array
     * @throws dml_exception
     */
    public function test_create_preparation () {
        global $DB;
        $generator = advanced_testcase::getDataGenerator();
        $data = array();

        $mytimestamp = time();

        // Timestamps are created to set the last access so we can test later the cronjob with the timechecker plugin.
        $tendaysago = $mytimestamp - 864000;
        $timestamponeyearago = $mytimestamp - 31622600;

        $user = $generator->create_user(array('username' => 'user', 'lastaccess' => $tendaysago, 'suspended' => '0'));
        $user->realusername = $user->username;
        $userneverloggedin = $generator->create_user(array('username' => 'userneverloggedin', 'lastaccess' => '',
            'suspended' => '0'));
        $userneverloggedin->realusername = $userneverloggedin->username;
        $useroneyearnotloggedin = $generator->create_user(array('username' => 'useroneyearnotloggedin',
            'lastaccess' => $timestamponeyearago, 'suspended' => '0'));
        $useroneyearnotloggedin->realusername = $userneverloggedin->username;
        $usersuspendedbypluginandmanually = $generator->create_user(array('username' => 'Anonym-x', 'suspended' => '1'));
        $usersuspendedbypluginandmanually->realusername = 'Somerealusername';
        $DB->insert_record_raw('tool_cleanupusers', array('id' => $usersuspendedbypluginandmanually->id, 'archived' => 1,
            'timestamp' => $tendaysago), true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', array('id' => $usersuspendedbypluginandmanually->id,
            'username' => 'Somerealusername', 'suspended' => $usersuspendedbypluginandmanually->suspended,
            'lastaccess' => $tendaysago), true, false, true);

        $usersuspendedmanually = $generator->create_user(array('username' => 'usersuspendedmanually', 'suspended' => '1'));
        $usersuspendedmanually->realusername = $usersuspendedmanually->username;

        $userdeleted = $generator->create_user(array('username' => 'userdeleted', 'suspended' => '1', 'deleted' => '1',
            'lastaccess' => $timestamponeyearago));
        $userdeleted->realusername = $userdeleted->username;

        $usersuspendedbyplugin = $generator->create_user(array('username' => 'Anonym-y', 'suspended' => '1',
            'firstname' => 'Anonym'));
        $usersuspendedbyplugin->realusername = 'usersuspendedbyplugin';
        $DB->insert_record_raw('tool_cleanupusers', array('id' => $usersuspendedbyplugin->id, 'archived' => true,
            'timestamp' => $timestamponeyearago), true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', array('id' => $usersuspendedbyplugin->id,
            'username' => 'usersuspendedbyplugin', 'suspended' => 0, 'lastaccess' => $timestamponeyearago),
            true, false, true);

        $userinconsistentsuspended = $generator->create_user(array('username' => 'userinconsistentarchivedbyplugin',
            'suspended' => '1', 'firstname' => 'Anonym', 'lastaccess' => $timestamponeyearago));
        $userinconsistentsuspended->realusername = $userinconsistentsuspended->username;
        $DB->insert_record_raw('tool_cleanupusers_archive', array('id' => $userinconsistentsuspended->id,
            'username' => 'userinconsistentarchivedbyplugin', 'suspended' => 0, 'lastaccess' => $timestamponeyearago),
            true, false, true);

        $userduplicatedname = $generator->create_user(array('username' => 'duplicatedname',
            'suspended' => '1', 'firstname' => 'Anonym'));
        $userduplicatedname->realusername = $userduplicatedname->username;
        $originaluser = $generator->create_user(array('username' => 'Anonym-z',
            'suspended' => '1', 'firstname' => 'Anonym'));
        $originaluser->realusername = $userduplicatedname->username;
        $DB->insert_record_raw('tool_cleanupusers_archive', array('id' => $originaluser->id,
            'username' => $userduplicatedname->username, 'suspended' => 0, 'lastaccess' => $timestamponeyearago),
            true, false, true);
        $DB->insert_record_raw('tool_cleanupusers', array('id' => $originaluser->id, 'archived' => true,
            'timestamp' => $tendaysago), true, false, true);

        $data['user'] = $user;
        $data['userdeleted'] = $userdeleted;
        $data['originaluser'] = $originaluser;
        $data['userneverloggedin'] = $userneverloggedin;
        $data['userduplicatedname'] = $userduplicatedname;
        $data['useroneyearnotloggedin'] = $useroneyearnotloggedin;
        $data['usersuspendedmanually'] = $usersuspendedmanually;
        $data['usersuspendedbyplugin'] = $usersuspendedbyplugin;
        $data['userinconsistentsuspended'] = $userinconsistentsuspended;
        $data['usersuspendedbypluginandmanually'] = $usersuspendedbypluginandmanually;

        return $data; // Return the user, course and group objects.
    }
}
