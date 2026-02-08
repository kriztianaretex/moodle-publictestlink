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

    // First, let's find what field comes after introeditor
    // The common pattern is that after introeditor comes the "showdescription" checkbox
    // Let's check if showdescription exists
    if ($mform->elementExists('showdescription')) {
        // We'll add our toggle after showdescription
        $publicquiz = $mform->createElement(
            'advcheckbox',
            'publicquiz',
            get_string('makequizpublic', 'local_publictestlink')
        );
        
        $mform->setDefault('publicquiz', 0);
        $mform->addHelpButton('publicquiz', 'makequizpublic', 'local_publictestlink');
        
        // Create a group with showdescription and our toggle
        $group = [];
        if ($showdesc = $mform->getElement('showdescription')) {
            $group[] = $showdesc;
        }
        $group[] = $publicquiz;
        
        $mform->addGroup($group, 'quizdisplaygroup', '', array(' ', ' '), false);
        
        // Replace the original showdescription with our group
        $mform->insertElementBefore($mform->getElement('quizdisplaygroup'), 'showdescription');
        $mform->removeElement('showdescription');
    } else {
        // If showdescription doesn't exist, let's add our toggle after introeditor
        // by inserting it before the next element
        $elements = $mform->_elements;
        $found_introeditor = false;
        $next_element_name = null;
        
        foreach ($elements as $element) {
            if ($found_introeditor && isset($element->_attributes['name'])) {
                $next_element_name = $element->_attributes['name'];
                break;
            }
            
            if (isset($element->_attributes['name']) && $element->_attributes['name'] === 'introeditor') {
                $found_introeditor = true;
            }
        }
        
        if ($next_element_name) {
            $publicquiz = $mform->createElement(
                'advcheckbox',
                'publicquiz',
                get_string('makequizpublic', 'local_publictestlink')
            );
            
            $mform->setDefault('publicquiz', 0);
            $mform->addHelpButton('publicquiz', 'makequizpublic', 'local_publictestlink');
            
            $mform->insertElementBefore($publicquiz, $next_element_name);
        } else {
            // Last resort: add it to the form
            $mform->addElement(
                'advcheckbox',
                'publicquiz',
                get_string('makequizpublic', 'local_publictestlink')
            );
            $mform->setDefault('publicquiz', 0);
            $mform->addHelpButton('publicquiz', 'makequizpublic', 'local_publictestlink');
        }
    }
}