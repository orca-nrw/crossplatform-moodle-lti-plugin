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
 * This page allows instructors to configure course level tool providers.
 *
 * @package mod_orcalti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/orcalti/edit_form.php');
require_once($CFG->dirroot.'/mod/orcalti/lib.php');

$courseid = required_param('course', PARAM_INT);

require_login($courseid, false);
$url = new moodle_url('/mod/orcalti/instructor_edit_tool_type.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('edittype', 'mod_orcalti'));

$action = optional_param('action', null, PARAM_TEXT);
$typeid = optional_param('typeid', null, PARAM_INT);

require_sesskey();

require_capability('mod/orcalti:addcoursetool', context_course::instance($courseid));

if (!empty($typeid)) {
    $type = orcalti_get_type($typeid);
    if ($type->course != $courseid) {
        throw new Exception('You do not have permissions to edit this tool type.');
        die;
    }
}

// Delete action is called via ajax.
if ($action == 'delete') {
    orcalti_delete_type($typeid);
    die;
}

// Add a timeout for closing for behat so it can check for errors before switching back to the main window.
$timeout = 0;
if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
    $timeout = 2000;
}

echo $OUTPUT->header();

if ($action == 'edit') {
    $type = orcalti_get_type_type_config($typeid);
} else {
    $type = new stdClass();
    $type->orcalti_clientid = null;
}

$form = new mod_orcalti_edit_types_form($url, (object)array('id' => $typeid, 'clientid' => $type->orcalti_clientid));

// If the user just opened an add or edit form.
if ($action == 'add' || $action == 'edit') {
    if ($action == 'edit') {
        $form->set_data($type);
    }
    echo $OUTPUT->heading(get_string('toolsetup', 'orcalti'));
    $form->display();
} else {
    $script = '';
    $closewindow = <<<EOF
        setTimeout(function() {
            window.close();
        }, $timeout);
EOF;

    if ($data = $form->get_data()) {
        $type = new stdClass();

        if (!empty($typeid)) {
            $type->id = $typeid;

            orcalti_load_type_if_cartridge($data);

            orcalti_update_type($type, $data);

            $fromdb = orcalti_get_type($typeid);
            $json = json_encode($fromdb);

            // Output script to update the calling window.
            $script = <<<EOF
                window.opener.M.mod_orcalti.editor.updateToolType({$json});
EOF;
        } else {
            $type->state = ORCALTI_TOOL_STATE_CONFIGURED;
            $type->course = $COURSE->id;

            orcalti_load_type_if_cartridge($data);

            $id = orcalti_add_type($type, $data);

            $fromdb = orcalti_get_type($id);
            $json = json_encode($fromdb);

            // Output script to update the calling window.
            $script = <<<EOF
                window.opener.M.mod_orcalti.editor.addToolType({$json});
EOF;
        }
        echo html_writer::script($script . $closewindow);
    } else if ($form->is_cancelled()) {
        echo html_writer::script($closewindow);
    } else {
        echo $OUTPUT->heading(get_string('toolsetup', 'orcalti'));
        $form->display();
    }
}

echo $OUTPUT->footer();
