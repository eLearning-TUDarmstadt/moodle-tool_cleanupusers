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
 * Step definition for cleanupusers
 *
 * @package    tool_cleanupusers
 * @copyright  2021 Justus Dieckmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Step definition for cleanupusers
 *
 * @package    tool_cleanupusers
 * @copyright  2021 Justus Dieckmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_cleanupusers extends behat_base {

    /**
     * Set the users returned by the testuserstatus for "" to ""
     *
     * @When /^I set the users returned by the testuserstatus for "([^"]*)" to "([^"]*)"$/
     *
     * @param string $action Identifier of the action
     * @param string $users Commaseperated list of usernames
     * @throws Exception
     */
    public function i_set_the_users_returned_by_the_testuserstatus_for_to(string $action, string $users) {
        $userids = [];
        foreach (explode(',', $users) as $username) {
            $username = trim($username);
            $userids[] = \core_user::get_user_by_username($username)->id;
        }
        switch (strtolower($action)) {
            case 'deletion':
            case 'delete':
                $setting = 'test_todelete';
                break;
            case 'reactivate':
            case 'reactivation':
                $setting = 'test_toreactivate';
                break;
            case 'suspend':
            case 'suspension':
                $setting = 'test_tosuspend';
                break;
            case 'neverloggedin':
                $setting = 'test_neverloggedin';
                break;
            default:
                throw new coding_exception('The specified action is not available.');
        }

        set_config($setting, implode(",", $userids), 'tool_cleanupusers');
    }

    /**
     * Click on something in row containing something
     *
     * @When /^I click on "([^"]*)" in the row containing "([^"]*)"$/
     *
     * @param string $text Text to click on
     * @param string $row Text in row
     * @throws Exception
     */
    public function i_click_on_in_the_row_containing(string $text, string $row) {
        $xpath = "//table/tbody/tr[contains(., '$row')]//*[contains(., '$text')]";
        $element = $this->find('xpath', $xpath);
        $element->click();
    }

    /**
     * Click on icon in row containing something
     *
     * @When /^I click on "([^"]*)" icon in the row containing "([^"]*)"$/
     *
     * @param string $icon Icon to click on
     * @param string $row Text in row
     * @throws Exception
     */
    public function i_click_on_icon_in_the_row_containing(string $icon, string $row) {
        $xpath = "//table/tbody/tr[contains(., '$row')]//*[contains(@title, '$icon')]";
        $element = $this->find('xpath', $xpath);
        $element->click();
    }

    /**
     * Click on something in row containing something
     *
     * @When /^I should see "([^"]*)" in the row containing "([^"]*)"$/
     *
     * @param string $text Text to click on
     * @param string $row Text in row
     * @throws Exception
     */
    public function i_should_see_in_the_row_containing(string $text, string $row) {
        $xpath = "//table/tbody/tr[contains(., '$row')]//*[contains(., '$text')]";
        $element = $this->find('xpath', $xpath);
    }
}
