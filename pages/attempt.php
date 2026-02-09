<?php
require_once('../../config.php');
require_once($CFG->libdir . '/questionlib.php');

$cmid = required_param('cmid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT);

$PAGE->set_context(context_module::instance($cmid));
$PAGE->set_url('/local/publictestlink/pages/attempt.php', ['id' => $attemptid]);
$PAGE->set_pagelayout('standard');

$qubaid = required_param('qubaid', PARAM_INT);
$quba = question_engine::load_questions_usage_by_activity($qubaid);

$displayoptions = new question_display_options();
$displayoptions->marks = question_display_options::MARK_AND_MAX;
$displayoptions->feedback = question_display_options::HIDDEN;
$displayoptions->generalfeedback = question_display_options::HIDDEN;
$displayoptions->rightanswer = question_display_options::HIDDEN;
$displayoptions->readonly = false;
$displayoptions->flags = question_display_options::VISIBLE;


$renderer = $PAGE->get_renderer('core_question');
foreach ($quba->get_slots() as $slot) {
    echo question_engine::render_question(
        $quba->get_question_attempt($slot),
        $displayoptions,
        $PAGE
    );
}