<?php
defined('MOODLE_INTERNAL') || die();
$capabilities = array(
    'local/pyrunner:run' => array(
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    ),
);
