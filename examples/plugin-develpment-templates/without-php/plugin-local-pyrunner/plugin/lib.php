<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');

function local_pyrunner_analyze(string $text): array {
    $curl = new curl();
    $headers = ['Content-Type: application/json'];
    $payload = json_encode(['text' => $text]);

    $resp = $curl->post('http://localhost:5050/api/analyze', $payload, $headers);
    $data = json_decode($resp, true);
    if (!is_array($data)) {
        return ['error' => 'Invalid response from microservice'];
    }
    return $data;
}
