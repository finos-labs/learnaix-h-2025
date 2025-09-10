<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
admin_externalpage_setup('local_cortex');

/**
 * Parse Cortex Analyst streaming response from Server-Sent Events format
 * @param string $sse_response The raw SSE response from stdout
 * @return string The parsed and concatenated response text
 */
function parse_cortex_streaming_response($sse_response) {
    $response_text = '';
    
    // Split response into lines
    $lines = explode("\n", $sse_response);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Look for data lines with JSON content
        if (strpos($line, 'data: {') === 0) {
            // Extract JSON from data: prefix
            $json_str = substr($line, 6); // Remove 'data: ' prefix
            
            // Parse JSON
            $json_data = json_decode($json_str, true);
            
            // Extract text from delta content
            if (isset($json_data['delta']['content'][0]['text'])) {
                $response_text .= $json_data['delta']['content'][0]['text'];
            }
        }
    }
    
    return trim($response_text);
}

/**
 * Send filename and arguments to Flask API endpoint
 * @param string $filename The Python file to execute
 * @param array $arguments Optional arguments to pass to the script
 * @return array Result array with success status and data
 */
function send_script_to_flask_api($filename, $arguments = array()) {
    // Flask API endpoint for Python execution
    $flask_url = 'http://localhost:5000/api/execute';
    
    // Prepare the data to send
    $data = array(
        'filename' => $filename
    );
    
    // Add arguments if provided
    if (!empty($arguments)) {
        $data['arguments'] = $arguments;
    }
    
    // Convert to JSON
    $json_data = json_encode($data);
    
    // Initialize cURL
    $curl = curl_init();
    
    // Set cURL options
    curl_setopt_array($curl, array(
        CURLOPT_URL => $flask_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json_data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data)
        ),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ));
    
    // Execute the request
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    
    // Close cURL
    curl_close($curl);
    
    // Handle response
    if ($curl_error) {
        return array(
            'success' => false,
            'error' => 'cURL Error: ' . $curl_error
        );
    }
    
    if ($http_code !== 200) {
        return array(
            'success' => false,
            'error' => 'HTTP Error: ' . $http_code,
            'response' => $response
        );
    }
    
    // Parse JSON response
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array(
            'success' => false,
            'error' => 'JSON Parse Error: ' . json_last_error_msg(),
            'raw_response' => $response
        );
    }
    
    return array(
        'success' => true,
        'data' => $result
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('cortex_analyst', 'local_cortex'));

// Add Flask API communication
echo html_writer::start_div('flask-api-section', array('style' => 'margin: 20px 0; padding: 15px; border: 2px solid #4CAF50; border-radius: 8px;'));
echo html_writer::tag('h3', get_string('flask_api_title', 'local_cortex'), array('style' => 'color: #4CAF50; margin-top: 0;'));

// Handle form submission
$user_filename = optional_param('user_filename', '', PARAM_TEXT);
$user_question = optional_param('user_question', '', PARAM_TEXT);
$form_submitted = optional_param('submit_script', false, PARAM_BOOL);

if ($form_submitted && !empty($user_filename) && confirm_sesskey()) {
    // User submitted a filename and optional question - send to Flask
            echo html_writer::tag('p', get_string('executing_file', 'local_cortex') . ': <strong>' . htmlspecialchars($user_filename) . '</strong>');
    
    if (!empty($user_question)) {
                    echo html_writer::tag('p', get_string('user_question', 'local_cortex') . ': <em>' . htmlspecialchars($user_question) . '</em>');
    }
    
    // Prepare arguments as JSON object
    $arguments = array();
    if (!empty($user_question)) {
        $arguments = array(
            'question' => $user_question,
            'timestamp' => date('c'),
            'user_id' => $USER->id,
            'session_id' => sesskey()
        );
    }
    
    // Call Flask API with filename and JSON arguments
    $flask_result = send_script_to_flask_api($user_filename, $arguments);
    
    // Display results
    if ($flask_result['success']) {
        // Success - display the response
        $response_data = $flask_result['data'];
        
        if ($response_data['success']) {
            echo html_writer::start_div('success-response', array('style' => 'background-color: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'));
            echo html_writer::tag('h4', '✅ ' . get_string('execution_success', 'local_cortex'), array('style' => 'color: #2e7d32; margin-top: 0;'));
        } else {
            echo html_writer::start_div('warning-response', array('style' => 'background-color: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; border: 1px solid #ffeaa7;'));
            echo html_writer::tag('h4', '⚠️ ' . get_string('execution_warning', 'local_cortex'), array('style' => 'color: #856404; margin-top: 0;'));
        }
        
        echo html_writer::tag('p', '<strong>' . get_string('filename_executed', 'local_cortex') . ':</strong> ' . htmlspecialchars($response_data['filename']));
        echo html_writer::tag('p', '<strong>' . get_string('return_code', 'local_cortex') . ':</strong> ' . htmlspecialchars($response_data['return_code']));
        echo html_writer::tag('p', '<strong>' . get_string('execution_message', 'local_cortex') . ':</strong> ' . htmlspecialchars($response_data['message']));
        
        // Show JSON arguments if available
        if (!empty($response_data['json_arguments'])) {
            echo html_writer::tag('p', '<strong>JSON Arguments Sent:</strong> <code>' . htmlspecialchars($response_data['json_arguments']) . '</code>');
        }
        
        // Show stdout if available
        if (!empty($response_data['stdout'])) {
            echo html_writer::tag('h5', get_string('program_output', 'local_cortex'), array('style' => 'color: #2e7d32; margin-top: 15px; margin-bottom: 5px;'));
            
            // Parse streaming response if it looks like Server-Sent Events
            $stdout_content = $response_data['stdout'];
            $parsed_response = parse_cortex_streaming_response($stdout_content);
            
            if (!empty($parsed_response)) {
                // Display the clean parsed response
                echo html_writer::tag('div', $parsed_response, 
                     array('style' => 'background-color: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; line-height: 1.6; white-space: pre-wrap;'));
            } else {
                // Fallback to raw output if parsing fails
                echo html_writer::tag('pre', htmlspecialchars($stdout_content), 
                     array('style' => 'background-color: #f8f9fa; padding: 10px; border-radius: 3px; border-left: 4px solid #28a745; font-family: monospace; white-space: pre-wrap;'));
            }
        }
        
        // Show stderr if available
        if (!empty($response_data['stderr'])) {
            echo html_writer::tag('h5', get_string('error_output', 'local_cortex'), array('style' => 'color: #d32f2f; margin-top: 15px; margin-bottom: 5px;'));
            echo html_writer::tag('pre', htmlspecialchars($response_data['stderr']), 
                 array('style' => 'background-color: #f8f9fa; padding: 10px; border-radius: 3px; border-left: 4px solid #dc3545; font-family: monospace; white-space: pre-wrap;'));
        }
        
        // Show raw JSON response in a collapsible section
        echo html_writer::start_tag('details', array('style' => 'margin-top: 10px;'));
        echo html_writer::tag('summary', get_string('full_json_response', 'local_cortex'));
        echo html_writer::tag('pre', htmlspecialchars(json_encode($response_data, JSON_PRETTY_PRINT)), 
             array('style' => 'background-color: #f5f5f5; padding: 10px; border-radius: 3px; font-size: 12px;'));
        echo html_writer::end_tag('details');
        
        echo html_writer::end_div(); // success/warning-response
    } else {
        // Error - display the error message
        echo html_writer::start_div('error-response', array('style' => 'background-color: #ffe8e8; padding: 10px; border-radius: 5px; margin: 10px 0;'));
                    echo html_writer::tag('h4', '❌ ' . get_string('flask_error', 'local_cortex'), array('style' => 'color: #d32f2f; margin-top: 0;'));
        echo html_writer::tag('p', '<strong>' . get_string('error_message', 'local_cortex') . ':</strong> ' . htmlspecialchars($flask_result['error']), 
             array('style' => 'color: #d32f2f;'));
        
        if (isset($flask_result['response'])) {
            echo html_writer::start_tag('details');
            echo html_writer::tag('summary', get_string('raw_response', 'local_cortex'));
            echo html_writer::tag('pre', htmlspecialchars($flask_result['response']), 
                 array('style' => 'background-color: #f5f5f5; padding: 10px; border-radius: 3px; font-size: 12px;'));
            echo html_writer::end_tag('details');
        }
        
        echo html_writer::end_div(); // error-response
    }
    
    echo html_writer::tag('hr', '', array('style' => 'margin: 20px 0;'));
}

// Always show the input form
echo html_writer::tag('h4', get_string('enter_script_info', 'local_cortex'), array('style' => 'color: #4CAF50;'));
echo html_writer::tag('p', get_string('enter_script_description', 'local_cortex'));

// Create the form
echo html_writer::start_tag('form', array(
    'method' => 'post',
    'action' => new moodle_url('/local/cortex/index.php'),
    'style' => 'background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'
));

// Add CSRF token for security
echo html_writer::empty_tag('input', array(
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey()
));

// Filename input field
echo html_writer::start_div('form-group', array('style' => 'margin-bottom: 15px;'));
echo html_writer::tag('label', get_string('filename_label', 'local_cortex') . ':', 
     array('for' => 'user_filename', 'style' => 'font-weight: bold; display: block; margin-bottom: 5px;'));
echo html_writer::empty_tag('input', array(
    'type' => 'text',
    'name' => 'user_filename',
    'id' => 'user_filename',
    'value' => $form_submitted ? htmlspecialchars($user_filename) : 'cortex.py',
    'placeholder' => get_string('filename_placeholder', 'local_cortex'),
    'style' => 'width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;',
    'maxlength' => 100,
    'required' => 'required'
));
echo html_writer::end_div();

// Question/Query input field
echo html_writer::start_div('form-group', array('style' => 'margin-bottom: 15px;'));
echo html_writer::tag('label', get_string('question_label', 'local_cortex') . ':', 
     array('for' => 'user_question', 'style' => 'font-weight: bold; display: block; margin-bottom: 5px;'));
echo html_writer::tag('textarea', $form_submitted ? htmlspecialchars($user_question) : '', array(
    'name' => 'user_question',
    'id' => 'user_question',
    'rows' => '3',
    'placeholder' => get_string('question_placeholder', 'local_cortex'),
    'style' => 'width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;'
));
echo html_writer::end_div();

// Submit button
echo html_writer::empty_tag('input', array(
    'type' => 'submit',
    'name' => 'submit_script',
    'value' => get_string('execute_script', 'local_cortex'),
    'style' => 'background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;'
));

echo html_writer::end_tag('form');



echo html_writer::end_div(); // flask-api-section

echo $OUTPUT->footer();