# moodle-tool_deprovisionuser *(Alpha_candidate)*
</br>
A Moodle Admin tool plugin to enable the automatic and manual deprovisioning of users.

This plugin is written by [Jan Dageförde](https://github.com/Dagefoerde), [Tobias Reischmann](https://github.com/tobiasreischmann) and [Nina Herrmann](https://github.com/NinaHerrmann).

## Installation
This plugin should go into `admin/tool/deprovisionuser`. No supplementary settings are required in the main plugin. Optionally the subplugin can be changed in `Home ► Site administration ► Users ► Deprovision of Users`. 
By default, the userstatuswwu subplugin is used. However, the userstatuswwu subplugin requires additional settings therefore please read the information for the [subplugins](#subplugins). 
                                                                                 

## Approach

### Automatic
A cronjob deletes, archives and reactivates users automatically. 
By default, the cronjob runs every day at 4am. 
The admin can change the revision of the cronjob in `Home ► Site administration ► Server ► Scheduled task`. 

Afterwards the admin receives a notification e-mail about the number of archived and deleted 
users. Additionally, information about the cronjob is logged and can be seen in the `Home ► Site administration ► Reports ► Logs` menu.

### Manually

Users can be changed manually by every person who has access to the admin menu. 
In the `Home ► Site administration ► Users ► Deprovision of Users` menu an overview of all users is given. 
The user can be altered with a link.

## Archive User

Moodle provides the following functionality when suspending a user:
- kills the user session
- mark the user in the `user` table as suspended
    - therefore, the user cannot log in anymore
    
The Plugin aims to make users anonymous that are suspended.

This includes:

- save necessary data in a shadow table to reactivate users when necessary. (`deprovisionuser_archive`)
- hide all other references in the `user` table e.g. `username`, ` firstname`, `lastname` and contact references
    - the username is set to Anonym + id
    
        *usernames must be unique therefore the id is appended.*
    
    - `firstname` is set to Anonym
        - references in e.g. Forums have merely reference to a user called `Anonym`
    - replacing all other data in the `user` table with the appropriate null value
        - when viewing the page of the user he/she cannot be identified

### desirable extension
- prohibit sending messages to suspended users

## Delete User

Moodle provides the following functionality when deleting a user with the `delete_user()` function:
- replaces the username with the e-mail address and a timestamp and replaces the email address 
with a random string of numbers and letters 
- mark the user in the `user` table as deleted
- calls all Plugins with a `pre_user_delete` function to execute the function
- all grades are deleted backup is kept in grade_grades_history table
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
- notifies all auth plugins

Guest Users and Admin Users cannot be deleted.

To check the technical implementation, look at `/lib/moodlelib.php`.

In addition to the provided functionality the deprovisionuser-plugin does:

- hash the username

    *in case the username already exist the username and the hashed username is hashed again*

## Subplugins

The Plugin requires at least one subplugin that returns the status of all users. 
Every university can write their own subplugin which specifies the conditions to delete, archive and 
reactivate users. An Interface for the methods to be implemented is included in the directory
 `deprovisionuser/classes`. 
As the default the subplugin of the University of Münster is installed and enabled and cannot be uninstalled.
Moreover, subplugins that are currently in use cannot be uninstalled.
