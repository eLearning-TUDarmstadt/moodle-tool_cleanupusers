# moodle-tool_deprovisionuser *(Alpha_candidate)*
</br>
A Moodle Admin tool to enable Deprovisioning of Users.

This plugin is written by [Jan Dageförde](https://github.com/Dagefoerde), [Tobias Reischmann](https://github.com/tobiasreischmann) and [Nina Herrmann](https://github.com/NinaHerrmann).


## Installation
This plugin should go into `admin/tool/deprovisionuser`.


## Approach
The Plugin executes a cron job which deletes, archives and reactivates archived users automatically. Afterwards the admin receives a notification
e-mail about the number of archived and deleted users.

## Archive User

Moodle provides the following functionality when suspending a user:
- kills the user session
- mark the user in the `user` table as suspended
    - threfore the user can not log in anymore
    
The Plugin aims to make users anonymous that are suspended.
This includes:

- hide username, firstname, lastname and all contact references to other moodle users
    - replacing the username with Anonym + id
    
        *This is neccessary since usernames have to be unique.*
    
    - replacing all other data in the `user` table with the appropriate null value
- save necessary data in a shadow table to reactivate users when necessary. (`deprovisionuser_archive`)

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
- remove user from
    - all cohort
    - all groups
- moves all unread messages to read
- purges log of previous password hashes
- removes all user tokens
- prohibits the user for all services
- forces the user logout - may fail if file based sessions used
- triggers event `\core\event\user_deleted`
- notifies all auth plugins

Guest Users and Admin Users can not be deleted.

To check the technical implementation look at `/lib/moodlelib.php`.

In addition to the provided functionality the deprovisionuser-plugin does:
- delete the complete entry in the `user` table.

## Subplugins
The Plugin requires at least one subplugin that returns the status of all users. 
Every university can write their own subplugin which specifies the conditions to delete archive and 
reactivate users. An Interface for the methods to be implemented is included in the directory
 `deprovisionuser/classes`. 
As the default the subplugin of the University of Münster is installed and can not be uninstalled.
Additionally, subplugins that are currently in use can not be uninstalled.
 
## TODO
 - [ ] Research tables to adjust.