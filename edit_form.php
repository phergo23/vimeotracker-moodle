<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_vimeotracker_mod_form extends moodleform_mod {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name', 'vimeotracker'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'videosettings', get_string('videosettings', 'vimeotracker'));
        $mform->addElement('text', 'vimeo_id', get_string('vimeoid', 'vimeotracker'), array('size' => '20'));
        $mform->setType('vimeo_id', PARAM_ALPHANUM);
        $mform->addRule('vimeo_id', null, 'required', null, 'client');

        $mform->addElement('checkbox', 'resume_enabled', get_string('resume_enabled', 'vimeotracker'));
        $mform->setDefault('resume_enabled', 1);

        $mform->addElement('checkbox', 'track_time', get_string('track_time', 'vimeotracker'));
        $mform->setDefault('track_time', 1);

        $percent_options = array();
        for ($i = 10; $i <= 100; $i += 10) {
            $percent_options[$i] = $i . '%';
        }
        $mform->addElement('select', 'min_watch_percent', get_string('min_watch_percent', 'vimeotracker'), $percent_options);
        $mform->setDefault('min_watch_percent', 80);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
