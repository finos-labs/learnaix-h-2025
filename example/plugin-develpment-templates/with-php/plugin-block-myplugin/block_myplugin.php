<?php
defined('MOODLE_INTERNAL') || die();

class block_myplugin extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_myplugin');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->text = html_writer::div(get_string('hello', 'block_myplugin'));
        $this->content->footer = html_writer::link(new moodle_url('/my/'), get_string('gotodashboard', 'block_myplugin'));
        return $this->content;
    }

    public function applicable_formats() {
        return array('site-index' => true, 'course-view' => true, 'mod' => false);
    }
}
