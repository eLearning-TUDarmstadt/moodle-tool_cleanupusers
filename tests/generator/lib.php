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
 * Data Generator for the tool_deprovisionuser plugin.
 *
 * @package    tool_deprovisionuser
 * @category   test
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Data Generator class for the tool_deprovisionuser plugin.
 *
 * @package    tool_deprovisionuser
 * @category   test
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_deprovisionuser_generator extends testing_data_generator {
    /**
     * Creates User to test the tool_deprovisionuser plugin.
     */
    public function test_create_preparation () {
        global $DB;
        $generator = advanced_testcase::getDataGenerator();
        $data = array();

        $course = $generator->create_course(array('name' => 'Some course'));
        $data['course'] = $course;
        $mytimestamp = time();

        $user = $generator->create_user(array('username' => 'neutraluser', 'lastaccess' => $mytimestamp, 'suspended' => '0'));
        $data['user'] = $user;

        $listuser = $generator->create_user(array('username' => 'n_herr03', 'lastaccess' => $mytimestamp, 'suspended' => '0'));
        $data['listuser'] = $listuser;

        $suspendeduser = $generator->create_user(array('username' => 'suspendeduser', 'suspended' => '1'));
        $data['suspendeduser'] = $suspendeduser;

        $timestamponeyearago = $mytimestamp - 31622600;
        $notsuspendeduser = $generator->create_user(array('username' => 'notsuspendeduser', 'suspended' => '0',
            'lastaccess' => $timestamponeyearago));
        $data['notsuspendeduser'] = $notsuspendeduser;

        $suspendeduser2 = $generator->create_user(array('username' => 'suspendeduser2', 'suspended' => '1'));
        $data['suspendeduser2'] = $suspendeduser2;

        $deleteduser = $generator->create_user(array('username' => 'deleteduser', 'suspended' => '1',
            'lastaccess' => $timestamponeyearago));
        $data['deleteduser'] = $deleteduser;

        // User that was archived by the plugin and will be deleted in cronjob.
        $suspendeduser3 = $generator->create_user(array('username' => 'anonym', 'suspended' => '1', 'firstname' => 'Anonym'));
        $DB->insert_record_raw('tool_deprovisionuser', array('id' => $suspendeduser3->id, 'archived' => true,
            'timestamp' => $timestamponeyearago), true, false, true);
        $DB->insert_record_raw('deprovisionuser_archive', array('id' => $suspendeduser3->id,
            'username' => 'archivedbyplugin', 'suspended' => 1, 'lastaccess' => $timestamponeyearago),
            true, false, true);
        $data['archivedbyplugin'] = $suspendeduser3;

        $timestampshortago = $mytimestamp - 3456;
        // User that was archived by the plugin and will be reactivated in cronjob.
        $reactivatebyplugin = $generator->create_user(array('username' => 'anonym2', 'suspended' => '1', 'firstname' => 'Anonym'));
        $DB->insert_record_raw('tool_deprovisionuser', array('id' => $reactivatebyplugin->id, 'archived' => true,
            'timestamp' => $timestampshortago), true, false, true);
        $DB->insert_record_raw('deprovisionuser_archive', array('id' => $reactivatebyplugin->id,
            'username' => 'reactivatebyplugin',
            'suspended' => 1, 'lastaccess' => $mytimestamp), true, false, true);
        $data['reactivatebyplugin'] = $reactivatebyplugin;

        $adminuser = $generator->create_user(array('username' => 'adminuser', 'suspended' => '1'));
        $data['adminuser'] = $adminuser;

        return $data; // Return the user, course and group objects.
    }
}