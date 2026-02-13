<?php

require_once('../../../config.php');
require_once('../locallib.php');

require_once('../classes/session.php');
require_once('../classes/access_manager.php');
require_once('../classes/shadow_user.php');
require_once('../classes/link_token.php');
require_once('../forms/non_user_login.php');

use mod_quiz\quiz_settings;
use core\notification;
use core\url as moodle_url;


$token = required_param('token', PARAM_ALPHANUMEXT);

$linktoken = publictestlink_link_token::require_token($token);

$quizid = $linktoken->get_quizid();
$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

$timenow = time();
$accessmanager = new publictestlink_access_manager($quizobj, $timenow);
$reasons = $accessmanager->get_formatted_reasons();
if ($reasons !== null) {
    redirect('/', $reasons, null, notification::ERROR);
    return;
}


$PAGE->requires->css('/local/publictestlink/styles.css');
$PAGE->set_cacheable(false);

$PAGE->set_url(
    new moodle_url(PLUGIN_URL . '/landing.php', ['token' => $token])
);

$PAGE->set_title('Login');
$PAGE->set_heading('Login as non-user');
$PAGE->set_pagelayout('standard');

$PAGE->add_body_class('landing-body');


function redirect_to_start() {
    global $PLUGIN_URL, $token;
    redirect(new moodle_url($PLUGIN_URL . '/start.php', ['token' => $token]));
}

$session = publictestlink_session::check_session();
if ($session !== null) {
    redirect_to_start();
    return;
}


$form = new local_publictestlink_non_user_login(
    null,
    ['token' => $token]
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