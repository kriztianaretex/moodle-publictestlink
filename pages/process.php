<?php
require_once('../../../config.php');
require_once('../locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');
require_once('../classes/link_token.php');

use core\url as moodle_url;
use mod_quiz\quiz_settings;

$token = required_param('token', PARAM_ALPHANUMEXT);
$isfinish = optional_param('finishattempt', false, PARAM_BOOL);

$linktoken = publictestlink_link_token::require_token($token);

$PAGE->set_cacheable(false);

$session = publictestlink_session::check_session();
if (!$session) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token]));
}

$quizid = $linktoken->get_quizid();
$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

$attempt = publictestlink_attempt::require_attempt($quizid, $session->get_user()->get_id());

// Shadow-user ownership check
if ($attempt->get_shadow_user()->get_id() !== $session->get_user()->get_id()) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token]));
}

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);

$timenow = time();

$quba->process_all_actions(
    $timenow,
    $_POST,
);

question_engine::save_questions_usage_by_activity($quba);

if ($isfinish) {
    $quba->finish_all_questions($timenow);
    question_engine::save_questions_usage_by_activity($quba);
    $attempt->mark_submitted($timenow);

    redirect(
        new moodle_url($PLUGIN_URL . '/review.php', ['token' => $token]),
        null,
        1000
    );
}

redirect(
    new moodle_url($PLUGIN_URL . '/attempt.php', ['token' => $token]),
    null,
    1000
);