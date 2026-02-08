<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add "Make quiz public" below the description editor.
 */
function local_publictestlink_coursemodule_standard_elements($formwrapper, $mform) {
    global $PAGE;
    
    $current = $formwrapper->get_current();

    // Only apply to quiz module
    if (empty($current->modulename) || $current->modulename !== 'quiz') {
        return;
    }


    $publicquiz = $mform->createElement(
        'advcheckbox',
        'publicquiz',
        get_string('makequizpublic', 'local_publictestlink')
    );
    $mform->insertElementBefore($publicquiz, 'name');
    
    $mform->setDefault('publicquiz', 0);
    $mform->addHelpButton('publicquiz', 'makequizpublic', 'local_publictestlink');
}