<?php

use core\exception\moodle_exception;
use core\output\html_writer;
use core\url as moodle_url;
use mod_quiz\quiz_settings;
use context;
use mod_quiz\output\attempt_summary_information;

require_once('../../../config.php');
require_once('../locallib.php');
require_once('../classes/attempt.php');



// Page parameters
$attemptid = required_param('attemptid', PARAM_INT);

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



// Start writing page
$PAGE->set_url($PLUGIN_URL . '/reviewteacher.php', ['attemptid' => $attemptid]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title('Review');

$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_course($quizobj->get_course());

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


// Summary table
$summary = new attempt_summary_information();
$summary->add_item('respondent',
    get_string('respondent', $MODULE),
    "{$shadowuser->get_firstname()} {$shadowuser->get_lastname()} ({$shadowuser->get_email()})"
);

$summary->add_item('attempt_state',
    get_string('attempt_state', MODULE),
    $attempt->get_state_readable()
);

$summary->add_item('started_on',
    get_string('started_on', MODULE),
    userdate($attempt->get_timestart())
);

// Extra fields for finished attempts
if (!$attempt->is_in_progress()) {
    $summary->add_item('completed_on',
        get_string('completed_on', MODULE),
        userdate($attempt->get_timeend())
    );

    $summary->add_item('duration',
        get_string('duration', MODULE),
        format_time($attempt->get_timeend() - $attempt->get_timestart())
    );

    $summary->add_item('marks',
        get_string('marks', MODULE),
        format_float($attempt->get_total_mark(), 2) . '/' . format_float($attempt->get_max_mark(), 2)
    );

    $summary->add_item('grade',
        get_string('grade', MODULE),
        get_string(
            'outof',
            'quiz', (object)[
                'grade'    => html_writer::tag('b', quiz_format_grade($attempt->get_quizobj()->get_quiz(), $attempt->get_scaled_grade())),
                'maxgrade' => quiz_format_grade($attempt->get_quizobj()->get_quiz(), $attempt->get_max_grade()),
            ]
        ) . ' (' . html_writer::tag('b', format_float($attempt->get_percentage() * 100, 0)) . '%)',
    );
}

echo html_writer::div($OUTPUT->render($summary), 'mb-3');

// Render all questions
foreach ($quba->get_slots() as $slot) {
    echo $quba->render_question($slot, $displayoptions, $slot);
}


// Finish review link
echo html_writer::div(
    html_writer::link(new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]), 'Finish review'),
    'd-flex flex-row w-full justify-content-end'
);

echo $OUTPUT->footer();