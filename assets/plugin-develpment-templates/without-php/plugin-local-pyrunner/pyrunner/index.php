<?php
require('../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/pyrunner:run', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/pyrunner/index.php'));
$PAGE->set_title(get_string('runheading', 'local_pyrunner'));
$PAGE->set_heading(get_string('runheading', 'local_pyrunner'));

$arg = optional_param('arg', '', PARAM_TEXT);
$output = '';

if ($arg !== '') {
    require_once(__DIR__ . '/lib.php');
    $data = local_pyrunner_analyze($arg);
    $output = json_encode($data, JSON_PRETTY_PRINT);
}

echo $OUTPUT->header();
echo html_writer::tag('h3', get_string('runheading', 'local_pyrunner'));

$form = html_writer::start_tag('form', ['method' => 'get']);
$form .= html_writer::tag('label', get_string('inputlabel', 'local_pyrunner'), ['for' => 'arg']);
$form .= html_writer::empty_tag('input', ['type' => 'text', 'name' => 'arg', 'id' => 'arg', 'value' => s($arg)]);
$form .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('runbutton', 'local_pyrunner')]);
$form .= html_writer::end_tag('form');
echo $form;

if ($output) {
    echo html_writer::tag('h4', get_string('output', 'local_pyrunner'));
    echo html_writer::tag('pre', s($output));
}
echo $OUTPUT->footer();
