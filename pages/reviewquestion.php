<?php

use core\exception\moodle_exception;
use mod_quiz\quiz_settings;
use context;
use core\output\html_writer;
use mod_quiz\output\attempt_summary_information;

require_once('../../../config.php');
require_once('../locallib.php');
require_once('../classes/attempt.php');


// Page parameters
$attemptid = required_param('attemptid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);

$attempt = publictestlink_attempt::from_id($attemptid);
$quizid = $attempt->get_quizid();

$quizobj = quiz_settings::create($quizid);
$cm = get_coursemodule_from_id('quiz', $quizobj->get_cmid(), 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
if (!$context) throw new moodle_exception('invalidcontext', $MODULE);

$shadowuser = $attempt->get_shadow_user();

// Require logging in
require_login($quizobj->get_course(), false, $cm);
/** @var context $context */
require_capability('mod/quiz:viewreports', $context);

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);


// Create summary table
$summary = new attempt_summary_information();
$summary->add_item('username',
    get_string('respondent', MODULE),
    "{$shadowuser->get_firstname()} {$shadowuser->get_lastname()} ({$shadowuser->get_email()})"
);

$summary->add_item('quizname',
    get_string('modulename', 'quiz'),
    $quizobj->get_quiz_name()
);

$question = $quba->get_question($slot);

$summary->add_item('questionname',
    get_string('questionname', 'quiz'),
    $question->name
);

$completedon = $attempt->get_timeend();
if ($completedon !== null) {
    $summary->add_item(
        'timestamp',
        get_string('completedon', 'quiz'),
        userdate($completedon)
    );
}


// Start creating page
$PAGE->set_url(PLUGIN_URL . '/reviewquestion.php', ['attemptid' => $attemptid, 'slot' => $slot]);
$PAGE->set_pagelayout('popup');
$PAGE->set_title('Question Review');

$PAGE->set_pagetype('mod-quiz-review');
$PAGE->set_blocks_editing_capability('moodle/site:manageblocks');
$PAGE->blocks->show_only_fake_blocks = false;
$PAGE->blocks->add_region('side-pre');
$PAGE->blocks->add_region('side-post');

$displayoptions = new question_display_options();
$displayoptions->readonly = true;
$displayoptions->marks = question_display_options::MARK_AND_MAX;
$displayoptions->correctness = question_display_options::VISIBLE;
$displayoptions->feedback = question_display_options::VISIBLE;
$displayoptions->rightanswer = question_display_options::VISIBLE;
$displayoptions->history = question_display_options::VISIBLE;

echo $OUTPUT->header();

// Render summary
echo html_writer::div($OUTPUT->render($summary), 'mb-3');

// Render question
echo $quba->render_question($slot, $displayoptions, $slot);

// Render close button
echo $OUTPUT->close_window_button();

echo $OUTPUT->footer();