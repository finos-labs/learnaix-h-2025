<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create main external page for the interface
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_cortexanalyst',
        get_string('pluginname', 'local_cortexanalyst'),
        new moodle_url('/local/cortexanalyst/index.php')
    ));
    
    // Create settings page
    $settings = new admin_settingpage('local_cortexanalyst_settings', get_string('settings', 'local_cortexanalyst'));
    
    // Add Snowflake Host setting
    $settings->add(new admin_setting_configtext(
        'local_cortexanalyst/snowflake_host',
        get_string('snowflake_host', 'local_cortexanalyst'),
        get_string('snowflake_host_desc', 'local_cortexanalyst'),
        '',
        PARAM_URL
    ));
    
    // Add Model Path setting
    $settings->add(new admin_setting_configtext(
        'local_cortexanalyst/model_path',
        get_string('model_path', 'local_cortexanalyst'),
        get_string('model_path_desc', 'local_cortexanalyst'),
        '@MOODLE_APP.PUBLIC.MOUNTED/moodledata/revenue_timeseries.yaml',
        PARAM_RAW
    ));
    
    // Add Token File Path setting
    $settings->add(new admin_setting_configtext(
        'local_cortexanalyst/token_file',
        get_string('token_file', 'local_cortexanalyst'),
        get_string('token_file_desc', 'local_cortexanalyst'),
        '/snowflake/session/token',
        PARAM_PATH
    ));
    
    $ADMIN->add('localplugins', $settings);
}