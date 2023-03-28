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
 * Utility code for ORCALTI service handling.
 *
 * @package mod_orcalti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/orcalti/OAuthBody.php');
require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use moodle\mod\orcalti as orcalti;

define('ORCALTI_ITEM_TYPE', 'mod');
define('ORCALTI_ITEM_MODULE', 'orcalti');
define('ORCALTI_SOURCE', 'mod/orcalti');

function orcalti_get_response_xml($codemajor, $description, $messageref, $messagetype) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><imsx_POXEnvelopeResponse />');
    $xml->addAttribute('xmlns', 'http://www.imsglobal.org/services/orcaltiv1p1/xsd/imsoms_v1p0');

    $headerinfo = $xml->addChild('imsx_POXHeader')->addChild('imsx_POXResponseHeaderInfo');

    $headerinfo->addChild('imsx_version', 'V1.0');
    $headerinfo->addChild('imsx_messageIdentifier', (string)mt_rand());

    $statusinfo = $headerinfo->addChild('imsx_statusInfo');
    $statusinfo->addchild('imsx_codeMajor', $codemajor);
    $statusinfo->addChild('imsx_severity', 'status');
    $statusinfo->addChild('imsx_description', $description);
    $statusinfo->addChild('imsx_messageRefIdentifier', $messageref);
    $incomingtype = str_replace('Response', 'Request', $messagetype);
    $statusinfo->addChild('imsx_operationRefIdentifier', $incomingtype);

    $xml->addChild('imsx_POXBody')->addChild($messagetype);

    return $xml;
}

function orcalti_parse_message_id($xml) {
    if (empty($xml->imsx_POXHeader)) {
        return '';
    }

    $node = $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo->imsx_messageIdentifier;
    $messageid = (string)$node;

    return $messageid;
}

function orcalti_parse_grade_replace_message($xml) {
    $node = $xml->imsx_POXBody->replaceResultRequest->resultRecord->sourcedGUID->sourcedId;
    $resultjson = json_decode((string)$node);
    if ( is_null($resultjson) ) {
        throw new Exception('Invalid sourcedId in result message');
    }
    $node = $xml->imsx_POXBody->replaceResultRequest->resultRecord->result->resultScore->textString;

    $score = (string) $node;
    if ( ! is_numeric($score) ) {
        throw new Exception('Score must be numeric');
    }
    $grade = floatval($score);
    if ( $grade < 0.0 || $grade > 1.0 ) {
        throw new Exception('Score not between 0.0 and 1.0');
    }

    $parsed = new stdClass();
    $parsed->gradeval = $grade;

    $parsed->instanceid = $resultjson->data->instanceid;
    $parsed->userid = $resultjson->data->userid;
    $parsed->launchid = $resultjson->data->launchid;
    $parsed->typeid = $resultjson->data->typeid;
    $parsed->sourcedidhash = $resultjson->hash;

    $parsed->messageid = orcalti_parse_message_id($xml);

    return $parsed;
}

function orcalti_parse_grade_read_message($xml) {
    $node = $xml->imsx_POXBody->readResultRequest->resultRecord->sourcedGUID->sourcedId;
    $resultjson = json_decode((string)$node);
    if ( is_null($resultjson) ) {
        throw new Exception('Invalid sourcedId in result message');
    }

    $parsed = new stdClass();
    $parsed->instanceid = $resultjson->data->instanceid;
    $parsed->userid = $resultjson->data->userid;
    $parsed->launchid = $resultjson->data->launchid;
    $parsed->typeid = $resultjson->data->typeid;
    $parsed->sourcedidhash = $resultjson->hash;

    $parsed->messageid = orcalti_parse_message_id($xml);

    return $parsed;
}

function orcalti_parse_grade_delete_message($xml) {
    $node = $xml->imsx_POXBody->deleteResultRequest->resultRecord->sourcedGUID->sourcedId;
    $resultjson = json_decode((string)$node);
    if ( is_null($resultjson) ) {
        throw new Exception('Invalid sourcedId in result message');
    }

    $parsed = new stdClass();
    $parsed->instanceid = $resultjson->data->instanceid;
    $parsed->userid = $resultjson->data->userid;
    $parsed->launchid = $resultjson->data->launchid;
    $parsed->typeid = $resultjson->data->typeid;
    $parsed->sourcedidhash = $resultjson->hash;

    $parsed->messageid = orcalti_parse_message_id($xml);

    return $parsed;
}

function orcalti_accepts_grades($orcaltiinstance) {
    global $DB;

    $acceptsgrades = true;
    $orcaltitype = $DB->get_record('lti_types', array('id' => $orcaltiinstance->typeid));

    if (empty($orcaltitype->toolproxyid)) {
        $typeconfig = orcalti_get_config($orcaltiinstance);

        $typeacceptgrades = isset($typeconfig['acceptgrades']) ? $typeconfig['acceptgrades'] : ORCALTI_SETTING_DELEGATE;

        if (!($typeacceptgrades == ORCALTI_SETTING_ALWAYS ||
            ($typeacceptgrades == ORCALTI_SETTING_DELEGATE && $orcaltiinstance->instructorchoiceacceptgrades == ORCALTI_SETTING_ALWAYS))) {
            $acceptsgrades = false;
        }
    } else {
        $enabledcapabilities = explode("\n", $orcaltitype->enabledcapability);
        $acceptsgrades = in_array('Result.autocreate', $enabledcapabilities) || in_array('BasicOutcome.url', $enabledcapabilities);
    }

    return $acceptsgrades;
}

/**
 * Set the passed user ID to the session user.
 *
 * @param int $userid
 */
function orcalti_set_session_user($userid) {
    global $DB;

    if ($user = $DB->get_record('user', array('id' => $userid))) {
        \core\session\manager::set_user($user);
    }
}

function orcalti_update_grade($orcaltiinstance, $userid, $launchid, $gradeval) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    $params = array();
    $params['itemname'] = $orcaltiinstance->name;

    $gradeval = $gradeval * floatval($orcaltiinstance->grade);

    $grade = new stdClass();
    $grade->userid   = $userid;
    $grade->rawgrade = $gradeval;

    $status = grade_update(ORCALTI_SOURCE, $orcaltiinstance->course, ORCALTI_ITEM_TYPE, ORCALTI_ITEM_MODULE, $orcaltiinstance->id, 0, $grade, $params);

    $record = $DB->get_record('orcalti_submission', array('orcaltiid' => $orcaltiinstance->id, 'userid' => $userid,
        'launchid' => $launchid), 'id');
    if ($record) {
        $id = $record->id;
    } else {
        $id = null;
    }

    if (!empty($id)) {
        $DB->update_record('orcalti_submission', array(
            'id' => $id,
            'dateupdated' => time(),
            'gradepercent' => $gradeval,
            'state' => 2
        ));
    } else {
        $DB->insert_record('orcalti_submission', array(
            'orcaltiid' => $orcaltiinstance->id,
            'userid' => $userid,
            'datesubmitted' => time(),
            'dateupdated' => time(),
            'gradepercent' => $gradeval,
            'originalgrade' => $gradeval,
            'launchid' => $launchid,
            'state' => 1
        ));
    }

    return $status == GRADE_UPDATE_OK;
}

function orcalti_read_grade($orcaltiinstance, $userid) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $grades = grade_get_grades($orcaltiinstance->course, ORCALTI_ITEM_TYPE, ORCALTI_ITEM_MODULE, $orcaltiinstance->id, $userid);

    $orcaltigrade = floatval($orcaltiinstance->grade);

    if (!empty($orcaltigrade) && isset($grades) && isset($grades->items[0]) && is_array($grades->items[0]->grades)) {
        foreach ($grades->items[0]->grades as $agrade) {
            $grade = $agrade->grade;
            if (isset($grade)) {
                return $grade / $orcaltigrade;
            }
        }
    }
}

function orcalti_delete_grade($orcaltiinstance, $userid) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $grade = new stdClass();
    $grade->userid   = $userid;
    $grade->rawgrade = null;

    $status = grade_update(ORCALTI_SOURCE, $orcaltiinstance->course, ORCALTI_ITEM_TYPE, ORCALTI_ITEM_MODULE, $orcaltiinstance->id, 0, $grade);

    return $status == GRADE_UPDATE_OK;
}

function orcalti_verify_message($key, $sharedsecrets, $body, $headers = null) {
    foreach ($sharedsecrets as $secret) {
        $signaturefailed = false;

        try {
            // TODO: Switch to core oauthlib once implemented - MDL-30149.
            orcalti\handle_oauth_body_post($key, $secret, $body, $headers);
        } catch (Exception $e) {
            debugging('ORCALTI message verification failed: '.$e->getMessage());
            $signaturefailed = true;
        }

        if (!$signaturefailed) {
            return $secret; // Return the secret used to sign the message).
        }
    }

    return false;
}

/**
 * Validate source ID from external request
 *
 * @param object $orcaltiinstance
 * @param object $parsed
 * @throws Exception
 */
function orcalti_verify_sourcedid($orcaltiinstance, $parsed) {
    $sourceid = orcalti_build_sourcedid($parsed->instanceid, $parsed->userid,
        $orcaltiinstance->servicesalt, $parsed->typeid, $parsed->launchid);

    if ($sourceid->hash != $parsed->sourcedidhash) {
        throw new Exception('SourcedId hash not valid');
    }
}

/**
 * Extend the ORCALTI services through the orcaltisource plugins
 *
 * @param stdClass $data ORCALTI request data
 * @return bool
 * @throws coding_exception
 */
function orcalti_extend_orcalti_services($data) {
    $plugins = get_plugin_list_with_function('orcaltisource', $data->messagetype);
    if (!empty($plugins)) {
        // There can only be one.
        if (count($plugins) > 1) {
            throw new coding_exception('More than one orcaltisource plugin handler found');
        }
        $data->xml = new SimpleXMLElement($data->body);
        $callback = current($plugins);
        call_user_func($callback, $data);

        return true;
    }
    return false;
}
