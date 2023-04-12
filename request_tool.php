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
 * Submits a request to administrators to add a tool configuration for the requested site.
 *
 * @package mod_orcalti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/orcalti/lib.php');
require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

$instanceid = required_param('instanceid', PARAM_INT);

$orcalti = $DB->get_record('orcalti', array('id' => $instanceid));
$course = $DB->get_record('course', array('id' => $orcalti->course));
$cm = get_coursemodule_from_instance('orcalti', $orcalti->id, $orcalti->course, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course);

require_sesskey();

require_capability('mod/orcalti:requesttooladd', context_course::instance($orcalti->course));

$baseurl = orcalti_get_domain_from_url($orcalti->toolurl);

$url = new moodle_url('/mod/orcalti/request_tool.php', array('instanceid' => $instanceid));
$PAGE->set_url($url);

$pagetitle = strip_tags($course->shortname);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($orcalti->name, true, array('context' => $context)));

// Add a tool type if one does not exist already.
if (!orcalti_get_tool_by_url_match($orcalti->toolurl, $orcalti->course, ORCALTI_TOOL_STATE_ANY)) {
    // There are no tools (active, pending, or rejected) for the launch URL. Create a new pending tool.
    $tooltype = new stdClass();
    $toolconfig = new stdClass();

    $toolconfig->orcalti_toolurl = orcalti_get_domain_from_url($orcalti->toolurl);
    $toolconfig->orcalti_typename = $toolconfig->orcalti_toolurl;

    orcalti_add_type($tooltype, $toolconfig);

    echo get_string('orcalti_tool_request_added', 'orcalti');
} else {
    echo get_string('orcalti_tool_request_existing', 'orcalti');
}

echo $OUTPUT->footer();
