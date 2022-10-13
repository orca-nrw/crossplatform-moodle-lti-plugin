<?php
    $settings->add(new admin_setting_configtext('orcalti/orcalti_username', get_string('orcalti_username', 'orcalti'),
                       get_string('orcalti_username_details', 'orcalti'), '', PARAM_TEXT));
 
    $settings->add(new admin_setting_configpasswordunmask('orcalti/orcalti_password', get_string('orcalti_password', 'orcalti'),
                       get_string('orcalti_password_details', 'orcalti'), '', PARAM_TEXT));
                       
    $settings->add(new admin_setting_configtext('orcalti/orcalti_url', get_string('orcalti_url', 'orcalti'),
                       get_string('orcalti_url_details', 'orcalti'), 'https://provider.orca.nrw', PARAM_TEXT));
