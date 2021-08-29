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
 * Class archive user.
 *
 * @package   tool_cleanupusers
 * @copyright 2017 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupusers;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/lib/moodlelib.php');

use \core\session\manager;
/**
 * The class collects the necessary information to suspend, delete and activate users.
 * It can be used in sub-plugins, since the constructor assures that all necessary information is transferred.
 *
 * @package   tool_cleanupusers
 * @copyright 2017 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class archiveduser {

    /** @var int The id of the user */
    public $id;

    /** @var int 1 if the user is suspended 0 otherwise */
    public $suspended;

    /** @var int timestamp */
    public $lastaccess;

    /** @var string username */
    public $username;

    /** @var int user deleted? */
    public $deleted;

    /**
     * Archiveduser constructor.
     * @param int $id
     * @param int $suspended
     * @param int $lastaccess
     * @param string $username
     * @param int $deleted
     */
    public function __construct($id, $suspended, $lastaccess, $username, $deleted) {
        $this->id = $id;
        $this->suspended = $suspended;
        $this->lastaccess = $lastaccess;
        $this->username = $username;
        $this->deleted = $deleted;
    }
}
