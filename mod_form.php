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
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file defines the main lti configuration form
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

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/orcalti/locallib.php');

class mod_orcalti_mod_form extends moodleform_mod {
    /**
     * Get the request options for orcalti tools.
     *
     * @return array
     */
    private function get_orcalti_request_options() {
        $configusername = get_config('orcalti', 'orcalti_username');
        $configpassword = get_config('orcalti', 'orcalti_password');
        $configurl = get_config('orcalti', 'orcalti_url');

        if (!$configurl) {
            $configurl = "https://provider.orca.nrw";
        }

        $auth = base64_encode("{$configusername}:{$configpassword}");
        $context["http"]=["header" => "Authorization: Basic $auth"];
        $context["ssl"]=["verify_peer" => true,"verify_peer_name"=> true];

        return array(
            "request_url" => $configurl,
            "request_context" => $context
        );
    }

    /**
     * Get the Basic Auth Header.
     *
     * @return string
     */
    private function get_basic_auth_header() {
        $configusername = get_config('orcalti', 'orcalti_username');
        $configpassword = get_config('orcalti', 'orcalti_password');
        $auth = base64_encode("{$configusername}:{$configpassword}");
        return "Authorization: Basic $auth";
    }

    /**
     * Get the translations for orcalti tools.
     *
     * @return array
     */
    public function get_orcalti_translations() {
        $stringarr = array(
            "modal_fullscreen_description",
            "modal_close", "orca_logo_alt",
            "website_url_orca", "orca_link_title",
            "categories", "contact",
            "email_address_orca",
            "send_support_request",
            "input_search_id",
            "input_search_placeholder",
            "searching_in_all_categories",
            "searching_in_category",
            "search_no_content_found",
            "no_search_no_content_found",
            "expand_description",
            "collapse_description",
            "button_select",
            "pagination_label",
            "pagination_next",
            "pagination_prev",
            "error",
            "open_category_menu"
        );

        $en = get_strings($stringarr, 'orcalti');
        $de = get_strings($stringarr, 'orcalti');

        $translations = array(
            "en" => $en,
            "de" => $de
        );

        return json_encode($translations);
    }

    /**
     * Get the options for orcalti SPA.
     *
     * @return array
     */
    public function get_orcalti_options() {
        $options = array(
            "root_id" => "mnrw-orca-lti-root",
            "selected_tool_url_field_name" => "toolurl",
            "selected_tool_id_field_name" => "toolid",
            "selected_tool_toolname_field_name" => "TOOL_NAME"
        );

        return $options;
    }

    /**
     * Get the domstring for orcalti SPA.
     *
     * @return string
     */
    public function get_orcalti_spa_domstring($rootid) {
        $chooseactivitystring = get_string('choose_activity', 'orcalti');
        $requiredstring = get_string('required');

        $domstring = '
        <div id="fitem_toolchooser" class="form-group row fitem">
          <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">
            <label class=" word-break d-inline " for="' . $rootid . '">' .
            $chooseactivitystring .
            '</label>
             <div class="ml-1 ml-md-auto d-flex align-items-center align-self-start">
               <div class="text-danger" title="' . $requiredstring . '">
                 <i class="icon fa fa-exclamation-circle text-danger fa-fw " title="' . $requiredstring . '" aria-label="' . $requiredstring . '"></i>
               </div>
             </div>
           </div>
         <div id="' . $rootid . '" class="col-md-9 form-inline align-items-start felement">Loading....</div>
        </div>';

        return $domstring;
    }

    private function get_content_by_url($subpath) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $requestconfig = $this->get_orcalti_request_options();
        $requesturl = $requestconfig["request_url"];
        $requestcontext = $requestconfig["request_context"];

        $curl = new \curl(array('proxy' => true));
        $curl->setopt(['CURLOPT_CONNECTTIMEOUT' => 10]);
        $curl->setopt(['CURLOPT_HEADER' => false]);
        $curl->setopt(['CURLOPT_NOBODY' => true]);
        $curl->setopt(['CURLOPT_RETURNTRANSFER' => true]);
        $basicauthheader = $this->get_basic_auth_header();
        $curl->setHeader($basicauthheader);

        if (!empty($CFG->proxyhost)) {
            $curl->setopt(['CURLOPT_PROXY' => $CFG->proxyhost]);
            if (!empty($CFG->proxyport)) {
                $curl->setopt(['CURLOPT_PROXYPORT' => $CFG->proxyport]);
            }
            if (!empty($CFG->proxytype)) {
                // Only set CURLOPT_PROXYTYPE if it's something other than the curl-default http.
                if ($CFG->proxytype == 'SOCKS5') {
                    $curl->setopt(['CURLOPT_PROXYTYPE' => 'CURLPROXY_SOCKS5']);
                }
            }
        }

        $resultjson = $curl->get(rtrim(rtrim($requesturl, " "), "/") . $subpath, $requestcontext);

        if (!empty($curl->info) && !empty($curl->info['http_code']) && $curl->info['http_code'] != 200 ) {
            throw new Exception($curl->info['http_code']);
        } else if (!empty($curl->error)) {
            throw new Exception($curl->error);
        }

        return $resultjson;
    }

    /**
     * Get the data of orcalti tools.
     *
     * @return array
     */
    public function get_orcalti_tools() {
        $requestconfig = $this->get_orcalti_request_options();
        $requesturl = $requestconfig["request_url"];

        // Get answer.
        $json = $this->get_content_by_url("/shared");
        // HTTP-Error-handling
        if ($json === false) {
            throw new Exception('http-error');
        } else if ($json == "") {
            throw new Exception('no-tools-error');
        } else {

            $toolsdata = json_decode($json);
            // Http Code 200 but empty or wrong json returned
            if (empty($toolsdata)) {
                throw new Exception("incorrectresponse");
            }

            $orcaltitools = array();
            foreach ((array) $toolsdata as $tooldata) {
                $orcaltitool = array();
                if (empty($tooldata->name)) {
                    $orcaltitool['name'] = $tooldata->fullname;
                } else {
                    $orcaltitool['name'] = $tooldata->name;
                }
                if ($tooldata->tool_url === null) {
                    $orcaltitool['url'] = rtrim(rtrim($requesturl, " "), "/") . "/moodle/enrol/lti/tool.php?id=" . $tooldata->toolid;
                } else {
                    $orcaltitool['url'] = $tooldata->tool_url;
                }
                $orcaltitool['category'] = $tooldata->category;
                $orcaltitool['toolid'] = $tooldata->toolid;
                $orcaltitool['description'] = $tooldata->description;
                $orcaltitool['key'] = $tooldata->key;
                $orcaltitools[] = $orcaltitool;
            }
            return json_encode($orcaltitools);
        }
    }

    public function get_orcalti_categories() {
        $json = $this->get_content_by_url("/categories");
        return $json;
    }

    public function get_orcalti_secrets($toolid, $toolurl) {
        $secret = "";

        $json = $this->get_content_by_url("/shared");
        if ($json && $toolid) {
            $toolsdata = json_decode($json);

            $filtereditems = array_filter($toolsdata, function($item, $k) use ($toolid, $toolurl) {
                return $item->toolid == $toolid && $item->tool_url == $toolurl;
            }, ARRAY_FILTER_USE_BOTH);

            $item = ($filtereditems) ? reset($filtereditems) : null;
            $secret = ($item) ? $item->secret : "";

        }

        if ($secret == "") {
            return 0;  // TODO: throw visible Error message.
        }
        return $secret;
    }

    public function definition() {
        global $PAGE, $OUTPUT, $COURSE;
        $ltierror = "";
        // Gets options for all orcalti related settings, also used for SPA.
        $orcaltioptions = $this->get_orcalti_options();

        if ($type = optional_param('type', false, PARAM_ALPHA)) {
            component_callback("orcaltisource_$type", 'add_instance_hook');
        }

        $this->typeid = 0;

        $mform = &$this->_form;
        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('basicltiname', 'orcalti'), array('size' => '64'));

        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        // Adding the optional "intro" and "introformat" pair of fields.
        $this->standard_intro_elements(get_string('basicltiintro', 'orcalti'));
        $mform->setAdvanced('introeditor');

        // Display the label to the right of the checkbox so it looks better & matches rest of the form.
        if ($mform->elementExists('showdescription')) {
            $coursedesc = $mform->getElement('showdescription');
            if (!empty($coursedesc)) {
                $coursedesc->setText(' ' . $coursedesc->getLabel());
                $coursedesc->setLabel('&nbsp');
            }
        }

        $mform->setAdvanced('showdescription');

        $mform->addElement('checkbox', 'showtitlelaunch', '&nbsp;', ' ' . get_string('display_name', 'orcalti'));
        $mform->setAdvanced('showtitlelaunch');
        $mform->setDefault('showtitlelaunch', true);
        $mform->addHelpButton('showtitlelaunch', 'display_name', 'orcalti');

        $mform->addElement('checkbox', 'showdescriptionlaunch', '&nbsp;', ' ' . get_string('display_description', 'orcalti'));
        $mform->setAdvanced('showdescriptionlaunch');
        $mform->addHelpButton('showdescriptionlaunch', 'display_description', 'orcalti');

        $mform->setType('urlmatchedtypeid', PARAM_INT);

        $launchoptions = array();
        $launchoptions[ORCALTI_LAUNCH_CONTAINER_DEFAULT] = get_string('default', 'orcalti');
        $launchoptions[ORCALTI_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'orcalti');
        $launchoptions[ORCALTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'orcalti');
        $launchoptions[ORCALTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW] = get_string('existing_window', 'orcalti');
        $launchoptions[ORCALTI_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'orcalti');

        $mform->addElement('select', 'launchcontainer', get_string('launchinpopup', 'orcalti'), $launchoptions);
        $mform->setDefault('launchcontainer', ORCALTI_LAUNCH_CONTAINER_WINDOW);
        $mform->addHelpButton('launchcontainer', 'launchinpopup', 'orcalti');
        $mform->setAdvanced('launchcontainer');

        $mform->addElement('html', $this->get_orcalti_spa_domstring($orcaltioptions["root_id"]));

        $toolurldomname = $orcaltioptions["selected_tool_url_field_name"];
        $mform->addElement('hidden', $toolurldomname);
        $mform->setType($toolurldomname, PARAM_TEXT);

        $tooliddomname = $orcaltioptions["selected_tool_id_field_name"];
        $mform->addElement('hidden', $tooliddomname);
        $mform->setType($tooliddomname, PARAM_TEXT);

        $mform->addElement('hidden', 'resourcekey');
        $mform->setType('resourcekey', PARAM_TEXT);

        $configusername = get_config('orcalti', 'orcalti_username');
        $mform->setDefault('resourcekey', $configusername);

        // Add privacy preferences fieldset where users choose whether to send their data.
        $mform->addElement('header', 'privacy', get_string('privacy', 'orcalti'));

        $mform->addElement('advcheckbox', 'instructorchoiceacceptgrades', '&nbsp;', ' ' . get_string('accept_grades', 'orcalti'));
        $mform->setDefault('instructorchoiceacceptgrades', '1');
        $mform->addHelpButton('instructorchoiceacceptgrades', 'accept_grades', 'orcalti');

        // Add standard course module grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        $mform->setAdvanced('cmidnumber');

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

        $editurl = new moodle_url(
            '/mod/orcalti/instructor_edit_tool_type.php',
            array('sesskey' => sesskey(), 'course' => $COURSE->id)
        );
        $ajaxurl = new moodle_url('/mod/orcalti/ajax.php');

        // All these icon uses are incorrect. LTI JS needs updating to use AMD modules and templates so it can use
        // the mustache pix helper - until then LTI will have inconsistent icons.
        $jsinfo = (object)array(
                        'edit_icon_url' => (string)$OUTPUT->image_url('t/edit'),
                        'add_icon_url' => (string)$OUTPUT->image_url('t/add'),
                        'delete_icon_url' => (string)$OUTPUT->image_url('t/delete'),
                        'green_check_icon_url' => (string)$OUTPUT->image_url('i/valid'),
                        'warning_icon_url' => (string)$OUTPUT->image_url('warning', 'orcalti'),
                        'instructor_tool_type_edit_url' => $editurl->out(false),
                        'ajax_url' => $ajaxurl->out(true),
                        'courseId' => $COURSE->id
                  );

        $module = array(
            'name' => 'mod_orcalti_edit',
            'fullpath' => '/mod/orcalti/mod_form.js',
            'requires' => array('base', 'io', 'querystring-stringify-simple', 'node', 'event', 'json-parse'),
            'strings' => array(
                array('addtype', 'orcalti'),
                array('edittype', 'orcalti'),
                array('deletetype', 'orcalti'),
                array('delete_confirmation', 'orcalti'),
                array('cannot_edit', 'orcalti'),
                array('cannot_delete', 'orcalti'),
                array('global_tool_types', 'orcalti'),
                array('course_tool_types', 'orcalti'),
                array('using_tool_configuration', 'orcalti'),
                array('using_tool_cartridge', 'orcalti'),
                array('domain_mismatch', 'orcalti'),
                array('custom_config', 'orcalti'),
                array('tool_config_not_found', 'orcalti'),
                array('tooltypeadded', 'orcalti'),
                array('tooltypedeleted', 'orcalti'),
                array('tooltypenotdeleted', 'orcalti'),
                array('tooltypeupdated', 'orcalti'),
                array('forced_help', 'orcalti')
            ),
        );

        if (!empty($typeid)) {
            $mform->setAdvanced('typeid');
            $mform->setAdvanced($toolurldomname);
        }

        $PAGE->requires->js_init_call('M.mod_orcalti.editor.init', array(json_encode($jsinfo)), true, $module);

        // Render SPA.
        $spacontent = null;
        $spacategories = null;
        $spatranslations = null;
        $spaoptions = null;
        $ltierror = null;
        
        try {
            $spatranslations = $this->get_orcalti_translations();
            $spaoptions = json_encode($orcaltioptions);

            $spacontent = $this->get_orcalti_tools();
            $spacategories = $this->get_orcalti_categories();
        } catch (Exception $e) {
            $ltierror = $e->getMessage();
        }

        $mform->addElement('hidden', 'lti_selector_tools');
        $mform->setType('lti_selector_tools', PARAM_TEXT);
        $mform->setDefault('lti_selector_tools', $spacontent);

        $mform->addElement('hidden', 'lti_selector_categories');
        $mform->setType('lti_selector_categories', PARAM_TEXT);
        $mform->setDefault('lti_selector_categories', $spacategories);

        $mform->addElement('hidden', 'lti_selector_translations');
        $mform->setType('lti_selector_translations', PARAM_TEXT);
        $mform->setDefault('lti_selector_translations', $spatranslations);

        $mform->addElement('hidden', 'lti_selector_options');
        $mform->setType('lti_selector_options', PARAM_TEXT);
        $mform->setDefault('lti_selector_options', $spaoptions);

        $mform->addElement('hidden', 'lti_selector_error');
        $mform->setType('lti_selector_error', PARAM_TEXT);
        $mform->setDefault('lti_selector_error', $ltierror);

        $PAGE->requires->js_call_amd('mod_orcalti/orca_lti_selector-lazy', 'init');
    }

    /**
     * Sets the current values handled by services in case of update.
     *
     * @param object $defaultvalues default values to populate the form with.
     */
    public function set_data($defaultvalues) {
        $services = orcalti_get_services();
        if (is_object($defaultvalues)) {
            foreach ($services as $service) {
                $service->set_instance_form_values($defaultvalues);
            }
        }
        parent::set_data($defaultvalues);
    }

    /**
     * Allows modules to modify the data returned by form get_data().
     * @param stdClass $data passed by reference
     */
    public function data_postprocessing($data) {
        if ($data->toolurl) {
            $params = [];
            parse_str(parse_url($data->toolurl, PHP_URL_QUERY), $params);
            $toolid = $params["id"];

            if (is_null($toolid)) {
                $toolid = $data->toolid;
            }

            if (!is_null($toolid) && !empty($toolid)) {
                $secret = $this->get_orcalti_secrets($toolid, $data->toolurl);
                $data->password = $secret;
            }
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if($data['toolurl'] === '') {
            $errors['name'] = get_string('missing_orca_content', 'mod_orcalti');
        }
        return $errors;
    }
}
