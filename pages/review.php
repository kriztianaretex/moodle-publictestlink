<?php
require_once('../../../config.php');
require_once('../locallib.php');

require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');

use core\url as moodle_url;
use core\notification;
use core\output\html_writer;

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

if ($attempt->get_shadow_user()->get_id() !== $session->get_user()->get_id()) {
    redirect(
        new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid])
    );
    return;
}

if ($attempt->is_in_progress()) {
    redirect(new moodle_url($PLUGIN_URL . '/attempt.php', ['attemptid' => $attemptid, 'cmid' => $cmid]));
}

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);

$PAGE->set_url($PLUGIN_URL . '/review.php', ['attemptid' => $attemptid, 'cmid' => $cmid]);
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