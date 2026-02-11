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

use core\url as moodle_url;
use core\notification;
use mod_quiz\quiz_settings;

// Page parameters
$cmid = required_param('cmid', PARAM_INT);

$PAGE->set_cacheable(false);

$session = publictestlink_session::check_session();
if ($session == null) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid]));
    return;
}

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$quizobj = quiz_settings::create($cm->instance);


$quizid = $quiz->id;
$shadowuserid = $session->get_user()->get_id();

$attempt = publictestlink_attempt::get_existing_attempt($quizid, $shadowuserid);

if ($attempt !== null) {
    $quba = $attempt->get_quba();
} else {
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

$accessmanager = new publictestlink_access_manager($quizobj, $timenow, $session->get_user(), $attempt);
$accessprevents = $accessmanager->prevent_access();
if (!empty($accessprevents)) {
    $messages = implode(
        '\n',
        array_map(fn($v) => "$v", $accessprevents)
    );

    publictestlink_session::logout();

    redirect(
        new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid]),
        (
            "You cannot access this quiz because of the following reasons:\n" .
            $messages
        ),
        null,
        notification::ERROR
    );

    return;
}


redirect(
    new moodle_url($PLUGIN_URL . '/attempt.php', [
        'attemptid' => $attempt->get_id(),
        'cmid' => $cmid,
    ])
);