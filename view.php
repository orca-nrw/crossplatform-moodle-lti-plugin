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

require_once('../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/mod/orcalti/lib.php');
require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$l  = optional_param('l', 0, PARAM_INT);  // orcalti ID.
$action = optional_param('action', '', PARAM_TEXT);
$foruserid = optional_param('user', 0, PARAM_INT);
$forceview = optional_param('forceview', 0, PARAM_BOOL);

if ($l) {  // Two ways to specify the module.
    $orcalti = $DB->get_record('orcalti', array('id' => $l), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('orcalti', $orcalti->id, $orcalti->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('orcalti', $id, 0, false, MUST_EXIST);
    $orcalti = $DB->get_record('orcalti', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$typeid = $orcalti->typeid;
if (empty($typeid) && ($tool = orcalti_get_tool_by_url_match($orcalti->toolurl))) {
    $typeid = $tool->id;
}
if ($typeid) {
    $toolconfig = orcalti_get_type_config($typeid);
    $toolurl = $toolconfig['toolurl'];
} else {
    $toolconfig = array();
    $toolurl = $orcalti->toolurl;
}

$PAGE->set_cm($cm, $course); // Set's up global $COURSE.
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

require_login($course, true, $cm);
require_capability('mod/orcalti:view', $context);

if (!empty($foruserid) && (int)$foruserid !== (int)$USER->id) {
    require_capability('gradereport/grader:view', $context);
}

$url = new moodle_url('/mod/orcalti/view.php', array('id' => $cm->id));
$PAGE->set_url($url);

$launchcontainer = orcalti_get_launch_container($orcalti, $toolconfig);

if ($launchcontainer == ORCALTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS) {
    $PAGE->set_pagelayout('incourse');
    $PAGE->blocks->show_only_fake_blocks(); // Disable blocks for layouts which do include pre-post blocks.
} else if ($launchcontainer == ORCALTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW) {
    if (!$forceview) {
        $url = new moodle_url('/mod/orcalti/launch.php', array('id' => $cm->id));
        redirect($url);
    }
} else { // Handles ORCALTI_LAUNCH_CONTAINER_DEFAULT, ORCALTI_LAUNCH_CONTAINER_EMBED, ORCALTI_LAUNCH_CONTAINER_WINDOW.
    $PAGE->set_pagelayout('incourse');
}

orcalti_view($orcalti, $course, $cm, $context);

$pagetitle = strip_tags($course->shortname.': '.format_string($orcalti->name));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

$activityheader = $PAGE->activityheader;
if (!$orcalti->showtitlelaunch) {
    $header['title'] = '';
}
if (!$orcalti->showdescriptionlaunch) {
    $header['description'] = '';
}
$activityheader->set_attrs($header ?? []);

// Print the page header.
echo $OUTPUT->header();

if ($typeid) {
    $config = orcalti_get_type_type_config($typeid);
} else {
    $config = new stdClass();
    $config->lti_ltiversion = ORCALTI_VERSION_1;
}
$launchurl = new moodle_url('/mod/orcalti/launch.php', ['id' => $cm->id, 'triggerview' => 0]);
if ($action) {
    $launchurl->param('action', $action);;
}
if ($foruserid) {
    $launchurl->param('user', $foruserid);;
}
unset($SESSION->lti_initiatelogin_status);
if (($launchcontainer == ORCALTI_LAUNCH_CONTAINER_WINDOW)) {
    if (!$forceview) {
        echo "<script language=\"javascript\">//<![CDATA[\n";
        echo "window.open('{$launchurl->out(true)}','orcalti-$cm->id');";
        echo "//]]\n";
        echo "</script>\n";
        echo "<p>".get_string("basicorcalti_in_new_window", "orcalti")."</p>\n";
    }
    echo html_writer::start_tag('p');
    echo html_writer::link($launchurl->out(false), get_string("basicorcalti_in_new_window_open", "orcalti"), array('target' => '_blank'));
    echo html_writer::end_tag('p');
} else {
    $content = '';
    // Build the allowed URL, since we know what it will be from $orcalti->toolurl,
    // If the specified toolurl is invalid the iframe won't load, but we still want to avoid parse related errors here.
    // So we set an empty default allowed url, and only build a real one if the parse is successful.
    $orcaltiallow = '';
    $urlparts = parse_url($toolurl);
    if ($urlparts && array_key_exists('scheme', $urlparts) && array_key_exists('host', $urlparts)) {
        $orcaltiallow = $urlparts['scheme'] . '://' . $urlparts['host'];
        // If a port has been specified we append that too.
        if (array_key_exists('port', $urlparts)) {
            $orcaltiallow .= ':' . $urlparts['port'];
        }
    }

    // Request the launch content with an iframe tag.
    $attributes = [];
    $attributes['id'] = "contentframe";
    $attributes['height'] = '600px';
    $attributes['width'] = '100%';
    $attributes['src'] = $launchurl;
    $attributes['allow'] = "microphone $orcaltiallow; " .
        "camera $orcaltiallow; " .
        "geolocation $orcaltiallow; " .
        "midi $orcaltiallow; " .
        "encrypted-media $orcaltiallow; " .
        "autoplay $orcaltiallow";
    $attributes['allowfullscreen'] = 1;
    $iframehtml = html_writer::tag('iframe', $content, $attributes);
    echo $iframehtml;


    // Output script to make the iframe tag be as large as possible.
    $resize = '
        <script type="text/javascript">
        //<![CDATA[
            YUI().use("node", "event", function(Y) {
                var doc = Y.one("body");
                var frame = Y.one("#contentframe");
                var padding = 15; //The bottom of the iframe wasn\'t visible on some themes. Probably because of border widths, etc.
                var lastHeight;
                var resize = function(e) {
                    var viewportHeight = doc.get("winHeight");
                    if(lastHeight !== Math.min(doc.get("docHeight"), viewportHeight)){
                        frame.setStyle("height", viewportHeight - frame.getY() - padding + "px");
                        lastHeight = Math.min(doc.get("docHeight"), doc.get("winHeight"));
                    }
                };

                resize();

                Y.on("windowresize", resize);
            });
        //]]
        </script>
';

    echo $resize;
}

// Finish the page.
echo $OUTPUT->footer();
