<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add public quiz settings to the quiz module form.
 *
 * @param moodleform_mod $formwrapper The moodleform_mod instance
 * @param MoodleQuickForm $mform The form instance
 */
function local_publictestlink_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB, $PAGE;
    
    debugging("=== local_publictestlink_coursemodule_standard_elements CALLED ===", DEBUG_DEVELOPER);
    
    // Get current module info
    $current = $formwrapper->get_current();
    
    // Check if we're editing a quiz
    if (!isset($current->modulename) || $current->modulename !== 'quiz') {
        debugging("Not a quiz module - returning", DEBUG_DEVELOPER);
        return;
    }
    
    debugging("Editing a quiz - continuing", DEBUG_DEVELOPER);
    
    $currentvalue = 0;
    $currenthash = '';
    
    if (!empty($current->instance)) {
        debugging("Quiz instance ID: " . $current->instance, DEBUG_DEVELOPER);
        
        try {
            $record = $DB->get_record('local_publictestlink', ['quizid' => $current->instance]);
            
            if ($record) {
                $currentvalue = (int)$record->ispublic;
                $currenthash = $record->hash;
                debugging("Found existing record: ispublic=$currentvalue, hash=" . ($currenthash ?: 'empty'), DEBUG_DEVELOPER);
            } else {
                debugging("No existing record found for quiz " . $current->instance, DEBUG_DEVELOPER);
            }
        } catch (dml_exception $e) {
            debugging("Database error: " . $e->getMessage(), DEBUG_DEVELOPER);
        }
    } else {
        debugging("No instance ID - might be creating new quiz", DEBUG_DEVELOPER);
    }
    
    // Create form element group
    $mform->addElement('header', 'publicquizheader', get_string('publicquizsettings', 'local_publictestlink'));
    $mform->setExpanded('publicquizheader');
    
    // Add checkbox
    $mform->addElement('advcheckbox', 'publicquiz', 
        get_string('makequizpublic', 'local_publictestlink'),
        get_string('makequizpublic_desc', 'local_publictestlink'),
        array('group' => 1),
        array(0, 1)
    );
    
    $mform->setDefault('publicquiz', $currentvalue);
    $mform->setType('publicquiz', PARAM_INT);
    $mform->addHelpButton('publicquiz', 'makequizpublic', 'local_publictestlink');
    
    // Show public URL if quiz is already public
    if ($currentvalue && !empty($currenthash)) {
        $publicurl = new moodle_url('/local/publictestlink/view.php', ['h' => $currenthash]);
        $mform->addElement('static', 'publicurl', 
            get_string('publicurl', 'local_publictestlink'),
            html_writer::link($publicurl, $publicurl->out(), ['target' => '_blank']) . 
            '<br/><small>' . get_string('publicurl_desc', 'local_publictestlink') . '</small>'
        );
        $mform->addElement('static', 'publicurl_info', '', 
            get_string('publicurl_warning', 'local_publictestlink')
        );
    }
    
    // Try to move it to a more visible position
    if ($mform->elementExists('name')) {
        $mform->insertElementBefore($mform->getElement('publicquizheader'), 'name');
    }
    
    debugging("Form elements added successfully", DEBUG_DEVELOPER);
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
    
    debugging("=== local_publictestlink_coursemodule_edit_post_actions CALLED ===", DEBUG_DEVELOPER);
    
    if (!isset($data->modulename) || $data->modulename !== 'quiz' || empty($data->instance)) {
        debugging("Not processing - not a quiz or no instance", DEBUG_DEVELOPER);
        return $data;
    }
    
    $quizid = $data->instance;
    debugging("Processing quiz ID: $quizid", DEBUG_DEVELOPER);
    
    // Get checkbox value - use optional_param which handles missing POST values
    $ispublic = optional_param('publicquiz', 0, PARAM_INT);
    debugging("Checkbox value (optional_param): $ispublic", DEBUG_DEVELOPER);
    
    // Also check $data object in case Moodle processed it
    if (isset($data->publicquiz)) {
        $ispublic = (int)$data->publicquiz;
        debugging("Checkbox value from \$data->publicquiz: $ispublic", DEBUG_DEVELOPER);
    }
    
    debugging("Final ispublic value to save: $ispublic", DEBUG_DEVELOPER);
    
    try {
        $record = $DB->get_record('local_publictestlink', ['quizid' => $quizid]);
        $now = time();
        
        if ($record) {
            // Update existing record - NEVER DELETE, just update ispublic
            $record->ispublic = $ispublic;
            $record->timemodified = $now;
            
            // Only generate hash if becoming public and hash is empty
            if ($ispublic == 1 && empty($record->hash)) {
                $record->hash = md5(uniqid($quizid . '_' . $now, true));
            }
            
            $DB->update_record('local_publictestlink', $record);
            debugging("Updated record for quiz $quizid - ispublic=$ispublic", DEBUG_DEVELOPER);
        } else {
            // Create new record only if ispublic is 1
            if ($ispublic == 1) {
                $newrecord = (object)[
                    'quizid' => $quizid,
                    'ispublic' => 1,
                    'timecreated' => $now,
                    'timemodified' => $now,
                    'hash' => md5(uniqid($quizid . '_' . $now, true))
                ];
                $DB->insert_record('local_publictestlink', $newrecord);
                debugging("Created new record for quiz $quizid - ispublic=1", DEBUG_DEVELOPER);
            } else {
                // Don't create record if ispublic is 0 and no record exists
                debugging("No record exists and ispublic=0 - nothing to do", DEBUG_DEVELOPER);
            }
        }
    } catch (dml_exception $e) {
        debugging("Database error: " . $e->getMessage(), DEBUG_DEVELOPER);
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
    
    debugging("Deleting public quiz record for quiz ID: " . $cm->instance, DEBUG_DEVELOPER);
    
    try {
        $DB->delete_records('local_publictestlink', ['quizid' => $cm->instance]);
    } catch (dml_exception $e) {
        debugging("Error deleting public quiz record: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
}