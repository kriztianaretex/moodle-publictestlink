<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add public quiz settings to the quiz module form.
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
    
    // Add checkbox
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
    
    // Get checkbox value
    $ispublic = optional_param('publicquiz', 0, PARAM_INT);
    
    // Also check $data object in case Moodle processed it
    if (isset($data->publicquiz)) {
        $ispublic = (int)$data->publicquiz;
    }
    
    $record = $DB->get_record('local_publictestlink', ['quizid' => $quizid]);
    
    if ($record) {
        // Update existing record
        $record->ispublic = $ispublic;
        $DB->update_record('local_publictestlink', $record);
    } else if ($ispublic == 1) {
        // Create new record only if ispublic is 1
        $newrecord = (object)[
            'quizid' => $quizid,
            'ispublic' => 1
        ];
        $DB->insert_record('local_publictestlink', $newrecord);
    }
    // If ispublic is 0 and no record exists, do nothing
    
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
}