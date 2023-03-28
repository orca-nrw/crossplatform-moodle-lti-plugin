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
 * This file contains a library of functions and constants for the orcalti module
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

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in URL module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function orcalti_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;

        default:
            return null;
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted basicorcalti record
 **/
function orcalti_add_instance($orcalti, $mform) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

    if (!isset($orcalti->toolurl)) {
        $orcalti->toolurl = '';
    }

    orcalti_load_tool_if_cartridge($orcalti);

    $orcalti->timecreated = time();
    $orcalti->timemodified = $orcalti->timecreated;
    $orcalti->servicesalt = uniqid('', true);
    if (!isset($orcalti->typeid)) {
        $orcalti->typeid = null;
    }

    orcalti_force_type_config_settings($orcalti, orcalti_get_type_config_by_instance($orcalti));

    if (empty($orcalti->typeid) && isset($orcalti->urlmatchedtypeid)) {
        $orcalti->typeid = $orcalti->urlmatchedtypeid;
    }

    if (!isset($orcalti->instructorchoiceacceptgrades) || $orcalti->instructorchoiceacceptgrades != ORCALTI_SETTING_ALWAYS) {
        // The instance does not accept grades back from the provider, so set to "No grade" value 0.
        $orcalti->grade = 0;
    }

    $orcalti->id = $DB->insert_record('orcalti', $orcalti);

    if (isset($orcalti->instructorchoiceacceptgrades) && $orcalti->instructorchoiceacceptgrades == ORCALTI_SETTING_ALWAYS) {
        if (!isset($orcalti->cmidnumber)) {
            $orcalti->cmidnumber = '';
        }

        orcalti_grade_item_update($orcalti);
    }

    $services = orcalti_get_services();
    foreach ($services as $service) {
        $service->instance_added( $orcalti );
    }

    $completiontimeexpected = !empty($orcalti->completionexpected) ? $orcalti->completionexpected : null;
    \core_completion\api::update_completion_date_event($orcalti->coursemodule, 'orcalti', $orcalti->id, $completiontimeexpected);

    return $orcalti->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function orcalti_update_instance($orcalti, $mform) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

    orcalti_load_tool_if_cartridge($orcalti);

    $orcalti->timemodified = time();
    $orcalti->id = $orcalti->instance;

    if (!isset($orcalti->showtitlelaunch)) {
        $orcalti->showtitlelaunch = 0;
    }

    if (!isset($orcalti->showdescriptionlaunch)) {
        $orcalti->showdescriptionlaunch = 0;
    }

    orcalti_force_type_config_settings($orcalti, orcalti_get_type_config_by_instance($orcalti));

    if (isset($orcalti->instructorchoiceacceptgrades) && $orcalti->instructorchoiceacceptgrades == ORCALTI_SETTING_ALWAYS) {
        orcalti_grade_item_update($orcalti);
    } else {
        // Instance is no longer accepting grades from Provider, set grade to "No grade" value 0.
        $orcalti->grade = 0;
        $orcalti->instructorchoiceacceptgrades = 0;

        orcalti_grade_item_delete($orcalti);
    }

    if (!isset($orcalti->typeid)) {
        $orcalti->typeid = null;
    }
    
    if ($orcalti->typeid == 0 && isset($orcalti->urlmatchedtypeid)) {
        $orcalti->typeid = $orcalti->urlmatchedtypeid;
    }

    $services = orcalti_get_services();
    foreach ($services as $service) {
        $service->instance_updated( $orcalti );
    }

    $completiontimeexpected = !empty($orcalti->completionexpected) ? $orcalti->completionexpected : null;
    \core_completion\api::update_completion_date_event($orcalti->coursemodule, 'orcalti', $orcalti->id, $completiontimeexpected);

    return $DB->update_record('orcalti', $orcalti);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function orcalti_delete_instance($id) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

    if (! $basicorcalti = $DB->get_record("orcalti", array("id" => $id))) {
        return false;
    }

    $result = true;

    // Delete any dependent records here.
    orcalti_grade_item_delete($basicorcalti);

    $orcaltitype = $DB->get_record('lti_types', array('id' => $basicorcalti->typeid));
    if ($orcaltitype) {
        $DB->delete_records('orcalti_tool_settings',
            array('toolproxyid' => $orcaltitype->toolproxyid, 'course' => $basicorcalti->course, 'coursemoduleid' => $id));
    }

    $cm = get_coursemodule_from_instance('orcalti', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'orcalti', $id, null);

    // We must delete the module record after we delete the grade item.
    if ($DB->delete_records("orcalti", array("id" => $basicorcalti->id)) ) {
        $services = orcalti_get_services();
        foreach ($services as $service) {
            $service->instance_deleted( $id );
        }
        return true;
    }
    return false;

}

/**
 * Return the preconfigured tools which are configured for inclusion in the activity picker.
 *
 * @param \core_course\local\entity\content_item $defaultmodulecontentitem reference to the content item for the ORCALTI module.
 * @param \stdClass $user the user object, to use for cap checks if desired.
 * @param stdClass $course the course to scope items to.
 * @return array the array of content items.
 */
function orcalti_get_course_content_items(\core_course\local\entity\content_item $defaultmodulecontentitem, \stdClass $user,
        \stdClass $course) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

    $types = [];

    // The 'External tool' entry (the main module content item), should always take the id of 1.
    if (has_capability('mod/orcalti:addmanualinstance', context_course::instance($course->id), $user)) {
        $types = [new \core_course\local\entity\content_item(
            1,
            $defaultmodulecontentitem->get_name(),
            $defaultmodulecontentitem->get_title(),
            $defaultmodulecontentitem->get_link(),
            $defaultmodulecontentitem->get_icon(),
            $defaultmodulecontentitem->get_help(),
            $defaultmodulecontentitem->get_archetype(),
            $defaultmodulecontentitem->get_component_name(),
            $defaultmodulecontentitem->get_purpose()
        )];
    }

    // Other, preconfigured tools take their own id + 1, so we'll never clash with the module's entry.
    $preconfiguredtools = orcalti_get_configured_types($course->id, $defaultmodulecontentitem->get_link()->param('sr'));
    foreach ($preconfiguredtools as $preconfiguredtool) {

        // Append the help link to the help text.
        if (isset($preconfiguredtool->help)) {
            if (isset($preconfiguredtool->helplink)) {
                $linktext = get_string('morehelp');
                $preconfiguredtool->help .= html_writer::tag('div',
                    $OUTPUT->doc_link($preconfiguredtool->helplink, $linktext, true), ['class' => 'helpdoclink']);
            }
        } else {
            $preconfiguredtool->help = '';
        }

        $types[] = new \core_course\local\entity\content_item(
            $preconfiguredtool->id + 1,
            $preconfiguredtool->name,
            new \core_course\local\entity\string_title($preconfiguredtool->title),
            $preconfiguredtool->link,
            $preconfiguredtool->icon,
            $preconfiguredtool->help,
            $defaultmodulecontentitem->get_archetype(),
            $defaultmodulecontentitem->get_component_name(),
            $defaultmodulecontentitem->get_purpose()
        );
    }
    return $types;
}

/**
 * Return all content items which can be added to any course.
 *
 * @param \core_course\local\entity\content_item $defaultmodulecontentitem
 * @return array the array of content items.
 */
function mod_orcalti_get_all_content_items(\core_course\local\entity\content_item $defaultmodulecontentitem): array {
    global $OUTPUT, $CFG;
    require_once($CFG->dirroot . '/mod/orcalti/locallib.php'); // For access to constants.

    // The 'External tool' entry (the main module content item), should always take the id of 1.
    $types = [new \core_course\local\entity\content_item(
        1,
        $defaultmodulecontentitem->get_name(),
        $defaultmodulecontentitem->get_title(),
        $defaultmodulecontentitem->get_link(),
        $defaultmodulecontentitem->get_icon(),
        $defaultmodulecontentitem->get_help(),
        $defaultmodulecontentitem->get_archetype(),
        $defaultmodulecontentitem->get_component_name(),
        $defaultmodulecontentitem->get_purpose()
    )];

    foreach (orcalti_get_orcalti_types() as $orcaltitype) {
        if ($orcaltitype->coursevisible != ORCALTI_COURSEVISIBLE_ACTIVITYCHOOSER) {
            continue;
        }
        $type           = new stdClass();
        $type->id       = $orcaltitype->id;
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->name     = 'orcalti_type_' . $orcaltitype->id;
        // Clean the name. We don't want tags here.
        $type->title    = clean_param($orcaltitype->name, PARAM_NOTAGS);
        $trimmeddescription = trim($orcaltitype->description);
        $type->help = '';
        if ($trimmeddescription != '') {
            // Clean the description. We don't want tags here.
            $type->help     = clean_param($trimmeddescription, PARAM_NOTAGS);
            $type->helplink = get_string('modulename_shortcut_link', 'orcalti');
        }
        if (empty($orcaltitype->icon)) {
            $type->icon = $OUTPUT->pix_icon('monologo', '', 'orcalti', array('class' => 'icon'));
        } else {
            $type->icon = html_writer::empty_tag('img', array('src' => $orcaltitype->icon, 'alt' => $orcaltitype->name, 'class' => 'icon'));
        }
        $type->link = new moodle_url('/course/modedit.php', array('add' => 'orcalti', 'return' => 0, 'typeid' => $orcaltitype->id));

        $types[] = new \core_course\local\entity\content_item(
            $type->id + 1,
            $type->name,
            new \core_course\local\entity\string_title($type->title),
            $type->link,
            $type->icon,
            $type->help,
            $defaultmodulecontentitem->get_archetype(),
            $defaultmodulecontentitem->get_component_name(),
            $defaultmodulecontentitem->get_purpose()
        );
    }

    return $types;
}

/**
 * Given a coursemodule object, this function returns the extra
 * information needed to print this activity in various places.
 * For this module we just need to support external urls as
 * activity icons
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info info
 */
function orcalti_get_coursemodule_info($coursemodule) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

    if (!$orcalti = $DB->get_record('orcalti', array('id' => $coursemodule->instance),
            'icon, secureicon, intro, introformat, name, typeid, toolurl, launchcontainer')) {
        return null;
    }

    $info = new cached_cm_info();

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('orcalti', $orcalti, $coursemodule->id, false);
    }

    if (!empty($orcalti->typeid)) {
        $toolconfig = orcalti_get_type_config($orcalti->typeid);
    } else if ($tool = orcalti_get_tool_by_url_match($orcalti->toolurl)) {
        $toolconfig = orcalti_get_type_config($tool->id);
    } else {
        $toolconfig = array();
    }

    // We want to use the right icon based on whether the
    // current page is being requested over http or https.
    if (orcalti_request_is_using_ssl() &&
        (!empty($orcalti->secureicon) || (isset($toolconfig['secureicon']) && !empty($toolconfig['secureicon'])))) {
        if (!empty($orcalti->secureicon)) {
            $info->iconurl = new moodle_url($orcalti->secureicon);
        } else {
            $info->iconurl = new moodle_url($toolconfig['secureicon']);
        }
    } else if (!empty($orcalti->icon)) {
        $info->iconurl = new moodle_url($orcalti->icon);
    } else if (isset($toolconfig['icon']) && !empty($toolconfig['icon'])) {
        $info->iconurl = new moodle_url($toolconfig['icon']);
    }

    // Does the link open in a new window?
    $launchcontainer = orcalti_get_launch_container($orcalti, $toolconfig);
    if ($launchcontainer == ORCALTI_LAUNCH_CONTAINER_WINDOW) {
        $launchurl = new moodle_url('/mod/orcalti/launch.php', array('id' => $coursemodule->id));
        $info->onclick = "window.open('" . $launchurl->out(false) . "', 'orcalti-".$coursemodule->id."'); return false;";
    }

    $info->name = $orcalti->name;

    return $info;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @TODO: implement this moodle function (if needed)
 **/
function orcalti_user_outline($course, $user, $mod, $basicorcalti) {
    return null;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @TODO: implement this moodle function (if needed)
 **/
function orcalti_user_complete($course, $user, $mod, $basicorcalti) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in basicorcalti activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @uses $CFG
 * @return boolean
 * @TODO: implement this moodle function
 **/
function orcalti_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false.
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 **/
function orcalti_cron () {
    return true;
}

/**
 * Must return an array of grades for a given instance of this module,
 * indexed by user.  It also returns a maximum allowed grade.
 *
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $basicorcaltiid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 *
 * @TODO: implement this moodle function (if needed)
 **/
function orcalti_grades($basicorcaltiid) {
    return null;
}

/**
 * @deprecated since Moodle 3.8
 */
function orcalti_scale_used() {
    throw new coding_exception('orcalti_scale_used() can not be used anymore. Plugins can implement ' .
        '<modname>_scale_used_anywhere, all implementations of <modname>_scale_used are now ignored');
}

/**
 * Checks if scale is being used by any instance of basicorcalti.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any basicorcalti
 *
 */
function orcalti_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('orcalti', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function orcalti_install() {
     return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function orcalti_uninstall() {
    return true;
}

/**
 * Returns available Basic ORCALTI types
 *
 * @return array of basicORCALTI types
 */
function orcalti_get_orcalti_types() {
    global $DB;

    return $DB->get_records('lti_types', null, 'state DESC, timemodified DESC');
}

/**
 * Returns available Basic ORCALTI types that match the given
 * tool proxy id
 *
 * @param int $toolproxyid Tool proxy id
 * @return array of basicORCALTI types
 */
function orcalti_get_orcalti_types_from_proxy_id($toolproxyid) {
    global $DB;

    return $DB->get_records('lti_types', array('toolproxyid' => $toolproxyid), 'state DESC, timemodified DESC');
}

/**
 * Create grade item for given basicorcalti
 *
 * @category grade
 * @param object $basicorcalti object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function orcalti_grade_item_update($basicorcalti, $grades = null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot.'/mod/orcalti/servicelib.php');

    if (!orcalti_accepts_grades($basicorcalti)) {
        return 0;
    }

    $params = array('itemname' => $basicorcalti->name, 'idnumber' => $basicorcalti->cmidnumber);

    if ($basicorcalti->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $basicorcalti->grade;
        $params['grademin']  = 0;

    } else if ($basicorcalti->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$basicorcalti->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT; // Allow text comments only.
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/orcalti', $basicorcalti->course, 'mod', 'orcalti', $basicorcalti->id, 0, $grades, $params);
}

/**
 * Update activity grades
 *
 * @param stdClass $basicorcalti The ORCALTI instance
 * @param int      $userid Specific user only, 0 means all.
 * @param bool     $nullifnone Not used
 */
function orcalti_update_grades($basicorcalti, $userid=0, $nullifnone=true) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/orcalti/servicelib.php');
    // ORCALTI doesn't have its own grade table so the only thing to do is update the grade item.
    if (orcalti_accepts_grades($basicorcalti)) {
        orcalti_grade_item_update($basicorcalti);
    }
}

/**
 * Delete grade item for given basicorcalti
 *
 * @category grade
 * @param object $basicorcalti object
 * @return object basicorcalti
 */
function orcalti_grade_item_delete($basicorcalti) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/orcalti', $basicorcalti->course, 'mod', 'orcalti', $basicorcalti->id, 0, null, array('deleted' => 1));
}

/**
 * Log post actions
 *
 * @return array
 */
function orcalti_get_post_actions() {
    return array();
}

/**
 * Log view actions
 *
 * @return array
 */
function orcalti_get_view_actions() {
    return array('view all', 'view');
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $orcalti        orcalti object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function orcalti_view($orcalti, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $orcalti->id
    );

    $event = \mod_orcalti\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('orcalti', $orcalti);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function orcalti_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER;

    $updates = course_check_module_updates_since($cm, $from, array(), $filter);

    // Check if there is a new submission.
    $updates->submissions = (object) array('updated' => false);
    $select = 'orcaltiid = :id AND userid = :userid AND (datesubmitted > :since1 OR dateupdated > :since2)';
    $params = array('id' => $cm->instance, 'userid' => $USER->id, 'since1' => $from, 'since2' => $from);
    $submissions = $DB->get_records_select('orcalti_submission', $select, $params, '', 'id');
    if (!empty($submissions)) {
        $updates->submissions->updated = true;
        $updates->submissions->itemids = array_keys($submissions);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/orcalti:manage', $cm->context)) {
        $select = 'orcaltiid = :id AND (datesubmitted > :since1 OR dateupdated > :since2)';
        $params = array('id' => $cm->instance, 'since1' => $from, 'since2' => $from);

        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers, SQL_PARAMS_NAMED);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->usersubmissions = (object) array('updated' => false);
        $submissions = $DB->get_records_select('orcalti_submission', $select, $params, '', 'id');
        if (!empty($submissions)) {
            $updates->usersubmissions->updated = true;
            $updates->usersubmissions->itemids = array_keys($submissions);
        }
    }

    return $updates;
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_orcalti_get_fontawesome_icon_map() {
    return [
        'mod_orcalti:warning' => 'fa-exclamation text-warning',
    ];
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_orcalti_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory,
                                                      int $userid = 0) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['orcalti'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/orcalti/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
