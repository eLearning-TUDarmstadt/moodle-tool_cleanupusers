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
 * The mod_page course module viewed event.
 *
 * @package    mod_page
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_deprovisionuser\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The tool_deprovisionuser informs admin about outcome of cronjob.
 *
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deprovisionusercronjob_completed extends \core\event\base {

    public static function create_simple($context, $numberarchived, $numberdeleted) {
        return self::create(array('context' => $context, 'other' => array('numberarchived' => $numberarchived,
            'numberdeleted' => $numberdeleted)));
    }

    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('cronjobcomplete', 'tool_deprovisionuser');
    }

    public function get_description() {
        $archived = $this->data['other']['numberarchived'];
        $deleted = $this->data['other']['numberdeleted'];
        if (empty($archived) and empty($deleted)) {
            return get_string('cronjobwasrunning', 'tool_deprovisionuser');
        } else {
            return get_string('e-mail-archived', 'tool_deprovisionuser', $archived) . ' ' .
                get_string('e-mail-deleted', 'tool_deprovisionuser', $deleted);
        }
    }
}
