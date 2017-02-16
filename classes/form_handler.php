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
 *
 *
 * @package   tool_deprovisionuser
 * @copyright 2016 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_deprovisionuser;

class form_handler {

    public function handle() {
        $content = '';
        $mform = new subplugin_select_form();
        /*if ($mform->is_cancelled()) {
            //Handle form cancel operation, if cancel button is present on form
        } else*/ if ($fromform = $mform->get_data()) {
            set_config('tool_deprovisionuser', $fromform->Subplugin);
            $content = 'You successfully submitted the Subplugin.';
            $content .= $mform->display();
            //In this case you process validated data. $mform->get_data() returns data posted in form.
        } else {
            // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
            // or on the first display of the form.

            //Set default data (if any)
            //$mform->set_data($toform);
            //displays the form
            $content .= $mform->display();
        }
        return $content;
    }
}