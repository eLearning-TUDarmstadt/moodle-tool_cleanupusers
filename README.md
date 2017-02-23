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
The Plugin aims to make users anonymous that are suspended.
This includes:
1. Hide username, firstname, lastname and all contact references to other moodle users.
2. Save necessary data in a shadow table to reactivate users when necessary.

Users that are deleted will be deleted in the `user` table.

## Subplugins
The Plugin requires at least one subplugin that returns the status of all users. 
Every university can write their own subplugin which specifies the condition to delete archive and 
reactivate users. An Interface for the methods to be implemented is included in the directory
 `deprovisionuser/classes`. 
As the default the subplugin of the University of Münster is installed and can not be uninstalled.
Additionally, subplugins that are currently in use can not be uninstalled.
 
## TODO
 - [ ] Research tables to adjust.