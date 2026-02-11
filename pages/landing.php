<?php

require_once('../../../config.php');
require_once('../locallib.php');

require_once('../classes/session.php');
require_once('../classes/access_manager.php');
require_once('../classes/shadow_user.php');
require_once('../forms/non_user_login.php');

use mod_quiz\quiz_settings;
use core\notification;
use core\url as moodle_url;

// TODO return if quiz is not public
$cmid = required_param('cmid', PARAM_INT);

$PAGE->set_cacheable(false);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$quizobj = quiz_settings::create($cm->instance);

$timenow = time();
$accessmanager = new publictestlink_access_manager($quizobj, $timenow);
$accessprevents = $accessmanager->prevent_access();
if (!empty($accessprevents)) {
    $messages = implode(
        ", ",
        array_map(fn($v) => "$v", $accessprevents)
    );

    redirect(
        '/',
        (
            "You cannot access this quiz yet because:" .
            $messages
        ),
        null, notification::ERROR
    );

    return;
}


$PAGE->requires->css('/local/publictestlink/styles.css');

$PAGE->set_url(
    new moodle_url('/local/publictestlink/pages/landing.php', ['cmid' => $cmid])
);

$PAGE->set_title('Login');
$PAGE->set_heading('Login as non-user');
$PAGE->set_pagelayout('standard');

$PAGE->add_body_class('landing-body');


function redirect_to_start() {
    global $PLUGIN_URL, $cmid;
    redirect(new moodle_url($PLUGIN_URL . '/start.php', ['cmid' => $cmid]));
}

$session = publictestlink_session::check_session();
if ($session !== null) {
    redirect_to_start();
    return;
}


$form = new local_publictestlink_non_user_login(
    null,
    ['cmid' => $cmid]
);

if ($data = $form->get_data()) {
    $shadowuser = publictestlink_shadow_user::from_email($data->email);
    if ($shadowuser === null) {
        $shadowuser = publictestlink_shadow_user::create(
            $data->email, $data->firstname, $data->lastname
        );
    } else {
        $shadowuser->update_names(
            $data->firstname, $data->lastname
        );
    }

    $session = publictestlink_session::login($shadowuser);

    redirect_to_start();
    return;
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();