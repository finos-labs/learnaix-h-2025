<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
admin_externalpage_setup('local_helloworld');


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('helloworld', 'local_helloworld'));
echo $OUTPUT->footer();