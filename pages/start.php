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
use core\exception\moodle_exception;
use mod_quiz\quiz_settings;

// Page parameters
$cmid = required_param('cmid', PARAM_INT);

$session = publictestlink_session::check_session();
if ($session == null) {
    redirect(
        new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid]),
        'You are not logged in.', null, notification::ERROR
    );
    return;
}

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$quba = question_engine::make_questions_usage_by_activity(
    $MODULE,
    context_module::instance($cmid)
);
$quba->set_preferred_behaviour($quiz->preferredbehaviour);

$quizobj = quiz_settings::create($cm->instance, $USER->id);
$structure = $quizobj->get_structure();

foreach ($structure->get_slots() as $slot) {
    $question = question_bank::load_question(
        $slot->questionid,
        $quiz->shuffleanswers
    );

    $quba->add_question($question, $slot->maxmark);
}

$quba->start_all_questions();

$attempt = publictestlink_attempt::start_new_or_resume(
    $quiz->id,
    $session->get_user()->get_id(),
    $quba
);

$timenow = time();
$accessmanager = new publictestlink_access_manager($quizobj, $attempt, $timenow);
$accessprevents = $accessmanager->prevent_access();
if (!empty($accessprevents)) {
    $output = $PAGE->get_renderer('mod_quiz');
    throw new moodle_exception(
        'attempterror',
        $MODULE,
        new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid]),
        $output->access_messages($accessprevents)
    );
}

redirect(
    new moodle_url($PLUGIN_URL . '/attempt.php', [
        'attemptid' => $attempt->get_id(),
        'cmid' => $cmid,
    ])
);