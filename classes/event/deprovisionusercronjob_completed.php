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
 * The tool_cleanupusers cron job complete event.
 *
 * @package    tool_cleanupusers
 * @copyright  2016/17 N Herrrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cleanupusers\event;

use core\event\base;
/**
 * The tool_cleanupusers event informs admin about outcome of cron-job.
 *
 * @package    tool_cleanupusers
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deprovisionusercronjob_completed extends base {
    /**
     * Creates a simple event with the number of users archives and deleted as additional information.
     *
     * @param \stdClass $context
     * @param int $numbersuspended number of users suspended.
     * @param int $numberdeleted number of users deleted.
     * @return \core\event\base
     */
    public static function create_simple($context, $numbersuspended, $numberdeleted) {
        return self::create(['context' => $context, 'other' => ['numbersuspended' => $numbersuspended,
            'numberdeleted' => $numberdeleted]]);
    }

    /**
     * Initialize data.
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Defines the name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('cronjobcomplete', 'tool_cleanupusers');
    }


    /**
     * Generates a message about the number of users deleted and suspended. The message is displayed in the Live Logs
     * and the Logs table. If no Users are affected the message states that the cron-job was running.
     *
     * @return string
     */
    public function get_description() {
        // Get event data to determine the number of users affected.
        $archived = $this->data['other']['numbersuspended'];
        $deleted = $this->data['other']['numberdeleted'];

        // If no user was affected...
        if (empty($archived) && empty($deleted)) {
            return get_string('cronjobwasrunning', 'tool_cleanupusers');
        } else {
            // Otherwise number of users affected.
            return get_string('e-mail-archived', 'tool_cleanupusers', $archived) . ' ' .
                get_string('e-mail-deleted', 'tool_cleanupusers', $deleted);
        }
    }
}
