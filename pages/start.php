<?php

/**
 * This script deals with starting a new attempt at a quiz.
 * 
 * Most of the code here is directly copied over from the following files in /public/mod/quiz:
 * startattempt.php
 */

require_once('../../../config.php');
require_once('../../../question/engine/lib.php');
require_once('../locallib.php');
require_once('../classes/access_manager.php');
require_once('../classes/session.php');
require_once('../classes/link_token.php');

use core\url as moodle_url;
use core\notification;
use mod_quiz\quiz_settings;


// Reload every page access
$PAGE->set_cacheable(false);

// Page parameters
$token = required_param('token', PARAM_ALPHANUMEXT);

// Require a valid token
$linktoken = publictestlink_link_token::require_token($token);

// Require a valid session
$session = publictestlink_session::check_session();
if ($session === null) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token]));
    return;
}

$quizid = $linktoken->get_quizid();
$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

$shadowuserid = $session->get_user()->get_id();

// Get the latest existing in-progress attempt.
$attempt = publictestlink_attempt::get_existing_attempt($quizid, $shadowuserid);

// If there's an existing attempt, use that. Otherwise, create one.
if ($attempt !== null) {
    $quba = $attempt->get_quba();
} else {
    $cm = get_coursemodule_from_instance('quiz', $quizid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    $quba = question_engine::make_questions_usage_by_activity($MODULE, $context);
    $quba->set_preferred_behaviour($quiz->preferredbehaviour);

    foreach ($quizobj->get_structure()->get_slots() as $slot) {
        $question = question_bank::load_question(
            $slot->questionid,
            $quiz->shuffleanswers
        );

        $quba->add_question($question, $slot->maxmark);
    }

    $quba->start_all_questions();

    $attempt = publictestlink_attempt::create($quizid, $shadowuserid, $quba);
}

$timenow = time();

// Check if user can access the quiz.
$accessmanager = new publictestlink_access_manager($quizobj, $timenow, $session->get_user(), $attempt);
$reasons = $accessmanager->get_formatted_reasons();
if ($reasons !== null) {
    redirect('/', $reasons, null, notification::ERROR);
    return;
}


redirect(
    new moodle_url($PLUGIN_URL . '/attempt.php', [
        'token' => $token
    ])
);