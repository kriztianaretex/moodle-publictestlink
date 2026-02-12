<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add Public Quiz checkbox to Quiz settings
 */
function local_publictestlink_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB;

    $current = $formwrapper->get_current();

    // Only for quiz module
    if (!isset($current->modulename) || $current->modulename !== 'quiz') {
        return;
    }

    // Get saved value
    $ispublic = 0;
    if (!empty($current->instance)) {
        $record = $DB->get_record('local_publictestlink_quizcustom', [
            'quizid' => $current->instance
        ]);
        if ($record) {
            $ispublic = (int)$record->enablepublicquiz;
        }
    }

    // Add checkbox
    $mform->addElement('advcheckbox',
        'enablepublicquiz',
        'Make quiz public',
        'Allow anyone with the link to access this quiz without login',
        ['group' => 1],
        [0, 1]
    );

    $mform->setDefault('enablepublicquiz', $ispublic);
    $mform->setType('enablepublicquiz', PARAM_INT);
    $mform->addHelpButton('enablepublicquiz', 'makequizpublic', 'local_publictestlink');

    // Move below "Display description on course page"
    $elements = $mform->_elements;
    $showpos = -1;
    $publicpos = -1;

    foreach ($elements as $pos => $element) {
        if (method_exists($element, 'getName')) {
            if ($element->getName() === 'showdescription') {
                $showpos = $pos;
            }
            if ($element->getName() === 'enablepublicquiz') {
                $publicpos = $pos;
            }
        }
    }

    if ($showpos !== -1 && $publicpos !== -1) {
        $el = $mform->_elements[$publicpos];
        unset($mform->_elements[$publicpos]);
        array_splice($mform->_elements, $showpos + 1, 0, [$el]);
        $mform->_elements = array_values($mform->_elements);
    }
}

/**
 * Add navigation bars to Timing and Grade sections - DIRECT OUTPUT METHOD
 */
function local_publictestlink_coursemodule_definition_after_data($formwrapper, $mform) {
    global $PAGE;

    $current = $formwrapper->get_current();
    if (!isset($current->modulename) || $current->modulename !== 'quiz') {
        return;
    }

    // DIRECT OUTPUT - THIS WILL DEFINITELY WORK
    echo '
    <style>
        /* Public Quiz Checkbox Styling */
        .fitem:has(input[name="enablepublicquiz"]) {
            border-left: 4px solid #0d6efd;
            background: #f0f7ff;
            padding: 12px 18px !important;
            border-radius: 8px;
            margin: 15px 0;
            transition: all 0.2s ease;
        }
        .fitem:has(input[name="enablepublicquiz"]):hover {
            background: #e3f2fd;
            border-left-width: 6px;
        }

        /* Navigation Bar Styling */
        .quiz-nav-bar {
            margin-left: 25px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 12px !important;
            font-size: 0.9rem !important;
        }

        .quiz-nav-btn {
            background: transparent !important;
            padding: 6px 16px !important;
            border-radius: 30px !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            border: 1.5px solid !important;
        }

        .quiz-nav-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1) !important;
        }

        .btn-timing {
            border-color: #0d6efd !important;
            color: #0d6efd !important;
        }

        .btn-timing:hover {
            background: #0d6efd !important;
            color: white !important;
        }

        .btn-grade {
            border-color: #198754 !important;
            color: #198754 !important;
        }

        .btn-grade:hover {
            background: #198754 !important;
            color: white !important;
        }

        .shortcut-badge {
            background: #e9ecef !important;
            padding: 4px 12px !important;
            border-radius: 30px !important;
            color: #495057 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 5px !important;
            font-size: 0.8rem !important;
            font-weight: 500 !important;
        }

        /* Section Highlight Animation */
        .section-highlight {
            animation: highlightPulse 1.5s ease;
            border: 3px solid #0d6efd !important;
            border-radius: 8px !important;
        }

        @keyframes highlightPulse {
            0% { box-shadow: 0 0 0 0 rgba(13,110,253,0.5); }
            70% { box-shadow: 0 0 0 15px rgba(13,110,253,0); }
            100% { box-shadow: 0 0 0 0 rgba(13,110,253,0); }
        }
    </style>

    <script>
    (function() {
        "use strict";
        
        console.log("🚀 Navigation injector STARTED");
        
        // ===== SCROLL FUNCTION =====
        window.scrollToTiming = function() {
            var el = document.getElementById("id_timing");
            if (el) {
                el.scrollIntoView({behavior: "smooth", block: "start"});
                el.classList.add("section-highlight");
                setTimeout(function() { el.classList.remove("section-highlight"); }, 2000);
            }
        };
        
        window.scrollToGrade = function() {
            var el = document.getElementById("id_grade");
            if (el) {
                el.scrollIntoView({behavior: "smooth", block: "start"});
                el.classList.add("section-highlight");
                setTimeout(function() { el.classList.remove("section-highlight"); }, 2000);
            }
        };
        
        // ===== TOGGLE FUNCTION =====
        window.toggleTiming = function() {
            var toggler = document.querySelector("#id_timing .ftoggler a");
            if (toggler) {
                toggler.click();
                console.log("⏱️ Toggled Timing section");
            }
        };
        
        window.toggleGrade = function() {
            var toggler = document.querySelector("#id_grade .ftoggler a");
            if (toggler) {
                toggler.click();
                console.log("📊 Toggled Grade section");
            }
        };
        
        // ===== INJECT NAVIGATION INTO TIMING HEADER =====
        function injectTimingNav() {
            var header = document.querySelector("#id_timing .ftoggler");
            if (!header) {
                console.log("⏳ Waiting for Timing header...");
                return false;
            }
            
            if (header.querySelector(".quiz-nav-bar")) {
                console.log("✅ Timing nav already exists");
                return true;
            }
            
            console.log("🎯 Injecting Timing navigation bar");
            
            var nav = document.createElement("span");
            nav.className = "quiz-nav-bar";
            
            // Shortcut badge
            var shortcut = document.createElement("span");
            shortcut.className = "shortcut-badge";
            shortcut.innerHTML = "⌨️ Alt+T";
            nav.appendChild(shortcut);
            
            // Jump to Grade button
            var jumpBtn = document.createElement("button");
            jumpBtn.className = "quiz-nav-btn btn-grade";
            jumpBtn.innerHTML = "📊 Jump to Grade";
            jumpBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.scrollToGrade();
            };
            nav.appendChild(jumpBtn);
            
            // Toggle Grade button
            var toggleBtn = document.createElement("button");
            toggleBtn.className = "quiz-nav-btn btn-timing";
            toggleBtn.innerHTML = "⏱️ Toggle Grade";
            toggleBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.toggleGrade();
            };
            nav.appendChild(toggleBtn);
            
            header.appendChild(nav);
            console.log("✅ Timing navigation bar ADDED");
            return true;
        }
        
        // ===== INJECT NAVIGATION INTO GRADE HEADER =====
        function injectGradeNav() {
            var header = document.querySelector("#id_grade .ftoggler");
            if (!header) {
                console.log("⏳ Waiting for Grade header...");
                return false;
            }
            
            if (header.querySelector(".quiz-nav-bar")) {
                console.log("✅ Grade nav already exists");
                return true;
            }
            
            console.log("🎯 Injecting Grade navigation bar");
            
            var nav = document.createElement("span");
            nav.className = "quiz-nav-bar";
            
            // Shortcut badge
            var shortcut = document.createElement("span");
            shortcut.className = "shortcut-badge";
            shortcut.innerHTML = "⌨️ Alt+G";
            nav.appendChild(shortcut);
            
            // Jump to Timing button
            var jumpBtn = document.createElement("button");
            jumpBtn.className = "quiz-nav-btn btn-timing";
            jumpBtn.innerHTML = "⏱️ Jump to Timing";
            jumpBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.scrollToTiming();
            };
            nav.appendChild(jumpBtn);
            
            // Toggle Timing button
            var toggleBtn = document.createElement("button");
            toggleBtn.className = "quiz-nav-btn btn-grade";
            toggleBtn.innerHTML = "📊 Toggle Timing";
            toggleBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.toggleTiming();
            };
            nav.appendChild(toggleBtn);
            
            header.appendChild(nav);
            console.log("✅ Grade navigation bar ADDED");
            return true;
        }
        
        // ===== KEYBOARD SHORTCUTS =====
        document.addEventListener("keydown", function(e) {
            if (e.altKey && e.key === "t") {
                e.preventDefault();
                window.scrollToTiming();
            }
            if (e.altKey && e.key === "g") {
                e.preventDefault();
                window.scrollToGrade();
            }
        });
        
        // ===== TRY IMMEDIATELY =====
        injectTimingNav();
        injectGradeNav();
        
        // ===== KEEP TRYING EVERY 300ms =====
        var attempts = 0;
        var maxAttempts = 30;
        var timer = setInterval(function() {
            var timingDone = injectTimingNav();
            var gradeDone = injectGradeNav();
            attempts++;
            
            if ((timingDone && gradeDone) || attempts >= maxAttempts) {
                clearInterval(timer);
                console.log("🏁 Navigation injection complete after " + attempts + " attempts");
            }
        }, 300);
        
        // ===== MUTATION OBSERVER BACKUP =====
        var observer = new MutationObserver(function(mutations) {
            injectTimingNav();
            injectGradeNav();
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Stop observer after 10 seconds
        setTimeout(function() {
            observer.disconnect();
            console.log("⏹️ Mutation observer stopped");
        }, 10000);
        
        console.log("🚀 Navigation injector RUNNING");
    })();
    </script>
    ';
}

/**
 * Save checkbox value
 */
function local_publictestlink_coursemodule_edit_post_actions($data, $course) {
    global $DB;

    if (!isset($data->modulename) || $data->modulename !== 'quiz' || empty($data->instance)) {
        return $data;
    }

    $quizid = $data->instance;
    $value = isset($data->enablepublicquiz) ? (int)$data->enablepublicquiz : 0;

    $record = $DB->get_record('local_publictestlink_quizcustom', [
        'quizid' => $quizid
    ]);

    if ($record) {
        if ($value == 1) {
            $record->enablepublicquiz = 1;
            $DB->update_record('local_publictestlink_quizcustom', $record);
        } else {
            $DB->delete_records('local_publictestlink_quizcustom', ['id' => $record->id]);
        }
    } else if ($value == 1) {
        $DB->insert_record('local_publictestlink_quizcustom', (object)[
            'quizid' => $quizid,
            'enablepublicquiz' => 1
        ]);
    }

    return $data;
}

/**
 * Delete record when quiz deleted
 */
function local_publictestlink_pre_course_module_delete($cm) {
    global $DB;

    if ($cm->modname === 'quiz') {
        $DB->delete_records('local_publictestlink_quizcustom', [
            'quizid' => $cm->instance
        ]);
    }
}