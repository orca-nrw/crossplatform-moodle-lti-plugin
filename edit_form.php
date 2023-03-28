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
 * This file defines de main basicorcalti configuration form
 *
 * @package mod_orcalti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Charles Severance
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

/**
 * ORCALTI Edit Form
 *
 * @package    mod_orcalti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_orcalti_edit_types_form extends moodleform {

    /**
     * Define this form.
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform    =& $this->_form;

        $istool = $this->_customdata && isset($this->_customdata->istool) && $this->_customdata->istool;
        $typeid = $this->_customdata->id ?? '';
        $clientid = $this->_customdata->clientid ?? '';

        // Add basicorcalti elements.
        $mform->addElement('header', 'setup', get_string('tool_settings', 'orcalti'));

        $mform->addElement('text', 'orcalti_typename', get_string('typename', 'orcalti'));
        $mform->setType('orcalti_typename', PARAM_TEXT);
        $mform->addHelpButton('orcalti_typename', 'typename', 'orcalti');
        $mform->addRule('orcalti_typename', null, 'required', null, 'client');

        $mform->addElement('text', 'orcalti_toolurl', get_string('toolurl', 'orcalti'), array('size' => '64'));
        $mform->setType('orcalti_toolurl', PARAM_URL);
        $mform->addHelpButton('orcalti_toolurl', 'toolurl', 'orcalti');

        $mform->addElement('textarea', 'orcalti_description', get_string('tooldescription', 'orcalti'), array('rows' => 4, 'cols' => 60));
        $mform->setType('orcalti_description', PARAM_TEXT);
        $mform->addHelpButton('orcalti_description', 'tooldescription', 'orcalti');
        if (!$istool) {
            $mform->addRule('orcalti_toolurl', null, 'required', null, 'client');
        } else {
            $mform->disabledIf('orcalti_toolurl', null);
        }

        if (!$istool) {
            $options = array(
                ORCALTI_VERSION_1 => get_string('oauthsecurity', 'orcalti'),
                ORCALTI_VERSION_1P3 => get_string('jwtsecurity', 'orcalti'),
            );
            $mform->addElement('select', 'orcalti_ltiversion', get_string('orcaltiversion', 'orcalti'), $options);
            $mform->setType('orcalti_ltiversion', PARAM_TEXT);
            $mform->addHelpButton('orcalti_ltiversion', 'orcaltiversion', 'orcalti');
            $mform->setDefault('orcalti_ltiversion', ORCALTI_VERSION_1);

            $mform->addElement('text', 'orcalti_resourcekey', get_string('resourcekey_admin', 'orcalti'));
            $mform->setType('orcalti_resourcekey', PARAM_TEXT);
            $mform->addHelpButton('orcalti_resourcekey', 'resourcekey_admin', 'orcalti');
            $mform->hideIf('orcalti_resourcekey', 'orcalti_ltiversion', 'eq', ORCALTI_VERSION_1P3);
            $mform->setForceLtr('orcalti_resourcekey');

            $mform->addElement('passwordunmask', 'orcalti_password', get_string('password_admin', 'orcalti'));
            $mform->setType('orcalti_password', PARAM_RAW);
            $mform->addHelpButton('orcalti_password', 'password_admin', 'orcalti');
            $mform->hideIf('orcalti_password', 'orcalti_ltiversion', 'eq', ORCALTI_VERSION_1P3);

            if (!empty($typeid)) {
                $mform->addElement('text', 'orcalti_clientid_disabled', get_string('clientidadmin', 'orcalti'));
                $mform->setType('orcalti_clientid_disabled', PARAM_TEXT);
                $mform->addHelpButton('orcalti_clientid_disabled', 'clientidadmin', 'orcalti');
                $mform->hideIf('orcalti_clientid_disabled', 'orcalti_ltiversion', 'neq', ORCALTI_VERSION_1P3);
                $mform->disabledIf('orcalti_clientid_disabled', null);
                $mform->setForceLtr('orcalti_clientid_disabled');
                $mform->addElement('hidden', 'orcalti_clientid');
                $mform->setType('orcalti_clientid', PARAM_TEXT);
            }

            $keyoptions = [
                ORCALTI_RSA_KEY => get_string('keytype_rsa', 'orcalti'),
                ORCALTI_JWK_KEYSET => get_string('keytype_keyset', 'orcalti'),
            ];
            $mform->addElement('select', 'orcalti_keytype', get_string('keytype', 'orcalti'), $keyoptions);
            $mform->setType('orcalti_keytype', PARAM_TEXT);
            $mform->addHelpButton('orcalti_keytype', 'keytype', 'orcalti');
            $mform->setDefault('orcalti_keytype', ORCALTI_JWK_KEYSET);
            $mform->hideIf('orcalti_keytype', 'orcalti_ltiversion', 'neq', ORCALTI_VERSION_1P3);

            $mform->addElement('textarea', 'orcalti_publickey', get_string('publickey', 'orcalti'), ['rows' => 8, 'cols' => 60]);
            $mform->setType('orcalti_publickey', PARAM_TEXT);
            $mform->addHelpButton('orcalti_publickey', 'publickey', 'orcalti');
            $mform->hideIf('orcalti_publickey', 'orcalti_keytype', 'neq', ORCALTI_RSA_KEY);
            $mform->hideIf('orcalti_publickey', 'orcalti_ltiversion', 'neq', ORCALTI_VERSION_1P3);
            $mform->setForceLtr('orcalti_publickey');

            $mform->addElement('text', 'orcalti_publickeyset', get_string('publickeyset', 'orcalti'), ['size' => '64']);
            $mform->setType('orcalti_publickeyset', PARAM_TEXT);
            $mform->addHelpButton('orcalti_publickeyset', 'publickeyset', 'orcalti');
            $mform->hideIf('orcalti_publickeyset', 'orcalti_keytype', 'neq', ORCALTI_JWK_KEYSET);
            $mform->hideIf('orcalti_publickeyset', 'orcalti_ltiversion', 'neq', ORCALTI_VERSION_1P3);
            $mform->setForceLtr('orcalti_publickeyset');

            $mform->addElement('text', 'orcalti_initiatelogin', get_string('initiatelogin', 'orcalti'), array('size' => '64'));
            $mform->setType('orcalti_initiatelogin', PARAM_URL);
            $mform->addHelpButton('orcalti_initiatelogin', 'initiatelogin', 'orcalti');
            $mform->hideIf('orcalti_initiatelogin', 'orcalti_ltiversion', 'neq', ORCALTI_VERSION_1P3);

            $mform->addElement('textarea', 'orcalti_redirectionuris', get_string('redirectionuris', 'orcalti'),
                array('rows' => 3, 'cols' => 60));
            $mform->setType('orcalti_redirectionuris', PARAM_TEXT);
            $mform->addHelpButton('orcalti_redirectionuris', 'redirectionuris', 'orcalti');
            $mform->hideIf('orcalti_redirectionuris', 'orcalti_ltiversion', 'neq', ORCALTI_VERSION_1P3);
            $mform->setForceLtr('orcalti_redirectionuris');
        }

        if ($istool) {
            $mform->addElement('textarea', 'orcalti_parameters', get_string('parameter', 'orcalti'), array('rows' => 4, 'cols' => 60));
            $mform->setType('orcalti_parameters', PARAM_TEXT);
            $mform->addHelpButton('orcalti_parameters', 'parameter', 'orcalti');
            $mform->disabledIf('orcalti_parameters', null);
            $mform->setForceLtr('orcalti_parameters');
        }

        $mform->addElement('textarea', 'orcalti_customparameters', get_string('custom', 'orcalti'), array('rows' => 4, 'cols' => 60));
        $mform->setType('orcalti_customparameters', PARAM_TEXT);
        $mform->addHelpButton('orcalti_customparameters', 'custom', 'orcalti');
        $mform->setForceLtr('orcalti_customparameters');

        if (!empty($this->_customdata->isadmin)) {
            $options = array(
                ORCALTI_COURSEVISIBLE_NO => get_string('show_in_course_no', 'orcalti'),
                ORCALTI_COURSEVISIBLE_PRECONFIGURED => get_string('show_in_course_preconfigured', 'orcalti'),
                ORCALTI_COURSEVISIBLE_ACTIVITYCHOOSER => get_string('show_in_course_activity_chooser', 'orcalti'),
            );
            if ($istool) {
                // ORCALTI2 tools can not be matched by URL, they have to be either in preconfigured tools or in activity chooser.
                unset($options[ORCALTI_COURSEVISIBLE_NO]);
                $stringname = 'show_in_course_orcalti2';
            } else {
                $stringname = 'show_in_course_orcalti1';
            }
            $mform->addElement('select', 'orcalti_coursevisible', get_string($stringname, 'orcalti'), $options);
            $mform->addHelpButton('orcalti_coursevisible', $stringname, 'orcalti');
            $mform->setDefault('orcalti_coursevisible', '1');
        } else {
            $mform->addElement('hidden', 'orcalti_coursevisible', ORCALTI_COURSEVISIBLE_PRECONFIGURED);
        }
        $mform->setType('orcalti_coursevisible', PARAM_INT);

        $mform->addElement('hidden', 'typeid');
        $mform->setType('typeid', PARAM_INT);

        $launchoptions = array();
        $launchoptions[ORCALTI_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'orcalti');
        $launchoptions[ORCALTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'orcalti');
        $launchoptions[ORCALTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW] = get_string('existing_window', 'orcalti');
        $launchoptions[ORCALTI_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'orcalti');

        $mform->addElement('select', 'orcalti_launchcontainer', get_string('default_launch_container', 'orcalti'), $launchoptions);
        $mform->setDefault('orcalti_launchcontainer', ORCALTI_LAUNCH_CONTAINER_WINDOW);
        $mform->addHelpButton('orcalti_launchcontainer', 'default_launch_container', 'orcalti');
        $mform->setType('orcalti_launchcontainer', PARAM_INT);

        $mform->addElement('advcheckbox', 'orcalti_contentitem', get_string('contentitem_deeplinking', 'orcalti'));
        $mform->addHelpButton('orcalti_contentitem', 'contentitem_deeplinking', 'orcalti');
        if ($istool) {
            $mform->disabledIf('orcalti_contentitem', null);
        }

        $mform->addElement('text', 'orcalti_toolurl_ContentItemSelectionRequest',
            get_string('toolurl_contentitemselectionrequest', 'orcalti'), array('size' => '64'));
        $mform->setType('orcalti_toolurl_ContentItemSelectionRequest', PARAM_URL);
        $mform->addHelpButton('orcalti_toolurl_ContentItemSelectionRequest', 'toolurl_contentitemselectionrequest', 'orcalti');
        $mform->disabledIf('orcalti_toolurl_ContentItemSelectionRequest', 'orcalti_contentitem', 'notchecked');
        if ($istool) {
            $mform->disabledIf('orcalti_toolurl__ContentItemSelectionRequest', null);
        }

        $mform->addElement('hidden', 'oldicon');
        $mform->setType('oldicon', PARAM_URL);

        $mform->addElement('text', 'orcalti_icon', get_string('icon_url', 'orcalti'), array('size' => '64'));
        $mform->setType('orcalti_icon', PARAM_URL);
        $mform->setAdvanced('orcalti_icon');
        $mform->addHelpButton('orcalti_icon', 'icon_url', 'orcalti');

        $mform->addElement('text', 'orcalti_secureicon', get_string('secure_icon_url', 'orcalti'), array('size' => '64'));
        $mform->setType('orcalti_secureicon', PARAM_URL);
        $mform->setAdvanced('orcalti_secureicon');
        $mform->addHelpButton('orcalti_secureicon', 'secure_icon_url', 'orcalti');

        if (!$istool) {
            // Display the orcalti advantage services.
            $this->get_orcalti_advantage_services($mform);
        }

        if (!$istool) {
            // Add privacy preferences fieldset where users choose whether to send their data.
            $mform->addElement('header', 'privacy', get_string('privacy', 'orcalti'));

            $options = array();
            $options[0] = get_string('never', 'orcalti');
            $options[1] = get_string('always', 'orcalti');
            $options[2] = get_string('delegate', 'orcalti');

            $mform->addElement('select', 'orcalti_sendname', get_string('share_name_admin', 'orcalti'), $options);
            $mform->setType('orcalti_sendname', PARAM_INT);
            $mform->setDefault('orcalti_sendname', '2');
            $mform->addHelpButton('orcalti_sendname', 'share_name_admin', 'orcalti');

            $mform->addElement('select', 'orcalti_sendemailaddr', get_string('share_email_admin', 'orcalti'), $options);
            $mform->setType('orcalti_sendemailaddr', PARAM_INT);
            $mform->setDefault('orcalti_sendemailaddr', '2');
            $mform->addHelpButton('orcalti_sendemailaddr', 'share_email_admin', 'orcalti');

            // ORCALTI Extensions.

            // Add grading preferences fieldset where the tool is allowed to return grades.
            $gradeoptions = array();
            $gradeoptions[] = get_string('never', 'orcalti');
            $gradeoptions[] = get_string('always', 'orcalti');
            $gradeoptions[] = get_string('delegate_tool', 'orcalti');

            $mform->addElement('select', 'orcalti_acceptgrades', get_string('accept_grades_admin', 'orcalti'), $gradeoptions);
            $mform->setType('orcalti_acceptgrades', PARAM_INT);
            $mform->setDefault('orcalti_acceptgrades', '2');
            $mform->addHelpButton('orcalti_acceptgrades', 'accept_grades_admin', 'orcalti');

            $mform->addElement('checkbox', 'orcalti_forcessl', get_string('force_ssl', 'orcalti'), '', $options);
            $mform->setType('orcalti_forcessl', PARAM_BOOL);
            if (!empty($CFG->mod_orcalti_forcessl)) {
                $mform->setDefault('orcalti_forcessl', '1');
                $mform->freeze('orcalti_forcessl');
            } else {
                $mform->setDefault('orcalti_forcessl', '0');
            }
            $mform->addHelpButton('orcalti_forcessl', 'force_ssl', 'orcalti');

            if (!empty($this->_customdata->isadmin)) {
                // Add setup parameters fieldset.
                $mform->addElement('header', 'setupoptions', get_string('miscellaneous', 'orcalti'));

                $options = array(
                    ORCALTI_DEFAULT_ORGID_SITEID => get_string('siteid', 'orcalti'),
                    ORCALTI_DEFAULT_ORGID_SITEHOST => get_string('sitehost', 'orcalti'),
                );

                $mform->addElement('select', 'orcalti_organizationid_default', get_string('organizationid_default', 'orcalti'), $options);
                $mform->setType('orcalti_organizationid_default', PARAM_TEXT);
                $mform->setDefault('orcalti_organizationid_default', ORCALTI_DEFAULT_ORGID_SITEID);
                $mform->addHelpButton('orcalti_organizationid_default', 'organizationid_default', 'orcalti');

                $mform->addElement('text', 'orcalti_organizationid', get_string('organizationidguid', 'orcalti'));
                $mform->setType('orcalti_organizationid', PARAM_TEXT);
                $mform->addHelpButton('orcalti_organizationid', 'organizationidguid', 'orcalti');

                $mform->addElement('text', 'orcalti_organizationurl', get_string('organizationurl', 'orcalti'));
                $mform->setType('orcalti_organizationurl', PARAM_URL);
                $mform->addHelpButton('orcalti_organizationurl', 'organizationurl', 'orcalti');
            }
        }

        /* Suppress this for now - Chuck
         * mform->addElement('text', 'orcalti_organizationdescr', get_string('organizationdescr', 'orcalti'))
         * mform->setType('orcalti_organizationdescr', PARAM_TEXT)
         * mform->addHelpButton('orcalti_organizationdescr', 'organizationdescr', 'orcalti')
         */

        /*
        // Add a hidden element to signal a tool fixing operation after a problematic backup - restore process
        //$mform->addElement('hidden', 'orcalti_fix');
        */

        $tab = optional_param('tab', '', PARAM_ALPHAEXT);
        $mform->addElement('hidden', 'tab', $tab);
        $mform->setType('tab', PARAM_ALPHAEXT);

        $courseid = optional_param('course', 1, PARAM_INT);
        $mform->addElement('hidden', 'course', $courseid);
        $mform->setType('course', PARAM_INT);

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

    }

    /**
     * Retrieves the data of the submitted form.
     *
     * @return stdClass
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data && !empty($this->_customdata->istool)) {
            // Content item checkbox is disabled in tool settings, so this cannot be edited. Just unset it.
            unset($data->orcalti_contentitem);
        }
        return $data;
    }

    /**
     * Generates the orcalti advantage extra configuration adding it to the mform
     *
     * @param MoodleQuickForm $mform
     */
    public function get_orcalti_advantage_services(&$mform) {
        // For each service add the label and get the array of configuration.
        $services = orcalti_get_services();
        $mform->addElement('header', 'services', get_string('services', 'orcalti'));
        foreach ($services as $service) {
            /** @var \mod_orcalti\local\orcaltiservice\service_base $service */
            $service->get_configuration_options($mform);
        }
    }

    /**
     * Validate the form data before we allow them to save the tool type.
     *
     * @param array $data
     * @param array $files
     * @return array Error messages
     */
    public function validation($data, $files) {
        global $CFG;

        $errors = parent::validation($data, $files);

        // ORCALTI2 tools do not contain a orcaltiversion field.
        if (isset($data['orcalti_ltiversion']) && $data['orcalti_ltiversion'] == ORCALTI_VERSION_1P3) {
            require_once($CFG->dirroot . '/mod/orcalti/upgradelib.php');

            $warning = mod_orcalti_verify_private_key();
            if (!empty($warning)) {
                $errors['orcalti_ltiversion'] = $warning;
                return $errors;
            }
        }
        return $errors;
    }
}
