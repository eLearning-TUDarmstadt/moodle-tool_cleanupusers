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
 * Create an Exception Class for the userstatus_userstatuswwu
 *
 * @package   userstatus_userstatuswwu
 * @copyright 2017 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userstatus_userstatuswwu;
/**
 * Thow errors for the plugin.
 * @package   userstatus_userstatuswwu
 * @copyright 2017 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userstatuswwu_exception extends \moodle_exception {
    /**
     * Constructor
     * @param string $errorcode The name of the string from webservice.php to print
     * @param string $a The name of the parameter
     * @param string $debuginfo Optional information to aid debugging
     */
    public function __construct($errorcode, $a = '', $debuginfo = null) {
        parent::__construct($errorcode, 'userstatus_userstatuswwu', '', $a, $debuginfo);
    }
}
