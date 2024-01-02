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
The sub-plugin can be selected in `Home ► Site administration ► Users ► Deprovision of Users`.   
By default, the **userstatuswwu sub-plugin** is used. 
However, it is likely that the sub-plugin requires additional settings, therefore, please read the information for the [sub-plugins](#sub-plugins) before using the plugin. 

## Manual Handling

Users can be changed manually by every person who has access to the admin menu.
Beforehand users were handled in the `Home ► Site administration ► Users ► Accounts ► Browse list of users` menu.  
The plugin provides an extra page which can be found in the `Home ► Site administration ► Users ► Deprovision of Users` menu.

## Automatic Processing
A cronjob deletes, archives and reactivates users automatically. 
By default, the cronjob runs every day at 4 am. 
The admin can change the revision of the cronjob in `Home ► Site administration ► Server ► Scheduled task`.   
After the cronjob ran successfully, the admin receives a notification e-mail about the number of archived and deleted 
users. In case problems occurred with single users, the number of users who could not be handled are listed. 
This is for example the case when a sub-plugin tries to suspend/delete an admin user. 
Additionally, information about the cronjob is logged and can be seen in the `Home ► Site administration ► Reports ► Logs` menu.

## Suspend User

Moodle provides the following functionality when suspending a user:
- kill the user session,
- mark the user in the `user` table as suspended.
    - Consequently, the user cannot sign in anymore.
    
The plugin aims to make users that are suspended **anonymous**. Therefore, the following additional functionalities are provided:  
- save necessary data in a shadow table to reactivate users when necessary (the table is called: `tool_cleanupusers_archive`),
- hide all other references in the `user` table e.g. `username`, ` firstname`.
    - The `username` is set to *anonym* with the `userid` appended  
      (usernames must be unique therefore the id is appended).
    - The field `firstname` is set to *Anonym*.
        - Consequently, references in e.g. the forum activity merely refer to a user called `Anonym`.
    - Replaces all other data in the `user` table with the appropriate null value.
        - When viewing the page of the user he/she cannot be identified.

## Delete User
Moodle provides a `delete_user()` function, which is used by the plugin.
In the plugin, firstly the username is hashed. In case the hashed value already exist the username and the hashed 
username are hashed again.  
Afterwards the moodle `delete_user()` function is executed with the following functions:
- the username is replaced with the e-mail address and a timestamp and the email address is replaced
with a random string of numbers and letters,
  
    *Due to the plugin changes the moodle function now uses the hashed username, therefore, the possibility to get information over the user since the e-mail is used as a new username is no longer possible.*
- the user is flagged in the `user` table as deleted,
- all plugins with a `pre_user_delete()` function are called to execute the function,
- all grades are deleted, backup is kept in `grade_grades_history` table,
- all item tags are removed,
- withdraws user from:
    - all courses
    - all roles in all contexts
- removes user from
    - all cohort
    - all groups
- moves all unread messages from the user to read,
- purges log of previous password hashes,
- removes all user tokens,
- prohibits the user from all services,
- forces the user logout (may fail if file based sessions used),
- triggers event `\core\event\user_deleted`,
- notifies all [`auth` plugins](https://docs.moodle.org/dev/Authentication_plugins).

Remarks : 
- Guest Users and Admin Users cannot be deleted.
- When the user is processed after the moodle function was executed, the user is no longer flagged as deleted.

To check the technical implementation, look at `/lib/moodlelib.php`.

## Sub-plugins

The plugin requires at least one sub-plugin of the type `cleanupusers_userstatus` that returns users to be handled by the cronjob. 
You can write their own sub-plugin which specifies the conditions to delete, archive, and 
reactivate users. An interface with the minimum functionality to be implemented is included in the directory `cleanupusers/classes`, consisting of four functions:
 - `get_to_suspend()`
 
    Returns an array of all users who are supposed to be suspended by the next cronjob.
     
 - `get_to_delete()`
 
    Returns an array of all users who are supposed to be deleted by the next cronjob.
   
 - `get_never_logged_in()`
 
    Returns an array of all users who never logged in.
    
 - `get_to_reactivate()`
 
     Returns an array of all users who should be reactivated by the next cronjob.
     
The arrays that are returned must provide at 
least the following information for each user that should be handled: 
  * `userid`: integer not exceeding 10 integers
  * `username`: varchar not exceeding 100 characters
  * `lastaccess`: Unix timestamp (10 integers)
  * `suspended`: integer 1 = suspended, 0 = not
  * `deleted`: integer 1 = deleted, 0 = not
  
As the default, the sub-plugin of the University of Münster is installed and enabled and cannot be uninstalled.
Moreover, sub-plugins that are currently in use cannot be uninstalled.
If you implement your own subplugin it should be placed in `admin/tool/cleanupusers/userstatus`.
### Userstatuswwu
This plugin selects user depending on a .txt file. In the installation of the plugin the path to the file has to be defined.
The .txt file list the usernames followed by an empty space and the group name. If you want to adjust the group names you will need to change the hardcoded names in the plugin.
Manually suspended users are not considered.
### Timechecker
The timechecker plugin suspends and deletes user depending on the last access of the user to the platform. 
The site administrator can define custom time spans, as a default 90 days have to pass without a user logging in until the 
user is suspended and 365 days until the user is deleted.
Currently, users that are manually suspended and did not log in for the defined time are also deleted.  
## Desirable Extension
- prohibit sending messages to suspended users
