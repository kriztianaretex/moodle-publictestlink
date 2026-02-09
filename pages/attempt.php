<?php
require_once('../../config.php');
require_once($CFG->libdir . '/questionlib.php');

$cmid = required_param('cmid', PARAM_INT);

$PAGE->set_context(context_module::instance($cm->id));
$PAGE->set_url('/local/publictestlink/pages/attempt.php', ['id' => $attemptid]);
$PAGE->set_pagelayout('standard');

$qubaid = required_param('qubaid', PARAM_INT);
$quba = question_engine::load_questions_usage_by_activity($qubaid);

$options = new question_display_options();
$options->marks = question_display_options::MAX_ONLY;
$options->markdp = 2;
$options->feedback = question_display_options::HIDDEN;
$options->correctness = question_display_options::HIDDEN;
$options->readonly = false;

$renderer = $PAGE->get_renderer('core_question');