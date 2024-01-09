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
    public function test_create_preparation() {
        global $DB;
        $generator = advanced_testcase::getDataGenerator();
        $data = [];

        $mytimestamp = time();

        // Timestamps are created to set the last access so we can test later the cronjob with the timechecker plugin.
        $tendaysago = $mytimestamp - 864000;
        $timestamponeyearago = $mytimestamp - 31622600;

        $user = $generator->create_user(['username' => 'user', 'lastaccess' => $tendaysago, 'suspended' => '0']);
        $user->realusername = $user->username;

        $userneverloggedin = $generator->create_user(['username' => 'userneverloggedin',
            'suspended' => '0']);
        $userneverloggedin->realusername = $userneverloggedin->username;

        $useroneyearnotloggedin = $generator->create_user(['username' => 'useroneyearnotloggedin',
            'lastaccess' => $timestamponeyearago, 'suspended' => '0']);
        $useroneyearnotloggedin->realusername = $userneverloggedin->username;

        $usersuspendedbypluginandmanually = $generator->create_user(['username' => 'anonym-x', 'suspended' => '1']);
        $usersuspendedbypluginandmanually->realusername = 'somerealusername';
        $DB->insert_record_raw('tool_cleanupusers', ['id' => $usersuspendedbypluginandmanually->id, 'archived' => 1,
            'timestamp' => $tendaysago], true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', ['id' => $usersuspendedbypluginandmanually->id,
            'username' => 'somerealusername', 'suspended' => $usersuspendedbypluginandmanually->suspended,
            'lastaccess' => $tendaysago], true, false, true);

        $usersuspendedmanually = $generator->create_user(['username' => 'usersuspendedmanually', 'suspended' => '1']);
        $usersuspendedmanually->realusername = $usersuspendedmanually->username;

        $userdeleted = $generator->create_user(['username' => 'userdeleted', 'suspended' => '1', 'deleted' => '1',
            'lastaccess' => $timestamponeyearago]);
        $userdeleted->realusername = $userdeleted->username;

        $usersuspendedbyplugin = $generator->create_user(['username' => 'anonym-y', 'suspended' => '1',
            'firstname' => 'Anonym']);
        $usersuspendedbyplugin->realusername = 'usersuspendedbyplugin';
        $DB->insert_record_raw('tool_cleanupusers', ['id' => $usersuspendedbyplugin->id, 'archived' => true,
            'timestamp' => $timestamponeyearago], true, false, true);
        $DB->insert_record_raw(
            'tool_cleanupusers_archive',
            ['id' => $usersuspendedbyplugin->id,
            'username' => 'usersuspendedbyplugin', 'suspended' => 0, 'lastaccess' => $timestamponeyearago],
            true,
            false,
            true
        );

        $userinconsistentsuspended = $generator->create_user(['username' => 'userinconsistentarchivedbyplugin',
            'suspended' => '1', 'firstname' => 'Anonym', 'lastaccess' => $timestamponeyearago]);
        $userinconsistentsuspended->realusername = $userinconsistentsuspended->username;
        $DB->insert_record_raw(
            'tool_cleanupusers_archive',
            ['id' => $userinconsistentsuspended->id,
            'username' => 'userinconsistentarchivedbyplugin', 'suspended' => 0, 'lastaccess' => $timestamponeyearago],
            true,
            false,
            true
        );

        $userduplicatedname = $generator->create_user(['username' => 'duplicatedname',
            'suspended' => '0', 'firstname' => 'Anonym']);
        $userduplicatedname->realusername = $userduplicatedname->username;

        $originaluser = $generator->create_user(['username' => 'anonym-z',
            'suspended' => '1', 'firstname' => 'Anonym']);
        $originaluser->realusername = $userduplicatedname->username;
        $DB->insert_record_raw(
            'tool_cleanupusers_archive',
            ['id' => $originaluser->id,
            'username' => $userduplicatedname->username, 'suspended' => 0, 'lastaccess' => $tendaysago],
            true,
            false,
            true
        );
        $DB->insert_record_raw('tool_cleanupusers', ['id' => $originaluser->id, 'archived' => true,
            'timestamp' => $tendaysago], true, false, true);

        $data['user'] = $user;  // Logged in recently, no action.
        $data['userdeleted'] = $userdeleted;    // Already deleted, filtered by cronjob.
        $data['originaluser'] = $originaluser;  // Cannot reactivate, username busy.
        $data['userneverloggedin'] = $userneverloggedin;    // Never logged in, no action.
        $data['userduplicatedname'] = $userduplicatedname;  // Never logged in, no action.
        $data['useroneyearnotloggedin'] = $useroneyearnotloggedin;  // Suspend.
        $data['usersuspendedmanually'] = $usersuspendedmanually;    // Not marked by timechecker?, no action.
        $data['usersuspendedbyplugin'] = $usersuspendedbyplugin;    // Delete.
        $data['userinconsistentsuspended'] = $userinconsistentsuspended;    // Cannot suspend, suspended = 1 already.
        $data['usersuspendedbypluginandmanually'] = $usersuspendedbypluginandmanually;  // Reactivate.

        return $data; // Return the user, course and group objects.
    }
}
