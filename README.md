# moodle-tool_deprovisionuser *(Alpha_candidate)*
</br>
A Moodle Admin tool to enable Deprovisioning of Users.

This plugin is written by [Jan Dageförde](https://github.com/Dagefoerde), [Tobias Reischmann](https://github.com/tobiasreischmann) and [Nina Herrmann](https://github.com/NinaHerrmann).


## Installation
This plugin should go into `admin/tool/deprovisionuser`.


## Approach
The Plugin executes a cron job which deletes, archives and reactivates archived users automatically. Afterwards the admin receives a notification
e-mail about the number of archived and deleted users.

## TODO
- [ ] Show a warning message for admins before users are archived or deleted
- [ ] Implement that at least one Subplugin with the interface  `userstatusinterface ` is installed

## Subplugins
The Plugin requires at least one subplugin that returns the status of all users. The intention is that every university can write their own subplugin
which specifies the condition to delete archive and reactivate users. An Interface for the methods to be implemented is included in the directory
 `deprovisionuser/classes`. The default subplugin archives users when the last access is one month ago and deletes them after eleven additional months.






