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
 * Renderer for the Web interface of tool_cleanupusers.
 *
 * @package    tool_cleanupusers
 * @copyright  2016/17/18/19 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/tablelib.php');
/**
 * Class of the tool_cleanupusers renderer.
 *
 * @package    tool_cleanupusers
 * @copyright  2016/17/18/19 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_cleanupusers_renderer extends plugin_renderer_base {

    /**
     * Function renders links to three pages to manage users.
     *
     * @return string html
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function render_index_page() {
        $output = '';
        $url = new moodle_url("/admin/tool/cleanupusers/neverloggedin.php");
        $output .= get_string("neverloggedin", 'tool_cleanupusers') . " " .
            html_writer::link( $url, "here", array()) . ".<br>" ;
        $url = new moodle_url("/admin/tool/cleanupusers/toarchive.php");
        $output .= get_string("toarchive", 'tool_cleanupusers') . " " .
            html_writer::link( $url, "here", array()) . ".<br>";
        $url = new moodle_url("/admin/tool/cleanupusers/todelete.php");
        $output .= get_string("todelete", 'tool_cleanupusers') . " " .
            html_writer::link( $url, "here", array()) . ".<br>";
        return $output;
    }

    /**
     * Functions returns the heading for the tool_cleanupusers.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_heading() {
        $output = '';
        $output .= $this->heading(get_string('pluginname', 'tool_cleanupusers'));
        return $output;
    }
}