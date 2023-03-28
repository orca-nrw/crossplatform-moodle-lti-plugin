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
 * This file contains all the backup steps that will be used
 * by the backup_orcalti_activity_task
 *
 * @package mod_orcalti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete assignment structure for backup, with file and id annotations
 */
class backup_orcalti_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines structure of activity backup
     * @return backup_nested_element
     */
    protected function define_structure() {
        global $DB;

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $orcalti = new backup_nested_element('orcalti', array('id'), array(
            'name',
            'intro',
            'introformat',
            'timecreated',
            'timemodified',
            'typeid',
            'toolurl',
            'securetoolurl',
            'preferheight',
            'launchcontainer',
            'instructorchoicesendname',
            'instructorchoicesendemailaddr',
            'instructorchoiceacceptgrades',
            'instructorchoiceallowroster',
            'instructorchoiceallowsetting',
            'grade',
            'instructorcustomparameters',
            'debuglaunch',
            'showtitlelaunch',
            'showdescriptionlaunch',
            'icon',
            'secureicon',
            new encrypted_final_element('resourcekey'),
            new encrypted_final_element('password'),
            )
        );

        $orcaltitype = new backup_nested_element('orcaltitype', array('id'), array(
            'name',
            'baseurl',
            'tooldomain',
            'state',
            'course',
            'coursevisible',
            'orcaltiversion',
            'clientid',
            'toolproxyid',
            'enabledcapability',
            'parameter',
            'icon',
            'secureicon',
            'createdby',
            'timecreated',
            'timemodified',
            'description'
            )
        );

        $orcaltitypesconfigs = new backup_nested_element('orcaltitypesconfigs');
        $orcaltitypesconfig  = new backup_nested_element('orcaltitypesconfig', array('id'), array(
                'name',
                'value',
            )
        );
        $orcaltitypesconfigencrypted  = new backup_nested_element('orcaltitypesconfigencrypted', array('id'), array(
                'name',
                new encrypted_final_element('value'),
            )
        );

        $orcaltitoolproxy = new backup_nested_element('orcaltitoolproxy', array('id'));

        $orcaltitoolsettings = new backup_nested_element('orcaltitoolsettings');
        $orcaltitoolsetting  = new backup_nested_element('orcaltitoolsetting', array('id'), array(
                'settings',
                'timecreated',
                'timemodified',
            )
        );

        $orcaltisubmissions = new backup_nested_element('orcaltisubmissions');
        $orcaltisubmission = new backup_nested_element('orcaltisubmission', array('id'), array(
            'userid',
            'datesubmitted',
            'dateupdated',
            'gradepercent',
            'originalgrade',
            'launchid',
            'state'
        ));

        // Build the tree
        $orcalti->add_child($orcaltitype);
        $orcaltitype->add_child($orcaltitypesconfigs);
        $orcaltitypesconfigs->add_child($orcaltitypesconfig);
        $orcaltitypesconfigs->add_child($orcaltitypesconfigencrypted);
        $orcaltitype->add_child($orcaltitoolproxy);
        $orcaltitoolproxy->add_child($orcaltitoolsettings);
        $orcaltitoolsettings->add_child($orcaltitoolsetting);
        $orcalti->add_child($orcaltisubmissions);
        $orcaltisubmissions->add_child($orcaltisubmission);

        // Define sources.
        $orcaltirecord = $DB->get_record('orcalti', ['id' => $this->task->get_activityid()]);
        $orcalti->set_source_array([$orcaltirecord]);

        $orcaltitypedata = $this->retrieve_orcalti_type($orcaltirecord);
        $orcaltitype->set_source_array($orcaltitypedata ? [$orcaltitypedata] : []);

        if (isset($orcaltitypedata->baseurl)) {
            // Add type config values only if the type was backed up. Encrypt password and resourcekey.
            $params = [backup_helper::is_sqlparam($orcaltitypedata->id),
                backup_helper::is_sqlparam('password'),
                backup_helper::is_sqlparam('resourcekey')];
            $orcaltitypesconfig->set_source_sql("SELECT id, name, value
                FROM {lti_types_config}
                WHERE typeid = ? AND name <> ? AND name <> ?", $params);
            $orcaltitypesconfigencrypted->set_source_sql("SELECT id, name, value
                FROM {lti_types_config}
                WHERE typeid = ? AND (name = ? OR name = ?)", $params);
        }

        if (!empty($orcaltitypedata->toolproxyid)) {
            // If this is ORCALTI 2 tool add settings for the current activity.
            $orcaltitoolproxy->set_source_array([['id' => $orcaltitypedata->toolproxyid]]);
            $orcaltitoolsetting->set_source_sql("SELECT *
                FROM {orcalti_tool_settings}
                WHERE toolproxyid = ? AND course = ? AND coursemoduleid = ?",
                [backup_helper::is_sqlparam($orcaltitypedata->toolproxyid), backup::VAR_COURSEID, backup::VAR_MODID]);
        } else {
            $orcaltitoolproxy->set_source_array([]);
        }

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $orcaltisubmission->set_source_table('orcalti_submission', array('orcaltiid' => backup::VAR_ACTIVITYID));
        }

        // Define id annotations
        $orcaltitype->annotate_ids('user', 'createdby');
        $orcaltitype->annotate_ids('course', 'course');
        $orcaltisubmission->annotate_ids('user', 'userid');

        // Define file annotations.
        $orcalti->annotate_files('mod_orcalti', 'intro', null); // This file areas haven't itemid.

        // Add support for subplugin structures.
        $this->add_subplugin_structure('orcaltisource', $orcalti, true);
        $this->add_subplugin_structure('orcaltiservice', $orcalti, true);

        // Return the root element (orcalti), wrapped into standard activity structure.
        return $this->prepare_activity_structure($orcalti);
    }

    /**
     * Retrieves a record from {orcalti_type} table associated with the current activity
     *
     * Information about site tools is not returned because it is insecure to back it up,
     * only fields necessary for same-site tool matching are left in the record
     *
     * @param stdClass $orcaltirecord record from {orcalti} table
     * @return stdClass|null
     */
    protected function retrieve_orcalti_type($orcaltirecord) {
        global $DB;
        if (!$orcaltirecord->typeid) {
            return null;
        }

        $record = $DB->get_record('lti_types', ['id' => $orcaltirecord->typeid]);
        if ($record && $record->course == SITEID) {
            // Site ORCALTI types or registrations are not backed up except for their name (which is visible).
            // Predefined course types can be backed up.
            $allowedkeys = ['id', 'course', 'name', 'toolproxyid'];
            foreach ($record as $key => $value) {
                if (!in_array($key, $allowedkeys)) {
                    $record->$key = null;
                }
            }
        }

        return $record;
    }
}
