<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * The form for logging in as a non-user.
 */
class local_publictestlink_non_user_login extends moodleform {

    public function definition() {
        global $MODULE;

        $mform = $this->_form;

        $mform->addElement('text', 'email', get_string('form_email', $MODULE));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required', null, 'client');

        $mform->addElement('text', 'firstname', get_string('form_firstname', $MODULE));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', null, 'required', null, 'client');

        $mform->addElement('text', 'lastname', get_string('form_lastname', $MODULE));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', null, 'required', null, 'client');

        $mform->addElement('hidden', 'token');
        $mform->setType('token', PARAM_INT);
        $mform->setDefault('token', $this->_customdata['token']);

        $this->add_action_buttons(true, get_string('submit'));
    }

    public function validation($data, $files) {
        $errors = [];

        if (!validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');
        }

        return $errors;
    }
}