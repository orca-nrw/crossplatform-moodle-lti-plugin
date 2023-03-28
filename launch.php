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
//
// This file is part of BasicORCALTI4Moodle
//
// BasicORCALTI4Moodle is an IMS BasicORCALTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicORCALTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicORCALTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicORCALTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicORCALTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleORCALTI consumer for Moodle is an implementation of the early specification of ORCALTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicORCALTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file contains all necessary code to view a orcalti activity instance
 *
 * @package mod_orcalti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/orcalti/lib.php');
require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

$cmid = required_param('id', PARAM_INT); // Course Module ID.
$triggerview = optional_param('triggerview', 1, PARAM_BOOL);
$action = optional_param('action', '', PARAM_TEXT);
$foruserid = optional_param('user', 0, PARAM_INT);

$cm = get_coursemodule_from_id('orcalti', $cmid, 0, false, MUST_EXIST);
$orcalti = $DB->get_record('orcalti', array('id' => $cm->instance), '*', MUST_EXIST);

$typeid = $orcalti->typeid;
if (empty($typeid) && ($tool = orcalti_get_tool_by_url_match($orcalti->toolurl))) {
    $typeid = $tool->id;
}
if ($typeid) {
    $config = orcalti_get_type_type_config($typeid);    
    if ($config->lti_ltiversion === ORCALTI_VERSION_1P3) {
        if (!isset($SESSION->lti_initiatelogin_status)) {
            $msgtype = 'basic-lti-launch-request';
            if ($action === 'gradeReport') {
                $msgtype = 'LtiSubmissionReviewRequest';
            }
            echo orcalti_initiate_login($cm->course, $cmid, $orcalti, $config, $msgtype, '', '', $foruserid);
            exit;
        } else {
            unset($SESSION->lti_initiatelogin_status);
        }
    }
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/orcalti:view', $context);

// Completion and trigger events.
if ($triggerview) {
    orcalti_view($orcalti, $course, $cm, $context);
}

$orcalti->cmid = $cm->id;
orcalti_launch_tool($orcalti, $foruserid);
