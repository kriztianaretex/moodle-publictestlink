<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add public quiz settings and result history to the quiz module form.
 *  
 * @param moodleform_mod $formwrapper The moodleform_mod instance
 * @param MoodleQuickForm $mform The form instance
 */
function local_publictestlink_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB;
    
    // Get current module info
    $current = $formwrapper->get_current();
    
    // Check if we're editing a quiz
    if (!isset($current->modulename) || $current->modulename !== 'quiz') {
        return;
    }
    
    $ispublic = 0;
    
    if (!empty($current->instance)) {
        $record = $DB->get_record('local_publictestlink', ['quizid' => $current->instance]);
        
        if ($record) {
            $ispublic = (int)$record->ispublic;
        }
        // If no record exists, $ispublic remains 0
    }
    
    // Create form element group
    $mform->addElement('header', 'publicquizheader', get_string('publicquizsettings', 'local_publictestlink'));
    $mform->setExpanded('publicquizheader');
    
    // Add checkbox element
    $mform->addElement('advcheckbox', 'publicquiz', 
        get_string('makequizpublic', 'local_publictestlink'),
        get_string('makequizpublic_desc', 'local_publictestlink'),
        array('group' => 1),
        array(0, 1)
    );
    
    $mform->setDefault('publicquiz', $ispublic);
    $mform->setType('publicquiz', PARAM_INT);
    $mform->addHelpButton('publicquiz', 'makequizpublic', 'local_publictestlink');
    
    // Try to move it to a more visible position
    if ($mform->elementExists('name')) {
        $mform->insertElementBefore($mform->getElement('publicquizheader'), 'name');
    }
}

/**
 * Add result history settings below display options in quiz form
 */
function local_publictestlink_coursemodule_mod_form($formwrapper, $mform) {
    global $DB;
    
    // Get current module info
    $current = $formwrapper->get_current();
    
    // Check if we're editing a quiz
    if (!isset($current->modulename) || $current->modulename !== 'quiz') {
        return;
    }
    
    // Add Result History section - This will appear after "Display" options
    $mform->addElement('header', 'resulthistoryheader', get_string('resulthistory', 'local_publictestlink'));
    $mform->setExpanded('resulthistoryheader', false);
    
    // Enable result history checkbox element
    $mform->addElement('advcheckbox', 'enableresulthistory', 
        get_string('enableresulthistory', 'local_publictestlink'),
        get_string('enableresulthistory_desc', 'local_publictestlink'),
        array('group' => 1),
        array(0, 1)
    );
    $mform->setDefault('enableresulthistory', 0);
    $mform->setType('enableresulthistory', PARAM_INT);
    
    // Maximum attempts to keep element
    $attemptsoptions = array(
        0 => get_string('keepall', 'local_publictestlink'),
        5 => '5',
        10 => '10', 
        20 => '20',
        50 => '50',
        100 => '100'
    );
    
    $mform->addElement('select', 'maxhistoryattempts', 
        get_string('maxhistoryattempts', 'local_publictestlink'),
        $attemptsoptions
    );
    $mform->setType('maxhistoryattempts', PARAM_INT);
    $mform->setDefault('maxhistoryattempts', 0);
    $mform->addHelpButton('maxhistoryattempts', 'maxhistoryattempts', 'local_publictestlink');
    $mform->disabledIf('maxhistoryattempts', 'enableresulthistory', 'notchecked');
    
    // Show history to students element
    $mform->addElement('advcheckbox', 'showhistorytostudents', 
        get_string('showhistorytostudents', 'local_publictestlink'),
        get_string('showhistorytostudents_desc', 'local_publictestlink'),
        array('group' => 1),
        array(0, 1)
    );
    $mform->setDefault('showhistorytostudents', 0);
    $mform->setType('showhistorytostudents', PARAM_INT);
    $mform->disabledIf('showhistorytostudents', 'enableresulthistory', 'notchecked');
    
    // Load existing values if editing
    if (!empty($current->instance)) {
        // Check if we have existing history settings
        $historyrecord = $DB->get_record('local_publictestlink_history', ['quizid' => $current->instance]);
        if ($historyrecord) {
            $mform->setDefault('enableresulthistory', $historyrecord->enabled);
            $mform->setDefault('maxhistoryattempts', $historyrecord->maxattempts);
            $mform->setDefault('showhistorytostudents', $historyrecord->showtostudents);
        }
    }
}

/**
 * Process the public quiz setting when quiz form is submitted.
 *
 * @param stdClass $data The form data
 * @param stdClass $course The course
 * @return stdClass Updated form data
 */
function local_publictestlink_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    
    if (!isset($data->modulename) || $data->modulename !== 'quiz' || empty($data->instance)) {
        return $data;
    }
    
    $quizid = $data->instance;
    
    // Process public quiz setting
    $ispublic = optional_param('publicquiz', 0, PARAM_INT);
    if (isset($data->publicquiz)) {
        $ispublic = (int)$data->publicquiz;
    }
    
    $record = $DB->get_record('local_publictestlink', ['quizid' => $quizid]);
    if ($record) {
        $record->ispublic = $ispublic;
        $DB->update_record('local_publictestlink', $record);
    } else {
        $newrecord = (object)[
            'quizid' => $quizid,
            'ispublic' => $ispublic
        ];
        $DB->insert_record('local_publictestlink', $newrecord);
    }
    
    // Process result history settings
    $enableresulthistory = optional_param('enableresulthistory', 0, PARAM_INT);
    $maxhistoryattempts = optional_param('maxhistoryattempts', 0, PARAM_INT);
    $showhistorytostudents = optional_param('showhistorytostudents', 0, PARAM_INT);
    
    // Store result history settings
    $historyrecord = $DB->get_record('local_publictestlink_history', ['quizid' => $quizid]);
    $timemodified = time();
    
    if ($historyrecord) {
        $historyrecord->enabled = $enableresulthistory;
        $historyrecord->maxattempts = $maxhistoryattempts;
        $historyrecord->showtostudents = $showhistorytostudents;
        $historyrecord->timemodified = $timemodified;
        $DB->update_record('local_publictestlink_history', $historyrecord);
    } else if ($enableresulthistory || $maxhistoryattempts > 0) {
        $newhistory = (object)[
            'quizid' => $quizid,
            'enabled' => $enableresulthistory,
            'maxattempts' => $maxhistoryattempts,
            'showtostudents' => $showhistorytostudents,
            'timecreated' => $timemodified,
            'timemodified' => $timemodified
        ];
        $DB->insert_record('local_publictestlink_history', $newhistory);
    }
    
    return $data;
}

/**
 * Delete public quiz records when a quiz is deleted.
 *
 * @param cm_info $cm The course module object
 */
function local_publictestlink_pre_course_module_delete($cm) {
    global $DB;
    
    if ($cm->modname !== 'quiz') {
        return;
    }
    
    $DB->delete_records('local_publictestlink', ['quizid' => $cm->instance]);
    $DB->delete_records('local_publictestlink_history', ['quizid' => $cm->instance]);
}

/**
 * Add Results Display Options to the quiz results page
 * This appears in the "Results" tab under "Display options"
 */
function local_publictestlink_quiz_report_display_options($quiz, $cm, $mode) {
    global $DB, $OUTPUT;
    
    // Only show on results pages
    if ($mode !== 'overview' && $mode !== 'responses' && $mode !== 'statistics') {
        return '';
    }
    
    $historyrecord = $DB->get_record('local_publictestlink_history', ['quizid' => $quiz->id]);
    
    if (!$historyrecord || !$historyrecord->enabled) {
        // Show "Nothing displayed" message when result history is not enabled
        return html_writer::div(
            get_string('nothingdisplayed', 'local_publictestlink'),
            'alert alert-info mt-3'
        );
    }
    
    $output = '';
    
    // Add Results Display Options section
    $output .= html_writer::start_div('card mt-3');
    $output .= html_writer::start_div('card-header');
    $output .= html_writer::tag('h5', get_string('resultsdisplayoptions', 'local_publictestlink'));
    $output .= html_writer::end_div();
    
    $output .= html_writer::start_div('card-body');
    
    // Check if there are any attempts to display
    $totalattempts = $DB->count_records('quiz_attempts', ['quiz' => $quiz->id, 'state' => 'finished']);
    
    if ($totalattempts === 0) {
        // Show "No attempts to display" message
        $output .= html_writer::div(
            get_string('noattemptstodisplay', 'local_publictestlink'),
            'alert alert-warning mb-3 text-center'
        );
    }
    
    // Results display format
    $output .= html_writer::start_tag('div', ['class' => 'form-group row']);
    $output .= html_writer::tag('label', get_string('resultsdisplayformat', 'local_publictestlink'), [
        'class' => 'col-md-4 col-form-label',
        'for' => 'id_resultsdisplayformat'
    ]);
    $output .= html_writer::start_tag('div', ['class' => 'col-md-8']);
    
    $displayoptions = [
        'detailed' => get_string('resultsdetailed', 'local_publictestlink'),
        'summary' => get_string('resultssummary', 'local_publictestlink'),
        'scoreonly' => get_string('resultsscoreonly', 'local_publictestlink')
    ];
    
    $currentformat = $historyrecord->resultsdisplayformat ?: 'summary';
    foreach ($displayoptions as $value => $label) {
        $checked = $currentformat === $value ? 'checked' : '';
        $output .= html_writer::start_tag('div', ['class' => 'form-check']);
        $output .= html_writer::tag('input', '', [
            'type' => 'radio',
            'name' => 'resultsdisplayformat',
            'id' => 'id_resultsdisplayformat_' . $value,
            'value' => $value,
            'class' => 'form-check-input',
            $checked => $checked
        ]);
        $output .= html_writer::tag('label', $label, [
            'class' => 'form-check-label',
            'for' => 'id_resultsdisplayformat_' . $value
        ]);
        $output .= html_writer::end_tag('div');
    }
    $output .= html_writer::end_tag('div');
    $output .= html_writer::end_tag('div');
    
    // Show correct answers checkbox
    $output .= html_writer::start_tag('div', ['class' => 'form-group row']);
    $output .= html_writer::start_tag('div', ['class' => 'col-md-8 offset-md-4']);
    $output .= html_writer::start_tag('div', ['class' => 'form-check']);
    $checked = $historyrecord->showcorrectanswers ? 'checked' : '';
    $output .= html_writer::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'showcorrectanswers',
        'id' => 'id_showcorrectanswers',
        'class' => 'form-check-input',
        $checked => $checked
    ]);
    $output .= html_writer::tag('label', get_string('showcorrectanswers', 'local_publictestlink'), [
        'class' => 'form-check-label',
        'for' => 'id_showcorrectanswers'
    ]);
    $output .= html_writer::end_tag('div');
    $output .= html_writer::end_tag('div');
    $output .= html_writer::end_tag('div');
    
    // Show feedback checkbox
    $output .= html_writer::start_tag('div', ['class' => 'form-group row']);
    $output .= html_writer::start_tag('div', ['class' => 'col-md-8 offset-md-4']);
    $output .= html_writer::start_tag('div', ['class' => 'form-check']);
    $checked = $historyrecord->showfeedback ? 'checked' : '';
    $output .= html_writer::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'showfeedback',
        'id' => 'id_showfeedback',
        'class' => 'form-check-input',
        $checked => $checked
    ]);
    $output .= html_writer::tag('label', get_string('showfeedback', 'local_publictestlink'), [
        'class' => 'form-check-label',
        'for' => 'id_showfeedback'
    ]);
    $output .= html_writer::end_tag('div');
    $output .= html_writer::end_tag('div');
    $output .= html_writer::end_tag('div');
    
    // Allow results download
    $output .= html_writer::start_tag('div', ['class' => 'form-group row']);
    $output .= html_writer::start_tag('div', ['class' => 'col-md-8 offset-md-4']);
    $output .= html_writer::start_tag('div', ['class' => 'form-check']);
    $checked = $historyrecord->allowresultsdownload ? 'checked' : '';
    $output .= html_writer::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'allowresultsdownload',
        'id' => 'id_allowresultsdownload',
        'class' => 'form-check-input',
        $checked => $checked
    ]);
    $output .= html_writer::tag('label', get_string('allowresultsdownload', 'local_publictestlink'), [
        'class' => 'form-check-label',
        'for' => 'id_allowresultsdownload'
    ]);
    $output .= html_writer::end_tag('div');
    $output .= html_writer::end_tag('div');
    $output .= html_writer::end_tag('div');
    
    // Download formats (only if download is enabled)
    if ($historyrecord->allowresultsdownload) {
        $output .= html_writer::start_tag('div', ['class' => 'form-group row']);
        $output .= html_writer::tag('label', get_string('resultsdownloadformats', 'local_publictestlink'), [
            'class' => 'col-md-4 col-form-label',
            'for' => 'id_resultsdownloadformats'
        ]);
        $output .= html_writer::start_tag('div', ['class' => 'col-md-8']);
        
        $downloadoptions = [
            'pdf' => get_string('formatpdf', 'local_publictestlink'),
            'csv' => get_string('formatcsv', 'local_publictestlink'),
            'both' => get_string('formatboth', 'local_publictestlink')
        ];
        
        $currentformat = $historyrecord->resultsdownloadformats ?: 'pdf';
        $output .= html_writer::select($downloadoptions, 'resultsdownloadformats', 
            $currentformat, false, ['class' => 'form-control', 'id' => 'id_resultsdownloadformats']);
        
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
    }
    
    // Save button (only show if there are attempts or settings to save)
    if ($totalattempts > 0 || $historyrecord) {
        $output .= html_writer::start_tag('div', ['class' => 'form-group row']);
        $output .= html_writer::start_tag('div', ['class' => 'col-md-8 offset-md-4']);
        $output .= html_writer::tag('button', get_string('saveresultsoptions', 'local_publictestlink'), [
            'type' => 'button',
            'class' => 'btn btn-primary',
            'id' => 'id_saveresultsoptions'
        ]);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
    }
    
    $output .= html_writer::end_div(); // card-body
    $output .= html_writer::end_div(); // card
    
    // Add JavaScript for saving options (only if there are attempts)
    if ($totalattempts > 0) {
        $output .= '
        <script>
        document.getElementById("id_saveresultsoptions").addEventListener("click", function() {
            var data = {
                quizid: ' . $quiz->id . ',
                resultsdisplayformat: document.querySelector(\'input[name="resultsdisplayformat"]:checked\').value,
                showcorrectanswers: document.getElementById("id_showcorrectanswers").checked ? 1 : 0,
                showfeedback: document.getElementById("id_showfeedback").checked ? 1 : 0,
                allowresultsdownload: document.getElementById("id_allowresultsdownload").checked ? 1 : 0
            };
            
            if (data.allowresultsdownload) {
                data.resultsdownloadformats = document.getElementById("id_resultsdownloadformats").value;
            }
            
            // Send AJAX request to save settings
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "' . new moodle_url('/local/publictestlink/ajax.php') . '", true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert("' . get_string('settingssaved', 'local_publictestlink') . '");
                } else {
                    alert("' . get_string('savefailed', 'local_publictestlink') . '");
                }
            };
            xhr.send(JSON.stringify(data));
        });
        </script>
        ';
    }
    
    return $output;
}

/**
 * Add quick results summary to results page
 */
function local_publictestlink_quiz_report_before_table($quiz, $cm, $mode) {
    global $DB, $OUTPUT;
    
    if ($mode !== 'overview') {
        return '';
    }
    
    $historyrecord = $DB->get_record('local_publictestlink_history', ['quizid' => $quiz->id]);
    
    if (!$historyrecord || !$historyrecord->enabled) {
        return '';
    }
    
    // Get quick stats
    $totalattempts = $DB->count_records('quiz_attempts', ['quiz' => $quiz->id, 'state' => 'finished']);
    
    if ($totalattempts === 0) {
        // Show "No data to display" message
        return html_writer::div(
            get_string('nodatatodisplay', 'local_publictestlink'),
            'alert alert-warning mt-3 text-center'
        );
    }
    
    $output = '';
    
    $uniqueusers = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT userid) 
         FROM {quiz_attempts} 
         WHERE quiz = ? AND state = 'finished'",
        [$quiz->id]
    );
    
    $avggrade = $DB->get_field_sql(
        "SELECT AVG(sumgrades) 
         FROM {quiz_attempts} 
         WHERE quiz = ? AND state = 'finished'",
        [$quiz->id]
    );
    
    $output .= html_writer::start_div('alert alert-info mt-3');
    $output .= html_writer::tag('h5', get_string('quickstats', 'local_publictestlink'));
    $output .= html_writer::start_tag('div', ['class' => 'row']);
    
    $output .= html_writer::start_tag('div', ['class' => 'col-md-4']);
    $output .= html_writer::tag('strong', $totalattempts);
    $output .= html_writer::tag('span', ' ' . get_string('totalattempts', 'local_publictestlink'));
    $output .= html_writer::end_tag('div');
    
    $output .= html_writer::start_tag('div', ['class' => 'col-md-4']);
    $output .= html_writer::tag('strong', $uniqueusers);
    $output .= html_writer::tag('span', ' ' . get_string('uniqueusers', 'local_publictestlink'));
    $output .= html_writer::end_tag('div');
    
    $output .= html_writer::start_tag('div', ['class' => 'col-md-4']);
    $output .= html_writer::tag('strong', format_float($avggrade, 2));
    $output .= html_writer::tag('span', ' ' . get_string('averagescore', 'local_publictestlink'));
    $output .= html_writer::end_tag('div');
    
    $output .= html_writer::end_tag('div');
    $output .= html_writer::end_div();
    
    return $output;
}

/**
 * Display message when no results are available
 */
function local_publictestlink_quiz_report_table($quiz, $cm, $mode) {
    global $DB;
    
    if ($mode !== 'overview') {
        return '';
    }
    
    $historyrecord = $DB->get_record('local_publictestlink_history', ['quizid' => $quiz->id]);
    
    if (!$historyrecord || !$historyrecord->enabled) {
        return '';
    }
    
    $totalattempts = $DB->count_records('quiz_attempts', ['quiz' => $quiz->id, 'state' => 'finished']);
    
    if ($totalattempts === 0) {
        // This function will be called after the main table, so we can add a message
        // However, in Moodle, we typically handle empty states differently
        return '';
    }
    
    return '';
}

/**
 * Display results to students after quiz completion
 */
function local_publictestlink_quiz_attempt_finished($event) {
    global $DB, $PAGE, $OUTPUT, $USER;
    
    $attemptid = $event->objectid;
    $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid]);
    
    if (!$attempt) {
        return;
    }
    
    $quizid = $attempt->quiz;
    $historyrecord = $DB->get_record('local_publictestlink_history', ['quizid' => $quizid]);
    
    if (!$historyrecord || !$historyrecord->enabled || !$historyrecord->showtostudents) {
        return;
    }
    
    $cm = get_coursemodule_from_instance('quiz', $quizid);
    
    // Check if user has any attempts
    $userattempts = $DB->count_records('quiz_attempts', [
        'quiz' => $quizid,
        'userid' => $USER->id,
        'state' => 'finished'
    ]);
    
    if ($userattempts === 0) {
        // Show "No results to display" message
        \core\notification::info(get_string('noresultsdisplay', 'local_publictestlink'));
        return;
    }
    
    // Check if we should show results immediately
    if ($historyrecord->showresultsimmediately) {
        $resultsurl = new moodle_url('/local/publictestlink/results.php', [
            'id' => $cm->id,
            'attempt' => $attemptid,
            'showresults' => 1
        ]);
        
        // Redirect to results page
        redirect($resultsurl);
    }
}