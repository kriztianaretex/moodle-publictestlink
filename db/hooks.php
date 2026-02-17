<?php
/**
 * Hook registration for local_publictestlink.
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => \local_publictestlink\hook_callbacks::class . '::before_footer_html_generation',
        'priority' => 0,
    ],
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => \local_publictestlink\hook_callbacks::class . '::quiz_tab',
        'priority' => 1,
    ],
];

