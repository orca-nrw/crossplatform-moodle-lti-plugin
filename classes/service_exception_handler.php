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
 * Exception handler for ORCALTI services
 *
 * @package   mod_orcalti
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_orcalti;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/../locallib.php');
require_once(__DIR__.'/../servicelib.php');

/**
 * Handles exceptions when handling incoming ORCALTI messages.
 *
 * Ensures that ORCALTI always returns a XML message that can be consumed by the caller.
 *
 * @package   mod_orcalti
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_exception_handler {
    /**
     * Enable error response logging.
     *
     * @var bool
     */
    protected $log = false;

    /**
     * The ORCALTI service message ID, if known.
     *
     * @var string
     */
    protected $id = '';

    /**
     * The ORCALTI service message type, if known.
     *
     * @var string
     */
    protected $type = 'unknownRequest';

    /**
     * Constructor.
     *
     * @param boolean $log Enable error response logging.
     */
    public function __construct($log) {
        $this->log = $log;
    }

    /**
     * Set the ORCALTI message ID being handled.
     *
     * @param string $id
     */
    public function set_message_id($id) {
        if (!empty($id)) {
            $this->id = $id;
        }
    }

    /**
     * Set the ORCALTI message type being handled.
     *
     * @param string $type
     */
    public function set_message_type($type) {
        if (!empty($type)) {
            $this->type = $type;
        }
    }

    /**
     * Echo an exception message encapsulated in XML.
     *
     * @param \Exception|\Throwable $exception The exception that was thrown
     */
    public function handle($exception) {
        $message = $exception->getMessage();

        // Add the exception backtrace for developers.
        if (debugging('', DEBUG_DEVELOPER)) {
            $message .= "\n".format_backtrace(get_exception_info($exception)->backtrace, true);
        }

        // Switch to response.
        $type = str_replace('Request', 'Response', $this->type);

        // Build the appropriate xml.
        $response = orcalti_get_response_xml('failure', $message, $this->id, $type);

        $xml = $response->asXML();

        // Log the request if necessary.
        if ($this->log) {
            orcalti_log_response($xml, $exception);
        }

        echo $xml;
    }
}
