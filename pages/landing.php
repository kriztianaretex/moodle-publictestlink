<?php

require_once('../../../config.php');
require_once('../locallib.php');

require_once('../classes/session.php');
require_once('../classes/access_manager.php');
require_once('../classes/shadow_user.php');
require_once('../classes/link_token.php');
require_once('../forms/non_user_login.php');

use core\exception\moodle_exception;
use mod_quiz\quiz_settings;
use core\notification;
use core\url as moodle_url;


// Page query parameters
$token = required_param('token', PARAM_ALPHANUMEXT);

// Require a valid token
$linktoken = publictestlink_link_token::require_token($token);

/**
 * Redirects to `start.php` to start the attempt.
 */
function redirect_to_start() {
    global $token;
    redirect(new moodle_url(PLUGIN_URL . '/start.php', ['token' => $token]));
}

// Check if session is valid
$session = publictestlink_session::check_session();
if ($session !== null) {
    redirect_to_start();
    return;
}

// Initialize required variables
$quizid = $linktoken->get_quizid();
$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

$cm = get_coursemodule_from_id('quiz', $quizobj->get_cmid(), 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
if (!$context) throw new moodle_exception('invalidcontext', $MODULE);

// Check if public users can access the quiz
$timenow = time();
$accessmanager = new publictestlink_access_manager($quizobj, $timenow);
$reasons = $accessmanager->get_formatted_reasons();
if ($reasons !== null) {
    redirect('/', $reasons, null, notification::ERROR);
    return;
}


// Never cache the page
$PAGE->set_cacheable(false);

$PAGE->requires->css('/local/publictestlink/styles.css');

$PAGE->set_url(new moodle_url(PLUGIN_URL . '/landing.php', ['token' => $token]));
$PAGE->requires->css('/local/publictestlink/styles.css');
$PAGE->add_body_class('landing-body');

$PAGE->set_pagelayout('standard');
$PAGE->set_blocks_editing_capability(false);
$PAGE->set_secondary_navigation(false);
$PAGE->set_show_course_index(false);

$PAGE->set_course($quizobj->get_course());
$PAGE->set_cm($cm);
$PAGE->set_context($context);

// Remove navbar
$PAGE->navbar->ignore_active(true);
foreach ($PAGE->navbar->get_items() as $node) {
    $node->action = null;
}

$PAGE->set_title('Login');
$PAGE->set_heading('Login as non-user');


// Create the form
$form = new local_publictestlink_non_user_login(
    null,
    ['token' => $token]
);

// When the form is submitted
if ($data = $form->get_data()) {
    $shadowuser = publictestlink_shadow_user::from_email($data->email);

    // If the user doesn't exist yet
    if ($shadowuser === null) {
        $shadowuser = publictestlink_shadow_user::create(
            $data->email, $data->firstname, $data->lastname
        );
    } else {
        $shadowuser->update_names(
            $data->firstname, $data->lastname
        );
    }

    // Then, log in the user.
    $session = publictestlink_session::login($shadowuser);

    redirect_to_start();
    return;
}

// Start writing the page
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();