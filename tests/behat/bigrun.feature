@tool @tool_cleanupusers
Feature: Go through a whole approve scenario

  @javascript
  Scenario: Admin adds some blocked users, and the subplugin is set to return some users.
    It is checked that the correct users are put into approve queues.
    One user is approved to be deleted and the deletion is checked.

    Given the following "users" exist:
      | username | firstname | lastname |
      | u1       | User      | 1        |
      | u2       | User      | 2        |
      | u3       | User      | 3        |
      | u4       | User      | 4        |
      | u5       | User      | 5        |
      | u6       | User      | 6        |
      | u7       | User      | 7        |
      | u8       | User      | 8        |
      | u9       | User      | 9        |
      | u10      | User      | 10       |
      | u11      | User      | 11       |
      | u12      | User      | 12       |
      | u13      | User      | 13       |
    And I log in as "admin"
    And the following config values are set as admin:
      | cleanupusers_subplugin | test | tool_cleanupusers |
    And I navigate to "Users > Clean up users > Overview" in site administration
    And I click on "Manage blocked users" "link"
    And I click on "Block new users" "button"
    And I set the following fields to these values:
      | Input field     | radio       |
      | Blocked forever | 1           |
      | Users           | u10, u11    |
      | Action          | All actions |
    And I click on "Add" "button"
    And I click on "Block new users" "button"
    And I set the following fields to these values:
      | Input field     | radio       |
      | Blocked forever | 1           |
      | Users           | u4, u9, u12 |
      | Action          | Reactivate  |
    And I click on "Add" "button"
    And I click on "Block new users" "button"
    And I set the following fields to these values:
      | Input field     | radio   |
      | Blocked forever | 1       |
      | Users           | u5, u11 |
      | Action          | Suspend |
    And I click on "Add" "button"
    And I click on "Block new users" "button"
    And I set the following fields to these values:
      | Input field     | radio   |
      | Blocked forever | 1       |
      | Users           | u6, u12 |
      | Action          | Delete  |
    And I click on "Add" "button"
    And I set the users returned by the testuserstatus for "reactivation" to "u1, u4, u7, u9, u11"
    And I set the users returned by the testuserstatus for "suspension" to "u2, u5, u7, u8, u9, u12"
    And I set the users returned by the testuserstatus for "deletion" to "u3, u6, u8, u10, u13"
    And I run the scheduled task "\tool_cleanupusers\task\archive_user_task"
    And I navigate to "Users > Clean up users > Overview" in site administration
    And I click on "2 users needing approval" in the row containing "Delete"
    And I click on "Approve" icon in the row containing "u3"
    And I click on "Overview" "link"
    And I should see "1 approved users" in the row containing "Delete"
    And I should see "0 approved users" in the row containing "Suspend"
    And I should see "0 approved users" in the row containing "Reactivate"
    And I should see "1 users needing approval" in the row containing "Delete"
    And I click on "4 users needing approval" in the row containing "Suspend"
    And I should see "u2"
    And I should see "u8"
    And I should see "u9"
    And I should see "u12"
    And I click on "Overview" "link"
    And I click on "2 users needing approval" in the row containing "Reactivate"
    And I should see "u1"
    And I should see "u7"
    And I run the scheduled task "\tool_cleanupusers\task\archive_user_task"
    And I click on "Overview" "link"
    Then I should see "0 approved users" in the row containing "Delete"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "u13"
    And I should not see "u3"
