<?php

require_once('../../config.php');
// $cmid = required_param('id', PARAM_INT);
// $cm = get_coursemodule_from_id('mymodulename', $cmid, 0, false, MUST_EXIST);
// $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

// require_login($course, true, $cm);

require_once('./forms/non_user_login.php');
$PAGE->requires->css('/local/publictestlink/styles.css');

$PAGE->set_url('/local/publictestlink/landing.php');
$PAGE->set_context(context_system::instance());

$PAGE->set_title('Login');
$PAGE->set_heading('Login as shadow user');
$PAGE->set_pagelayout('standard');

$PAGE->add_body_class('landing-body');

$form = new local_publictestlink_non_user_login();

if ($data = $form->get_data()) {
    var_dump($data->firstname);
    var_dump($data->lastname);
    var_dump($data->email);
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();