<?php
/**
 * Created by IntelliJ IDEA.
 * User: nina
 * Date: 17.01.17
 * Time: 17:09
 */

namespace tool_deprovisionuser;


class deprovisionuser_exception extends \moodle_exception {

    /**
     * Constructor
     * @param string $errorcode The name of the string from webservice.php to print
     * @param string $a The name of the parameter
     * @param string $debuginfo Optional information to aid debugging
     */
    function __construct($errorcode, $a = '', $debuginfo = null) {
        parent::__construct($errorcode, 'tool_deprovisionuser', '', $a, $debuginfo);
    }
}