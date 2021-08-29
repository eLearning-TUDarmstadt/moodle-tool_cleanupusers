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
 * form to add new blocked users
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cleanupusers\local\form;

use tool_cleanupusers\useraction;

defined('MOODLE_INTERNAL') || die();

/**
 * form to add new blocked users
 *
 * @package   tool_cleanupusers
 * @copyright 2021 Justus Dieckmann WWU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blockusersform extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $options = [
                0 => get_string('allactions', 'tool_cleanupusers'),
                useraction::DELETE => useraction::get_string_for_action_verb(useraction::DELETE),
                useraction::SUSPEND => useraction::get_string_for_action_verb(useraction::SUSPEND),
                useraction::REACTIVATE => useraction::get_string_for_action_verb(useraction::REACTIVATE),
        ];
        $mform->addElement('select', 'action', get_string('action', 'tool_cleanupusers'), $options);

        $mform->addElement('date_time_selector', 'blockeduntil', get_string('delayeduntil', 'tool_cleanupusers'));
        $mform->setDefault('blockeduntil', get_config('tool_cleanupusers', 'rollbackduration') + time());
        $mform->disabledIf('blockeduntil', 'blockedforever', 'checked');

        $mform->addElement('checkbox', 'blockedforever', get_string('blockedforever', 'tool_cleanupusers'));

        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'selectusersvia', '', get_string('file'), 0);
        $radioarray[] = $mform->createElement('radio', 'selectusersvia', '', get_string('inputfield', 'tool_cleanupusers'), 1);
        $mform->addGroup($radioarray, 'selectusersviaar', get_string('selectusersvia', 'tool_cleanupusers'), array(' '), false);


        $options = [
                'ajax' => 'core_search/form-search-user-selector',
                'multiple' => true,
                'valuehtmlcallback' => function ($value) {
                    global $DB, $OUTPUT;
                    $user = $DB->get_record('user', ['id' => (int)$value], '*', IGNORE_MISSING);
                    if (!$user || !user_can_view_profile($user)) {
                        return false;
                    }
                    $details = user_get_user_details($user);
                    return $OUTPUT->render_from_template(
                            'core_search/form-user-selector-suggestion', $details);
                }
        ];
        $mform->addElement('autocomplete', 'users', get_string('users'), array(), $options);
        $mform->hideIf('users', 'selectusersvia', 'neq', 1);

        $mform->addElement('filepicker', 'usersfile', get_string('users'), null,
                array('accepted_types' => '*'));
        $mform->addHelpButton('usersfile', 'usersfile', 'tool_cleanupusers');
        $mform->hideIf('usersfile', 'selectusersvia', 'neq', 0);

        $this->add_action_buttons(true, get_string('add'));


    }
}