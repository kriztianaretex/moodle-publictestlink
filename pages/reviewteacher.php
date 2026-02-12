<?php

use core\exception\moodle_exception;
use core\output\html_writer;
use core\url as moodle_url;
use mod_quiz\quiz_settings;
use context;

require_once('../../../config.php');
require_once('../locallib.php');
require_once('../classes/attempt.php');



$attemptid = required_param('attemptid', PARAM_INT);

$attempt = publictestlink_attempt::from_id($attemptid);
$quizid = $attempt->get_quizid();

$quizobj = quiz_settings::create($quizid);
$cm = get_coursemodule_from_id('quiz', $quizobj->get_cmid(), 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
if (!$context) throw new moodle_exception('invalidcontext', $MODULE);

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);

require_login($quizobj->get_course(), false, $cm);
/** @var context $context */
require_capability('mod/quiz:viewreports', $context);



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


echo html_writer::start_div('quizattemptsummary mb-4');
    echo html_writer::start_tag('table', ['class' => 'table generaltable generalbox quizreviewsummary']);
        echo html_writer::start_tag('tbody');
            $shadowuser = $attempt->get_shadow_user();

            echo html_writer::tag('tr',
                html_writer::tag('th', get_string('respondent', $MODULE), ['class' => 'cell']) .
                html_writer::tag('td',
                    "{$shadowuser->get_firstname()} {$shadowuser->get_lastname()} ({$shadowuser->get_email()})",
                    ['class' => 'cell']
                )
            );

            echo html_writer::tag('tr',
                html_writer::tag('th', get_string('attempt_state', $MODULE), ['class' => 'cell']) .
                html_writer::tag('td', $attempt->get_state_readable(), ['class' => 'cell'])
            );

            echo html_writer::tag('tr',
                html_writer::tag('th', get_string('started_on', $MODULE), ['class' => 'cell']) .
                html_writer::tag('td', userdate($attempt->get_timestart()), ['class' => 'cell'])
            );

            if (!$attempt->is_in_progress()) {
                echo html_writer::tag('tr',
                    html_writer::tag('th', get_string('completed_on', $MODULE), ['class' => 'cell']) .
                    html_writer::tag('td', userdate($attempt->get_timeend()), ['class' => 'cell'])
                );

                echo html_writer::tag('tr',
                    html_writer::tag('th', get_string('duration', $MODULE), ['class' => 'cell']) .
                    html_writer::tag('td', format_time($attempt->get_timeend() - $attempt->get_timestart()), ['class' => 'cell'])
                );

                echo html_writer::tag('tr',
                    html_writer::tag('th', get_string('marks', $MODULE), ['class' => 'cell']) .
                    html_writer::tag('td',
                        format_float($attempt->get_total_mark(), 2) . '/' . format_float($attempt->get_max_mark(), 2),
                        ['class' => 'cell']
                    )
                );

                echo html_writer::tag('tr',
                    html_writer::tag('th', get_string('grade', $MODULE), ['class' => 'cell']) .
                    html_writer::tag('td',
                        get_string('outof', 'quiz', (object)[
                            'grade'    => html_writer::tag('b', quiz_format_grade($attempt->get_quizobj()->get_quiz(), $attempt->get_scaled_grade())),
                            'maxgrade' => quiz_format_grade($attempt->get_quizobj()->get_quiz(), $attempt->get_max_grade()),
                        ]) .
                        ' (' . html_writer::tag('b', format_float($attempt->get_percentage() * 100, 0)) . '%)',
                        ['class' => 'cell']
                    )
                );
            }

        echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
echo html_writer::end_div();



foreach ($quba->get_slots() as $slot) {
    echo $quba->render_question($slot, $displayoptions);
}


echo html_writer::div(
    html_writer::link(
        new moodle_url('/mod/quiz/report.php', [
            'id'     => $cm->id,
            'mode'   => 'overview', // default attempts list
        ]),
        'Finish review'
    ),
    'd-flex flex-row w-full justify-content-end'
);

echo $OUTPUT->footer();