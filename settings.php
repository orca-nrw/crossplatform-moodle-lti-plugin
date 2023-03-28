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

defined('MOODLE_INTERNAL') || die;

    $ADMIN->add('modsettings', new admin_externalpage('orcaltitoolconfigure', 'ORCA-LTI 1.3 Configuration', new moodle_url('/mod/orcalti/toolconfigure.php')));

    if ($ADMIN->fulltree) {

        $settings->add(new admin_setting_configtext('orcalti/orcalti_username', get_string('orcalti_username', 'orcalti'),
                            get_string('orcalti_username_details', 'orcalti'), '', PARAM_TEXT));

        $settings->add(new admin_setting_configpasswordunmask('orcalti/orcalti_password', get_string('orcalti_password', 'orcalti'),
                            get_string('orcalti_password_details', 'orcalti'), '', PARAM_TEXT));

        $settings->add(new admin_setting_configtext('orcalti/orcalti_url', get_string('orcalti_url', 'orcalti'),
                            get_string('orcalti_url_details', 'orcalti'), 'https://provider.orca.nrw/ltidir/', PARAM_TEXT));

    }
