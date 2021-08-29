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
 * A scheduled task for tool_cleanupusers cron.
 *
 * The Class archive_user_task is supposed to show the admin a page of users which will be archived and expectes a submit or
 * cancel reaction.
 * @package    tool_cleanupusers
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_cleanupusers\task;

defined('MOODLE_INTERNAL') || die();

use tool_cleanupusers\cleanupusers_exception;
// Needed for the default plugin.
use tool_cleanupusers\local\manager\subpluginmanager;
use tool_cleanupusers\transaction;
use tool_cleanupusers\useraction;
use tool_cleanupusers\usermanager;
use userstatus_userstatuswwu\userstatuswwu;
use tool_cleanupusers\archiveduser;
use tool_cleanupusers\event\deprovisionusercronjob_completed;
use core\task\scheduled_task;

class archive_user_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('archive_user_task', 'tool_cleanupusers');
    }

    /**
     * Runs the cron job - Calls for the currently activated sub-plugin to return arrays of users.
     * Distinguishes between users to reacticate, suspend and delete.
     * Subsequently sends an e-mail to the admin containing information about the amount of successfully changed users
     * and the amount of failures.
     * Last but not least triggers an event with the same information.
     *
     * @return true
     */
    public function execute() {
        $this->execute_user_actions();
        $this->fill_approve_queue();
        return true;
    }

    public function execute_user_actions() {
        global $DB;

        $userstatuschecker = subpluginmanager::get_userstatus_plugin();

        // Private function is executed to suspend, delete and activate users.
        $subpluginactions = [
                useraction::SUSPEND => $userstatuschecker->get_to_suspend(),
                useraction::REACTIVATE => $userstatuschecker->get_to_reactivate(),
                useraction::DELETE => $userstatuschecker->get_to_delete()
        ];

        $results = [];

        foreach ($subpluginactions as $useraction => $users) {
            // Only get those users, that are actually approved for the desired action.
            if (count($users) == 0) {
                $approvedusers = [];
            } else {
                list($insql, $inparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED);
                $inparams['action'] = $useraction;
                $approvedusers = $DB->get_fieldset_select('tool_cleanupusers_approve',
                        'userid',
                        'approved = 1 AND action = :action AND userid ' . $insql);
            }

            $results[$useraction] = $this->change_user_deprovisionstatus($approvedusers, $useraction);
        }

        $unabletoactivate = $results[useraction::REACTIVATE]['failures'];
        $unabletoarchive = $results[useraction::SUSPEND]['failures'];
        $unabletodelete = $results[useraction::DELETE]['failures'];
        $userarchived = $results[useraction::SUSPEND]['countersuccess'];
        $userdeleted = $results[useraction::DELETE]['countersuccess'];

        // Admin is informed about the cron-job and the amount of users that are affected.

        $admin = get_admin();
        // Number of users suspended or deleted.
        $messagetext = get_string('e-mail-archived', 'tool_cleanupusers', $userarchived) .
            "\r\n" .get_string('e-mail-deleted', 'tool_cleanupusers', $userdeleted);

        // No Problems occured during the cron-job.
        if (empty($unabletoactivate) and empty($unabletoarchive) and empty($unabletodelete)) {
            $messagetext .= "\r\n\r\n" . get_string('e-mail-noproblem', 'tool_cleanupusers');
        } else {
            // Extra information for problematic users.
            $messagetext .= "\r\n\r\n" . get_string('e-mail-problematic_delete', 'tool_cleanupusers',
                            count($unabletodelete)) . "\r\n\r\n" . get_string('e-mail-problematic_suspend', 'tool_cleanupusers',
                            count($unabletoarchive)) . "\r\n\r\n" . get_string('e-mail-problematic_reactivate', 'tool_cleanupusers',
                            count($unabletoactivate));
        }

        // Email is send from the do not reply user.
        $sender = \core_user::get_noreply_user();
        email_to_user($admin, $sender, 'Update Infos Cron Job tool_cleanupusers', $messagetext);

        // Triggers deprovisionusercronjob_completed event.
        $context = \context_system::instance();
        $event = deprovisionusercronjob_completed::create_simple($context, $userarchived, $userdeleted);
        $event->trigger();

        return true;
    }

    public function fill_approve_queue() {
        global $DB;

        $userstatuschecker = subpluginmanager::get_userstatus_plugin();

        // Delete all delays that are expired.
        $DB->delete_records_select('tool_cleanupusers_delay', 'delayuntil IS NOT NULL and delayuntil < :time',
                ['time' => time()]);

        // User the subplugins wants to do things to. Reactivate > Suspend > Delete is the priority, if the users is in
        $subpluginactions = [
                useraction::REACTIVATE => $userstatuschecker->get_to_reactivate(),
                useraction::SUSPEND => $userstatuschecker->get_to_suspend(),
                useraction::DELETE => $userstatuschecker->get_to_delete()
        ];

        $actionits = [];
        $delayits = [];

        $globaldelay = new \ArrayIterator(
                $DB->get_fieldset_select('tool_cleanupusers_delay', 'id',
                        'action IS NULL ' .
                        'ORDER BY id ASC')
        );

        foreach ($subpluginactions as $action => $users) {
            sort($subpluginactions[$action]);
            $actionits[$action] = new \ArrayIterator($subpluginactions[$action]);

            $delayits[$action] = new \ArrayIterator(
                    $DB->get_fieldset_select('tool_cleanupusers_delay', 'id',
                            'action = :action ' .
                            'ORDER BY id ASC', ['action' => $action])
            );
        }

        $selectedactions = $this->calculate_useractions($actionits, $delayits, $globaldelay);
        $this->update_approve_db($selectedactions);
    }

    protected function calculate_useractions($actionits, $delayits, $globaldelayit) {
        $selectedactions = [];

        foreach($actionits as $action => $iterator) {
            $selectedactions[$action] = [];
        }

        while (count($actionits) > 0) {
            $minuser = null;
            $chosenaction = null;

            foreach ($actionits as $action => $iterator) {
                if (!$iterator->valid()) {
                    unset($actionits[$action]);
                    continue;
                }

                self::forward_iterator_until($delayits[$action], $iterator->current());
                if ($delayits[$action]->current() === $iterator->current()) {
                    $iterator->next();
                    continue;
                }

                if ($minuser === null) {
                    $chosenaction = $action;
                    $minuser = $iterator->current();
                } else if ($minuser == $iterator->current()) {
                    // Something with a higher priority has the same user as we do.
                    $iterator->next();
                } else if ($iterator->current() < $minuser) {
                    $chosenaction = $action;
                    $minuser = $iterator->current();
                }
            }

            if ($chosenaction !== null) {
                self::forward_iterator_until($globaldelayit, $minuser);
                if ($globaldelayit->current() !== $minuser) {
                    $selectedactions[$chosenaction][] = $minuser;
                }
                $actionits[$chosenaction]->next();
            }
        }
        return $selectedactions;
    }

    protected function update_approve_db($selectedactions) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        foreach ($selectedactions as $action => $users) {
            if (count($users) == 0) {
                continue;
            }

            list($notinsql, $notinparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'param', false);
            $notinparams['action'] = $action;

            $DB->delete_records_select('tool_cleanupusers_approve', 'action = :action AND userid ' . $notinsql,
                    $notinparams);
        }

        foreach ($selectedactions as $action => $users) {
            /* TODO would like to replace this with something akin to INSERT INTO {approve} SELECT (u.id, :action, 0)
                FROM (VALUES (23), (83), ...) as u (id) WHERE u.id IS NOT IN (SELECT userid FROM approve); */

            $existingusers = new \ArrayIterator(
                    $DB->get_fieldset_select('tool_cleanupusers_approve', 'userid', 'action = :action ORDER BY userid ASC',
                            ['action' => $action])
            );

            $record = new \stdClass();
            $record->action = $action;
            $record->approved = 0;
            foreach($users as $user) {
                self::forward_iterator_until($existingusers, $user);
                if ($user === $existingusers->current()) {
                    continue;
                }

                $record->userid = $user;
                $DB->insert_record_raw('tool_cleanupusers_approve', $record, false, true);
            }
        }
        $transaction->allow_commit();
    }

    protected static function forward_iterator_until(\ArrayIterator $iterator, $until) {
        while ($iterator->valid() && $iterator->current() < $until) {
            $iterator->next();
        }
    }

    /**
     * Deletes, suspends or reactivates an array of users.
     *
     * @param  array $userarray of users
     * @param  string $intention of suspend, delete, reactivate
     * @return array ['countersuccess'] successfully changed users ['failures'] userids, who could not be changed.
     * @throws \coding_exception
     */
    protected function change_user_deprovisionstatus($userarray, $intention) {
        // Checks whether the intention is valid.
        if (!in_array($intention, useraction::actions)) {
            throw new \coding_exception('Invalid parameters in tool_cleanupusers.');
        }

        // Number of successfully changed users.
        $countersuccess = 0;

        // Array of users who could not be changed.
        $failures = array();

        // Alternatively one could have wrote different function for each intention.
        // However this would have produced duplicated code.
        // Therefore checking the intention parameter repeatedly was preferred.
        foreach ($userarray as $key => $userid) {
            if (!is_siteadmin($userid)) {
                try {
                    switch ($intention) {
                        case useraction::SUSPEND:
                            usermanager::suspend_user($userid);
                            break;
                        case useraction::REACTIVATE:
                            usermanager::reactivate_user($userid);
                            break;
                        case useraction::DELETE:
                            usermanager::delete_user($userid);
                            break;
                        // No default since if-clause checks the intention parameter.
                    }
                    $countersuccess++;
                } catch (cleanupusers_exception $e) {
                    $failures[$key] = $userid;
                }
            }
        }
        $result = array();
        $result['countersuccess'] = $countersuccess;
        $result['failures'] = $failures;
        return $result;
    }
}