<?php
require_once('../../../config.php');
require_once('../locallib.php');

require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');
require_once('../classes/access_manager.php');
require_once('../classes/link_token.php');

use core\url as moodle_url;
use core\notification;
use core\output\html_writer;
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

$attempt = publictestlink_attempt::require_attempt($quizid, $shadowuserid, publictestlink_attempt::SUBMITTED);

$timenow = time();
$accessmanager = new publictestlink_access_manager($quizobj, $timenow, $session->get_user(), $attempt);
$reasons = $accessmanager->get_formatted_reasons();
if ($reasons !== null) {
    redirect('/', $reasons, null, notification::ERROR);
    return;
}

if ($attempt->get_shadow_user()->get_id() !== $session->get_user()->get_id()) {
    redirect(
        new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token])
    );
    return;
}

if ($attempt->is_in_progress()) {
    redirect(new moodle_url($PLUGIN_URL . '/attempt.php', ['token' => $token]));
}

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);

$PAGE->set_url($PLUGIN_URL . '/review.php', ['token' => $token]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Review');
$PAGE->set_heading('The results are in!');

$displayoptions = new question_display_options();
$displayoptions->readonly = true;
$displayoptions->marks = question_display_options::MARK_AND_MAX;
$displayoptions->correctness = question_display_options::VISIBLE;
$displayoptions->feedback = question_display_options::VISIBLE;
$displayoptions->rightanswer = question_display_options::VISIBLE;
$displayoptions->history = question_display_options::VISIBLE;


echo $OUTPUT->header();

foreach ($quba->get_slots() as $slot) {
    echo $quba->render_question($slot, $displayoptions);
}

echo html_writer::div(
    html_writer::link(
        new moodle_url($PLUGIN_URL . '/exit.php'),
        'Exit and finish review',
        ['class' => 'btn btn-danger']
    )
);

echo $OUTPUT->footer();