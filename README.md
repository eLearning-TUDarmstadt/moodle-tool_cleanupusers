# moodle-tool_cleanupusers *(Alpha_candidate)* WIP!

[![Build Status](https://travis-ci.org/learnweb/moodle-tool_cleanupusers.svg?branch=master)](https://travis-ci.org/learnweb/moodle-tool_cleanupusers)


The **clean up users plugin** enables the automatic and manual suspension and deletion of users.

The plugin is written by [Jan Dageförde](https://github.com/Dagefoerde), [Tobias Reischmann](https://github.com/tobiasreischmann) and [Nina Herrmann](https://github.com/NinaHerrmann).

## Motivation
Hitherto users could be suspended and deleted manually in the `Home ► Site administration ► Users ► Accounts ► Browse list of users` menu.
However, every user must be handled individually which becomes confusing and time consuming with a rising number of users.
To handle user efficiently there is a need to determine rules which identify the suitable action for each user and handle the user accordingly.
Therefore, the plugin aims to automatically suspend and delete users to custom rules. 

## Installation

This plugin should go into `admin/tool/cleanupusers`. 
No supplementary settings are required in the **clean up users plugin**. 
Optionally the sub-plugin can be changed in `Home ► Site administration ► Users ► Deprovision of Users`. 
By default, the **userstatuswwu sub-plugin** is used. 
However, it is likely that the sub-plugin requires additional settings therefore please read the information for the [sub-plugins](#sub-plugins). 

## Manual Handling

Users can be changed manually by every person who has access to the admin menu.
Beforehand users where handled in the `Home ► Site administration ► Users ► Accounts ► Browse list of users` menu.

The plugin provides an extra page which can be found in the `Home ► Site administration ► Users ► Deprovision of Users` menu.

## Automatic Processing
A cronjob deletes, archives and reactivates users automatically. 
By default, the cronjob runs every day at 4am. 
The admin can change the revision of the cronjob in `Home ► Site administration ► Server ► Scheduled task`. 

After the cronjob ran successfully, the admin receives a notification e-mail about the number of archived and deleted 
users. In case problems occurred with single users, the number of users who could not be handled are listed. 
An example is when a sub-plugin tries to suspend/delete a admin user. 
Additionally, information about the cronjob is logged and can be seen in the `Home ► Site administration ► Reports ► Logs` menu.

## Suspend User

Moodle provides the following functionality when suspending a user:
- kill the user session
- mark the user in the `user` table as suspended
    - therefore, the user cannot sign in anymore
    
The plugin aims to make users **anonymous** that are suspended.

This includes:

- save necessary data in a shadow table to reactivate users when necessary. (name of the table: `tool_cleanupusers_archive`)
- hide all other references in the `user` table e.g. `username`, ` firstname` etc.
    - the `username` is set to *Anonym* with the `userid` appended
        - usernames must be unique therefore the id is appended.
    - `firstname` is set to *Anonym*
        - references in e.g. the forum activity have merely reference to a user called `Anonym`
    - replacing all other data in the `user` table with the appropriate null value
        - when viewing the page of the user he/she cannot be identified

## Delete User
Moodle provides a `delete_user()` function. However, when the user is processed after the function was executed, the user is no longer flagged as deleted.
In the plugin, firstly the username is hashed. In case the hashed value already exist the username and the hashed username are hashed again.

Afterwards the moodle `delete_user()` function is executed with the following functions:
- replaces the username with the e-mail address and a timestamp and replaces the email address 
with a random string of numbers and letters
    
    *now uses the hashed username therefore identification is only possible with the userid*
    
- flag the user in the `user` table as deleted
- calls all plugins with a `pre_user_delete()` function to execute the function
- all grades are deleted, backup is kept in `grade_grades_history` table
- all item tags are removed
- withdrawn user from:
    - all courses
    - all roles in all contexts
- removes user from
    - all cohort
    - all groups
- moves all unread messages to read
- purges log of previous password hashes
- removes all user tokens
- prohibits the user for all services
- forces the user logout - may fail if file based sessions used
- triggers event `\core\event\user_deleted`
- notifies all [`auth` plugins](https://docs.moodle.org/dev/Authentication_plugins)

Guest Users and Admin Users cannot be deleted.

To check the technical implementation, look at `/lib/moodlelib.php`.

## Sub-plugins

The Plugin requires at least one sub-plugin that returns users to be handled by the cronjob. 
Every university can write their own sub-plugin which specifies the conditions to delete, archive and 
reactivate users. An Interface is included in the directory `cleanupusers/classes`. 

The sub-plugin needs to implement four functions:
 - get_to_suspend()
 
    returns an array of all users who are supposed to be suspended by the next cronjob.
     
 - get_to_delete()
 
    returns an array of all users who are supposed to be deleted by the next cronjob.
   
 - get_never_logged_in()
 
    returns an array of all users who never logged in.
    
 - get_to_reactivate()
 
     returns an array of all users who are supposed to be reactivated by the next cronjob.
     
The arrays that are returned have the `userid` as the key for the specific user. The value of the key must provide at 
least the following information in an additional array: 
  * `userid`: integer not exceeding 10 integers
  * `username`: varchar not exceeding 100 characters
  * `lastaccess`: Unix timestamp (10 integers)
  * `suspended`: integer 1 = suspended, 0 = not
  * `deleted`: integer 1 = deleted, 0 = not
  
As the default the sub-plugin of the University of Münster is installed and enabled and cannot be uninstalled.
Moreover, sub-plugins that are currently in use cannot be uninstalled.

### Userstatuswwu
### Timechecker

## Desirable Extension
- prohibit sending messages to suspended users
