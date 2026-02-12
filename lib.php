<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/quizcustom.php');

/**
 * Add public quiz settings to the quiz module form.
 *  
 * @param moodleform_mod $formwrapper The moodleform_mod instance
 * @param MoodleQuickForm $mform The form instance
 */
function local_publictestlink_coursemodule_standard_elements($formwrapper, $mform) {
    // Get current module info
    $current = $formwrapper->get_current();
    
    // Check if we're editing a quiz
    if (!isset($current->modulename) || $current->modulename !== 'quiz' || empty($current->instance)) {
        return;
    }

    $quizid = (int)$current->coursemodule;
    $quizcustom = publictestlink_quizcustom::from_quizid($quizid);
    
    $ispublic = false;
    if ($quizcustom !== null) {
        $ispublic = $quizcustom->get_ispublic();
    }


    $mform->insertElementBefore(
        $mform->createElement(
            'advcheckbox',
            'ispublic',
            'Make quiz public',
            'Allow anyone with the link to access this quiz without login',
            ['group' => 1],
            [0, 1]
        ),
        'timing'
    );

    $mform->setDefault('ispublic', $ispublic);
    $mform->setType('ispublic', PARAM_INT);
    $mform->addHelpButton('ispublic', 'makequizpublic', 'local_publictestlink');
}

/**
 * Save checkbox value
 */
function local_publictestlink_coursemodule_edit_post_actions($data) {
    if (!isset($data->modulename) || $data->modulename !== 'quiz' || empty($data->instance)) {
        return $data;
    }
    
    $quizid = (int)$data->coursemodule;
    
    // Get checkbox value
    $ispublic = (bool)optional_param('ispublic', 0, PARAM_INT);

    
    // Also check $data object in case Moodle processed it
    if (isset($data->ispublic)) {
        $ispublic = (bool)$data->ispublic;
    }

    $quizcustom = publictestlink_quizcustom::from_quizid($quizid);

    if ($quizcustom === null) {
        $quizcustom = publictestlink_quizcustom::create(
            $quizid, $ispublic
        );
    } else {
        $quizcustom->set_is_public($ispublic);
    }

    return $data;
}

// /**
//  * Delete public quiz records when a quiz is deleted.
//  *
//  * @param cm_info $cm The course module object
//  */
// function local_publictestlink_pre_course_module_delete($cm) {
//     if ($cm->modname !== 'quiz') {
//         return;
//     }

//     echo 'test';

//     $quizcustom = publictestlink_quizcustom::from_quizid($cm->id);
//     if ($quizcustom === null) return;

//     $quizcustom->delete();
// }