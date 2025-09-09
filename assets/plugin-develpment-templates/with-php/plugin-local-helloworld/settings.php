<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_helloworld',
        get_string('pluginname', 'local_helloworld'),
        new moodle_url('/local/helloworld/index.php')
    ));
}