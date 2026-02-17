<?php
require_once('../../../config.php');
require_once('../locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');
require_once('../classes/access_manager.php');
require_once('../classes/link_token.php');
require_once('../classes/user_header_writer.php');

use core\exception\moodle_exception;
use core\url as moodle_url;
use core\output\html_writer;
use core\notification;
use core_table\output\html_table;
use core_table\output\html_table_cell;
use mod_quiz\quiz_settings;

/** @var moodle_page $PAGE */


// Always reload page
$PAGE->set_cacheable(false);

// Page parameters
$token = required_param('token', PARAM_ALPHANUMEXT);

// Require valid token
$linktoken = publictestlink_link_token::require_token($token);

// Require valid session
$session = publictestlink_session::check_session();
if ($session === null) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token]));
    return;
}

$quizid = $linktoken->get_quizid();
$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

$cm = get_coursemodule_from_id('quiz', $quizobj->get_cmid(), 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
if (!$context) throw new moodle_exception('invalidcontext', $MODULE);

$shadowuserid = $session->get_user()->get_id();

// Require valid attempt
$attempt = publictestlink_attempt::require_attempt($quizid, $shadowuserid);

// Check if attempt is still valid
$timenow = time();
$accessmanager = new publictestlink_access_manager($quizobj, $timenow, $session->get_user(), $attempt);
$reasons = $accessmanager->get_formatted_reasons();
if ($reasons !== null) {
    redirect('/', $reasons, null, notification::ERROR);
}

// If the attempt is still in progress, back to landing you go
if (!$attempt->is_in_progress()) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token]));
}

$quba = $attempt->get_quba();



// Start writing page
$PAGE->set_url($PLUGIN_URL . '/attempt.php', ['token' => $token]);
$PAGE->requires->css('/local/publictestlink/styles.css');
$PAGE->add_body_class('landing-body');

$PAGE->set_pagelayout('incourse');
$PAGE->set_blocks_editing_capability(false);
$PAGE->set_secondary_navigation(false);
$PAGE->set_show_course_index(false);
$PAGE->set_title($quiz->name);
$PAGE->set_heading($course->fullname);

$PAGE->set_course($quizobj->get_course());
$PAGE->set_cm($cm);
$PAGE->set_context($context);

// Disable navbar
$PAGE->navbar->ignore_active(true);
foreach ($PAGE->navbar->get_items() as $node) {
    $node->action = null;
}


echo $OUTPUT->header();

// Write "Logged in as ..."
user_header_writer::write($session);


// Modal for confirming
echo html_writer::start_div('modal fade', [
    'id' => 'ptl-submit-modal',
    'tabindex' => '-1',
    'aria-hidden' => 'true',
]);
    echo html_writer::start_div('modal-dialog');
        echo html_writer::start_div('modal-content');
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

            echo html_writer::div(
                html_writer::tag(
                    'p',
                    'Once you submit, you will no longer be able to change your answers.'
                ),
                'modal-body'
            );

            echo html_writer::div(
                html_writer::tag('button', 'Cancel', [
                    'type' => 'button',
                    'class' => 'btn btn-secondary',
                    'data-bs-dismiss' => 'modal',
                ]) .

                html_writer::link(
                    new moodle_url($PLUGIN_URL . '/process.php', ['token' => $token, 'finishattempt' => true]),
                    get_string('submitallandfinish', 'quiz'),
                    ['class' => 'btn btn-primary']
                ),
                'modal-footer'
            );
        echo html_writer::end_div(); // modal-content
    echo html_writer::end_div(); // modal-dialog
echo html_writer::end_div(); // modal


// Start creating attempts table
echo html_writer::start_div('publictestlink-attempt-wrapper');
    // COPY PASTED FROM mod/quiz/classes/output/renderer.php, summary_table()

    $table = new html_table();
    $table->attributes['class'] = 'table generaltable table-striped quizsummaryofattempt table-hover';
    $table->head = [get_string('question', 'quiz'), get_string('status', 'quiz')];
    $table->align = ['left', 'left'];
    $table->size = ['', ''];
    $markscolumn = $displayoptions->marks >= question_display_options::MARK_AND_MAX;
    if ($markscolumn) {
        $table->head[] = get_string('marks', 'quiz');
        $table->align[] = 'left';
        $table->size[] = '';
    }
    $tablewidth = count($table->align);
    $table->data = [];

    // Get the summary info for each question.
    $slots = $quba->get_slots();

    function get_question_number($slot) {
        global $quba;
        $slots = $quba->get_slots();
        return array_search($slot, $slots) + 1;
    }

    foreach ($slots as $slot) {
        // Add a section headings if we need one here.
        // $heading = $quba->get_heading_before_slot($slot);
        // if ($heading !== null) {
        //     // There is a heading here.
        //     $rowclasses = 'quizsummaryheading';
        //     if ($heading) {
        //         $heading = format_string($heading);
        //     } else {
        //         if (count($attemptobj->get_quizobj()->get_sections()) > 1) {
        //             // If this is the start of an unnamed section, and the quiz has more
        //             // than one section, then add a default heading.
        //             $heading = get_string('sectionnoname', 'quiz');
        //             $rowclasses .= ' dimmed_text';
        //         }
        //     }
        //     $cell = new html_table_cell(format_string($heading));
        //     $cell->header = true;
        //     $cell->colspan = $tablewidth;
        //     $table->data[] = [$cell];
        //     $table->rowclasses[] = $rowclasses;
        // }

        // Don't display information items.
        if ($quba->get_question($slot, false)->length === 0) {
            continue;
        }

        // Real question, show it.
        $flag = '';
        if ($quba->get_question_attempt($slot)->is_flagged()) {
            // Quiz has custom JS manipulating these image tags - so we can't use the pix_icon method here.
            $flag = html_writer::empty_tag('img', ['src' => $this->image_url('i/flagged'),
                'alt' => get_string('flagged', 'question'), 'class' => 'questionflag icon ms-2']);
        }
        // if ($attemptobj->can_navigate_to($slot)) {
        //     $row = [html_writer::link($attemptobj->attempt_url($slot),
        //             $attemptobj->get_question_number($slot) . $flag),
        //             $attemptobj->get_question_status($slot, $displayoptions->correctness)];
        // } else {
        $row = [get_question_number($slot) . $flag,
                $quba->get_question_state_string($slot, $displayoptions->correctness)];
        // }
        // if ($markscolumn) {
        //     $row[] = $attemptobj->get_question_mark($slot);
        // }
        $table->data[] = $row;
        $table->rowclasses[] = 'quizsummary' . $slot . ' ' . $quba->get_question_attempt($slot)->get_state_class(false);
    }

    // Print the summary table.
    echo html_writer::table($table);

    // Buttons
    echo html_writer::start_div('d-flex mt-4 flex-column w-100 align-items-center gap-4');
        echo html_writer::link(
            new moodle_url($PLUGIN_URL . '/attempt.php', ['token' => $token]),
            get_string('returnattempt', 'quiz'),
            ['class' => 'btn btn-secondary']
        );

        echo html_writer::tag('button', get_string('submitallandfinish', 'quiz'), [
            'type'  => 'button',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#ptl-submit-modal',
            'class' => 'btn btn-primary',
        ]);
    echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();