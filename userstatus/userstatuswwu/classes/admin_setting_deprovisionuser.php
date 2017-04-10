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
 * Extra admin setting page to validate data.
 *
 * @package   userstatus_userstatuswwu
 * @copyright 2016 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_userstatuswwu;

use admin_setting_configtext;

defined('MOODLE_INTERNAL') || die;

class admin_setting_deprovisionuser extends admin_setting_configtext {

    public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype=PARAM_RAW,
                                $size=null, $maxlength = 0) {
        $this->maxlength = $maxlength;
        parent::__construct($name, $visiblename, $description, $defaultsetting, $paramtype, $size);
    }

    public function validate($data) {
        $config = get_config('userstatus_userstatuswwu');
        $errors = array();
        if (!file_exists($config->pathtotxt)) {
            $errors['txtrout'] = new userstatuswwu_exception(get_string('zivlistnotfound', 'userstatus_userstatuswwu'));
        }
        if (empty($errors)) {
            return true;
        }
        return $errors;
    }
}