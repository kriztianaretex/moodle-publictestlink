<?php
require_once('../../../config.php');
require_once('../locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');

use core\url as moodle_url;
use core\output\html_writer;
use core\notification;


$cmid = required_param('cmid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT);

$PAGE->set_cacheable(false);

$session = publictestlink_session::check_session();
if ($session == null) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid]));
    return;
}

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$attempt = publictestlink_attempt::from_id($attemptid);

if (
    $attempt->get_shadow_user()->get_id() !== $session->get_user()->get_id() ||
    !$attempt->is_in_progress()
) {
    redirect(
        new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid])
    );
    return;
}

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);

$PAGE->requires->css('/local/publictestlink/styles.css');
$PAGE->add_body_class('landing-body');
$PAGE->set_url($PLUGIN_URL . '/attempt.php', ['id' => $attemptid]);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($quiz->name);

$displayoptions = new question_display_options();
$displayoptions->marks = question_display_options::MARK_AND_MAX;
$displayoptions->feedback = question_display_options::HIDDEN;
$displayoptions->generalfeedback = question_display_options::HIDDEN;
$displayoptions->rightanswer = question_display_options::HIDDEN;
$displayoptions->readonly = false;
$displayoptions->flags = question_display_options::VISIBLE;



echo $OUTPUT->header();

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url($PLUGIN_URL . '/process.php', [
        'attemptid' => $attemptid,
        'cmid' => $cmid,
    ]),
]);

$questionrenderer = $PAGE->get_renderer('core_question');

foreach ($quba->get_slots() as $slot) {
    echo $quba->render_question($slot, $displayoptions);
}

echo html_writer::start_div('modal fade', [
    'id' => 'ptl-submit-modal',
    'tabindex' => '-1',
    'aria-hidden' => 'true',
]);

echo html_writer::start_div('modal-dialog modal-dialog-centered');

echo html_writer::start_div('modal-content');

// Header
echo html_writer::div(
    html_writer::tag('h5', 'Submit attempt?', ['class' => 'modal-title']) .
    html_writer::tag('button', '', [
        'type' => 'button',
        'class' => 'btn-close',
        'data-bs-dismiss' => 'modal',
        'aria-label' => 'Close',
    ]),
    'modal-header'
);

// Body
echo html_writer::div(
    html_writer::tag(
        'p',
        'Once you submit, you will no longer be able to change your answers.'
    ),
    'modal-body'
);

// Footer
echo html_writer::div(
    html_writer::tag('button', 'Cancel', [
        'type' => 'button',
        'class' => 'btn btn-secondary',
        'data-bs-dismiss' => 'modal',
    ]) .
    html_writer::tag('button', 'Yes, submit attempt', [
        'type'  => 'submit',
        'name'  => 'finishattempt',
        'value' => 'true',
        'class' => 'btn btn-primary ms-2',
    ]),
    'modal-footer'
);

echo html_writer::end_div(); // modal-content
echo html_writer::end_div(); // modal-dialog
echo html_writer::end_div(); // modal

echo html_writer::start_div('ptl-attempt-actions d-flex flex-row gap-2');

echo html_writer::tag('button', 'Save answers and don\'t submit', [
    'type'  => 'submit',
    'name'  => 'finishattempt',
    'value' => 'false',
    'class' => 'btn btn-secondary',
]);

echo html_writer::tag('button', 'Submit answers', [
    'type'  => 'button',
    'data-bs-toggle' => 'modal',
    'data-bs-target' => '#ptl-submit-modal',
    'class' => 'btn btn-primary',
]);

echo html_writer::end_div();
echo html_writer::end_tag('form');
echo $OUTPUT->footer();