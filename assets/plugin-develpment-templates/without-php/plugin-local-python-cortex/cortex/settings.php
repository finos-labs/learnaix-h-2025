<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_cortex',
        get_string('pluginname', 'local_cortex'),
        new moodle_url('/local/cortex/index.php')
    ));
}