<?php
require_once('../../../config.php');
require_once('../locallib.php');

require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');
require_once('../classes/access_manager.php');
require_once('../classes/link_token.php');
require_once('../classes/user_header_writer.php');

use core\url as moodle_url;
use core\notification;
use core\output\html_writer;
use mod_quiz\quiz_settings;


// Reload page when accessed, never cache in the browser.
$PAGE->set_cacheable(false);

// Page parameters
$token = required_param('token', PARAM_ALPHANUMEXT);
$linktoken = publictestlink_link_token::require_token($token);

// Check if a session exists
$session = publictestlink_session::check_session();
if ($session === null) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token]));
    return;
}

$quizid = $linktoken->get_quizid();
$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

$shadowuserid = $session->get_user()->get_id();

// Require an existing submitted attempt
$attempt = publictestlink_attempt::require_attempt($quizid, $shadowuserid, publictestlink_attempt::SUBMITTED);

// Check if user can access this page
$timenow = time();
$accessmanager = new publictestlink_access_manager($quizobj, $timenow, $session->get_user(), $attempt);
$reasons = $accessmanager->get_formatted_reasons();
if ($reasons !== null) {
    redirect('/', $reasons, null, notification::ERROR);
    return;
}

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);


// Start writing page
$PAGE->set_url($PLUGIN_URL . '/review.php', ['token' => $token]);
$PAGE->add_body_class('landing-body');

$PAGE->set_pagelayout('incourse');
$PAGE->set_title('Review');

$PAGE->set_blocks_editing_capability(false);
$PAGE->set_secondary_navigation(false);
$PAGE->set_show_course_index(false);

$PAGE->set_course($quizobj->get_course());
$PAGE->set_cm($quizobj->get_cm());
$PAGE->set_context($context);

// Disable navbar
$PAGE->navbar->ignore_active(true);
foreach ($PAGE->navbar->get_items() as $node) {
    $node->action = null;
}


$displayoptions = new question_display_options();
$displayoptions->readonly = true;
$displayoptions->marks = question_display_options::MARK_AND_MAX;
$displayoptions->correctness = question_display_options::VISIBLE;
$displayoptions->feedback = question_display_options::VISIBLE;
$displayoptions->rightanswer = question_display_options::VISIBLE;
$displayoptions->history = question_display_options::VISIBLE;

echo $OUTPUT->header();

user_header_writer::write($session);

echo html_writer::start_div('d-flex mb-3 flex-row w-100 justify-content-center');
    echo html_writer::tag('h1', get_string('resultsin', MODULE));
echo html_writer::end_div();

// Render all questions
foreach ($quba->get_slots() as $slot) {
    echo $quba->render_question($slot, $displayoptions, $slot);
}

// Add exit button
echo html_writer::start_div('d-flex flex-row w-100 justify-content-end');
    echo html_writer::link(
        new moodle_url($PLUGIN_URL . '/exit.php'),
        'Exit and finish review',
        ['class' => 'btn btn-danger']
    );
echo html_writer::end_div();

echo $OUTPUT->footer();