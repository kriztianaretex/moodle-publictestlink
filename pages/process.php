<?php
require_once('../../../config.php');
require_once('../locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');

use core\url as moodle_url;

$attemptid = required_param('attemptid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$isfinish = optional_param('finishattempt', false, PARAM_BOOL);

$PAGE->set_cacheable(false);

$session = publictestlink_session::check_session();
if (!$session) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid]));
}

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$attempt = publictestlink_attempt::from_id($attemptid);

// Shadow-user ownership check
if ($attempt->get_shadow_user()->get_id() !== $session->get_user()->get_id()) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid]));
}

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);

$timenow = time();

$quba->process_all_actions(
    $timenow,
    $_POST,
);

question_engine::save_questions_usage_by_activity($quba);

if ($isfinish) {
    $quba->finish_all_questions($timenow);
    question_engine::save_questions_usage_by_activity($quba);
    $attempt->mark_submitted($timenow);

    redirect(
        new moodle_url($PLUGIN_URL . '/review.php', [
            'attemptid' => $attemptid,
            'cmid'      => $cmid,
        ]),
        null,
        1000
    );
}

redirect(
    new moodle_url($PLUGIN_URL . '/attempt.php', [
        'attemptid' => $attemptid,
        'cmid'      => $cmid,
    ]),
    null,
    1000
);