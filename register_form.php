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
 * This file defines the main tool registration configuration form
 *
 * @package mod_orcalti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/orcalti/locallib.php');

/**
 * The mod_orcalti_register_types_form class.
 *
 * @package    mod_orcalti
 * @since      Moodle 2.8
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_orcalti_register_types_form extends moodleform {

    /**
     * Set up the form definition.
     */
    public function definition() {
        global $CFG;

        $mform    =& $this->_form;

        $mform->addElement('header', 'setup', get_string('registration_options', 'orcalti'));

        // Tool Provider name.

        $strrequired = get_string('required');
        $mform->addElement('text', 'orcalti_registrationname', get_string('registrationname', 'orcalti'));
        $mform->setType('orcalti_registrationname', PARAM_TEXT);
        $mform->addHelpButton('orcalti_registrationname', 'registrationname', 'orcalti');
        $mform->addRule('orcalti_registrationname', $strrequired, 'required', null, 'client');

        // Registration URL.

        $mform->addElement('text', 'orcalti_registrationurl', get_string('registrationurl', 'orcalti'), array('size' => '64'));
        $mform->setType('orcalti_registrationurl', PARAM_URL);
        $mform->addHelpButton('orcalti_registrationurl', 'registrationurl', 'orcalti');
        $mform->addRule('orcalti_registrationurl', $strrequired, 'required', null, 'client');

        // ORCALTI Capabilities.

        $options = array_keys(orcalti_get_capabilities());
        natcasesort($options);
        $attributes = array( 'muorcaltiple' => 1, 'size' => min(count($options), 10) );
        $mform->addElement('select', 'orcalti_capabilities', get_string('capabilities', 'orcalti'),
            array_combine($options, $options), $attributes);
        $mform->setType('orcalti_capabilities', PARAM_TEXT);
        $mform->addHelpButton('orcalti_capabilities', 'capabilities', 'orcalti');
        $mform->addRule('orcalti_capabilities', $strrequired, 'required', null, 'client');

        // ORCALTI Services.

        $services = orcalti_get_services();
        $options = array();
        foreach ($services as $service) {
            $options[$service->get_id()] = $service->get_name();
        }
        $attributes = array( 'muorcaltiple' => 1, 'size' => min(count($options), 10) );
        $mform->addElement('select', 'orcalti_services', get_string('services', 'orcalti'), $options, $attributes);
        $mform->setType('orcalti_services', PARAM_TEXT);
        $mform->addHelpButton('orcalti_services', 'services', 'orcalti');
        $mform->addRule('orcalti_services', $strrequired, 'required', null, 'client');

        $mform->addElement('hidden', 'toolproxyid');
        $mform->setType('toolproxyid', PARAM_INT);

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
     * Set up rules for disabling fields.
     */
    public function disable_fields() {

        $mform    =& $this->_form;

        $mform->disabledIf('orcalti_registrationurl', null);
        $mform->disabledIf('orcalti_capabilities', null);
        $mform->disabledIf('orcalti_services', null);

    }
}
