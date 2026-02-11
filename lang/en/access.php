<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'local/publictestlink:manage' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ),
    ),
);