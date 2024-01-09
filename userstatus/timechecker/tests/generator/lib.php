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
 * Data Generator for the userstatus_timechecker sub-plugin
 *
 * @package    userstatus_timechecker
 * @category   test
 * @copyright  2016/17 Nina Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class Data Generator for the userstatus_timechecker sub-plugin
 *
 * @package    userstatus_timechecker
 * @category   test
 * @copyright  2016/17 Nina Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userstatus_timechecker_generator extends testing_data_generator {
    /**
     * Creates users to test the sub-plugin.
     */
    public function test_create_preparation() {
        global $DB;
        $generator = advanced_testcase::getDataGenerator();
        $data = [];
        $mytimestamp = time();

        $user = $generator->create_user(['username' => 'neutraluser', 'lastaccess' => $mytimestamp]);
        $data['user'] = $user;

        $timestamponeyearago = $mytimestamp - 31536000;
        $userlongnotloggedin = $generator->create_user(['username' => 'userlongnotloggedin',
            'lastaccess' => $timestamponeyearago]);
        $data['useroneyearnotlogedin'] = $userlongnotloggedin;

        $timestampfifteendays = $mytimestamp - 1296000;
        $userfifteendays = $generator->create_user(['username' => 'userfifteendays', 'lastaccess' => $timestampfifteendays]);
        $data['userfifteendays'] = $userfifteendays;

        // User manually suspended.
        $oneyearnintydays = $mytimestamp - 39313000;
        $userarchived = $generator->create_user(['username' => 'userarchivedmanually', 'lastaccess' => $oneyearnintydays,
            'suspended' => 1]);
        $data['userarchivedoneyearnintydaysmanually'] = $userarchived;
        $userarchived2 = $generator->create_user(['username' => 'userarchivedautomatically', 'lastaccess' => $oneyearnintydays,
            'suspended' => 1]);
        $DB->insert_record_raw('tool_cleanupusers', ['id' => $userarchived2->id, 'archived' => true,
            'timestamp' => $oneyearnintydays], true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', ['id' => $userarchived2->id,
            'username' => 'userarchivedautomatically',
            'suspended' => 0, 'lastaccess' => $oneyearnintydays], true, false, true);
        $data['userarchivedoneyearnintydaysautomatically'] = $userarchived2;

        $neverloggedin = $generator->create_user(['username' => 'neverloggedin']);
        $data['neverloggedin'] = $neverloggedin;

        // User suspended by the plugin.
        $tendaysago = $mytimestamp - 864000;
        $reactivate = $generator->create_user(['username' => 'anonym', 'suspended' => 1]);
        $DB->insert_record_raw('tool_cleanupusers', ['id' => $reactivate->id, 'archived' => true,
            'timestamp' => $tendaysago], true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', ['id' => $reactivate->id, 'username' => 'reactivate',
            'suspended' => 1, 'lastaccess' => $tendaysago], true, false, true);
        $data['reactivate'] = $reactivate;

        return $data; // Return the user, course and group objects.
    }
}
