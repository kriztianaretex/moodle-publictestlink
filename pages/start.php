<?php

use mod_quiz\quiz_settings;

require_once('../../../config.php');
require_once($CFG->libdir . '/questionlib.php');

// From quiz ID
$quizid = required_param('quizid', PARAM_INT);
$quiz = $DB->get_record('quiz', ['id' => $quizid], '*');

$cm = get_coursemodule_from_instance('quiz', $quiz->id);

// Create Question Usage By Activity
$quba = question_engine::make_questions_usage_by_activity(
    'local_publictestlink',
    context_module::instance($cm->id)
);
$quba->set_preferred_behaviour('deferredfeedback');

// Slot then question Loading
$quizobj = quiz_settings::create($quiz->id);

foreach ($quizobj->get_structure()->get_slots() as $slot) {
    $question = question_bank::load_question($slot->questionid);

    $quba->add_question(
        $question,
        $slot->maxmark
    );
}

$quba->start_all_questions();

question_engine::save_questions_usage_by_activity($quba);