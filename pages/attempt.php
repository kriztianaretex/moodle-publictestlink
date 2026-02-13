<?php
require_once('../../../config.php');
require_once('../locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');
require_once('../classes/access_manager.php');
require_once('../classes/link_token.php');

use core\url as moodle_url;
use core\output\html_writer;
use core\notification;
use mod_quiz\quiz_settings;


$PAGE->set_cacheable(false);

$token = required_param('token', PARAM_ALPHANUMEXT);

$linktoken = publictestlink_link_token::require_token($token);

$session = publictestlink_session::check_session();
if ($session === null) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token]));
    return;
}

$quizid = $linktoken->get_quizid();
$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

$shadowuserid = $session->get_user()->get_id();
$attempt = publictestlink_attempt::require_attempt($quizid, $shadowuserid);

$timenow = time();
$accessmanager = new publictestlink_access_manager($quizobj, $timenow, $session->get_user(), $attempt);
$reasons = $accessmanager->get_formatted_reasons();
if ($reasons !== null) {
    redirect('/', $reasons, null, notification::ERROR);
    return;
}

if (
    $attempt->get_shadow_user()->get_id() !== $session->get_user()->get_id() ||
    !$attempt->is_in_progress()
) {
    redirect(
        new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token])
    );
    return;
}

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);

$PAGE->requires->css('/local/publictestlink/styles.css');
$PAGE->add_body_class('landing-body');
$PAGE->set_url($PLUGIN_URL . '/attempt.php', ['token' => $token]);
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
    'action' => new moodle_url($PLUGIN_URL . '/process.php', ['token' => $token]),
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