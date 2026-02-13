<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/classes/quizcustom.php');
require_once(__DIR__ . '/classes/link_token.php');

use core\output\html_writer;
use core\url as moodle_url;

/**
 * Add public quiz settings to the quiz module form.
 *  
 * @param moodleform_mod $formwrapper The moodleform_mod instance
 * @param MoodleQuickForm $mform The form instance
 */
function local_publictestlink_coursemodule_standard_elements($formwrapper, $mform) {
    global $PAGE;

    // Get current module info
    $current = $formwrapper->get_current();
    
    // Check if we're editing a quiz
    if (!isset($current->modulename) || $current->modulename !== 'quiz' || empty($current->instance)) {
        return;
    }

    $quizid = (int)$current->instance;
    $quizcustom = publictestlink_quizcustom::from_quizid($quizid);
    
    $ispublic = false;
    if ($quizcustom !== null) {
        $ispublic = $quizcustom->get_ispublic();
    }



    $mform->insertElementBefore(
        $mform->addElement('header', 'publicquizheader', get_string('publicquizsettings', 'local_publictestlink')),
        'timing'
    );
    $mform->setExpanded('publicquizheader', false);

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



    if ($ispublic) {
        $linktoken = publictestlink_link_token::ensure_for_quiz($quizid);

        $publicurl = new moodle_url(
            PLUGIN_URL . '/landing.php',
            ['token' => $linktoken->get_token()]
        );
    
        $url = $publicurl->out(false);
    
        $inputid  = 'publicquizlinkinput_' . $current->instance;
        $buttonid = 'publicquizlinkbtn_' . $current->instance;
    
        $linkhtml = html_writer::start_div('d-flex align-items-center gap-2');
    
        $linkhtml .= html_writer::empty_tag('input', [
            'type'     => 'text',
            'class'    => 'w-full form-control',
            'id'       => $inputid,
            'value'    => $url,
            'readonly' => 'readonly',
        ]);
    
        $linkhtml .= html_writer::tag(
            'button',
            get_string('public_url_copy', MODULE),
            [
                'type'  => 'button',
                'class' => 'btn btn-primary',
                'id'    => $buttonid,
            ]
        );
    
        $linkhtml .= html_writer::end_div();
    
        $mform->insertElementBefore(
            $mform->createElement(
                'static',
                'public_url',
                get_string('public_url', MODULE),
                $linkhtml
            ),
            'timing'
        );
    
        $PAGE->requires->js_init_code("
            (function() {
                const btn = document.getElementById('$buttonid');
                const input = document.getElementById('$inputid');
                if (!btn || !input) {
                    return;
                }
    
                btn.addEventListener('click', function() {
                    input.select();
    
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(input.value);
                    } else {
                        document.execCommand('copy');
                    }
    
                    alert('Copied!');
                });
            })();
        ");
    }
}

/**
 * Save checkbox value
 */
function local_publictestlink_coursemodule_edit_post_actions($data) {
    if (!isset($data->modulename) || $data->modulename !== 'quiz' || empty($data->instance)) {
        return $data;
    }
    
    $quizid = (int)$data->instance;
    
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


    $existinglink = publictestlink_link_token::from_quizid($quizid);

    if ($ispublic) {
        if ($existinglink === null) publictestlink_link_token::create($quizid);
    } else {
        if ($existinglink !== null) publictestlink_link_token::delete($quizid);
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