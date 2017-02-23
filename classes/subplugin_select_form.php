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
 * Create an Form Class for the tool_deprovisionuser
 *
 * @package   tool_deprovisionuser
 * @copyright 2017 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_deprovisionuser;
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
use moodleform;
use core_plugin_manager;

class subplugin_select_form extends moodleform {
    /**
     * Defines the subplugin select form.
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;
        $plugins = core_plugin_manager::instance()->get_plugins_of_type('userstatus');
        $types = array();
        foreach ($plugins as $value) {
            $types[$value->name] = $value->name;
        }
        if (empty(get_config('tool_deprovisionuser'))) {
            $text = 'Please select a subplugin';
        } else {
            $text = 'Change the subplugin';
        }
        $mform->addElement('select', 'subplugin', $text, $types);
        $mform->addElement('submit', 'reset', 'Submit');
    }

    /**
     * Checks data for correctness
     * @param array $data
     * @param array $files
     * @return bool/array array in case an error occurs, otherwise true.
     */
    public function validation($data, $files) {
        $plugins = core_plugin_manager::instance()->get_plugins_of_type('userstatus');
        $issubplugin = false;
        foreach ($plugins as $value) {
            if ($value->name == $data['subplugin']) {
                $issubplugin = true;
                break;
            }
        }
        if ($issubplugin == false) {
            $issubplugin['subplugin'] = new deprovisionuser_subplugin_exception
            (get_string('errormessagesubplugin', 'tool_deprovisionuser'));
        }
        return $issubplugin;
    }
}