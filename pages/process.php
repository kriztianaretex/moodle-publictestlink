<?php
require_once('../../../config.php');
require_once('../locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');
require_once('../classes/link_token.php');

use core\url as moodle_url;
use mod_quiz\quiz_settings;


// Page query parameters
$token = required_param('token', PARAM_ALPHANUMEXT);
$isfinish = optional_param('finishattempt', false, PARAM_BOOL);

// Require a valid token.
$linktoken = publictestlink_link_token::require_token($token);

// Always reload page when accessed
$PAGE->set_cacheable(false);

// Require a session
$session = publictestlink_session::check_session();
if (!$session) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token]));
}

$quizid = $linktoken->get_quizid();
$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

$attempt = publictestlink_attempt::require_attempt($quizid, $session->get_user()->get_id());

$timenow = time();

// Check the time left for the attempt.
$endtime = null;
$timeleft = null;
if ($quiz->timelimit > 0) {
    $endtime = $attempt->get_timestart() + $quiz->timelimit;
    $timeleft = max(0, $endtime - $timenow);
}

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);

$quba->process_all_actions(
    $timenow,
    $_POST,
);


// Save the attempt
question_engine::save_questions_usage_by_activity($quba);


/**
 * Submits the attempt.
 */
function submit() {
    global $quba, $timenow, $attempt, $token;

    $quba->finish_all_questions($timenow);
    question_engine::save_questions_usage_by_activity($quba);
    $attempt->mark_submitted($timenow);

    redirect(new moodle_url(PLUGIN_URL . '/review.php', ['token' => $token]));
}

// If there is no time left for the attempt, auto-submit
if ($timeleft !== null && $timeleft <= 0) {
    $overduehandle = $quiz->overduehandling;

    // if ($overduehandle === 'autosubmit') {
    submit();
}

// If the user wants to finish the attempt, submit
if ($isfinish) submit();

// Otherwise, go to summary
redirect(new moodle_url($PLUGIN_URL . '/summary.php', ['token' => $token]));