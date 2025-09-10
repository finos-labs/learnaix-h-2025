<?php
// Moodle standard header
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
admin_externalpage_setup('local_cortexanalyst');

// Ensure the code is wrapped in a Moodle form or page
$PAGE->set_url('/local/cortexanalyst/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Cortex Analyst');
$PAGE->set_heading('Cortex Analyst Integration');

// We'll put the logic in a function to keep it clean
function callCortexAnalystAPI(string $prompt): array {
    global $CFG;
    
    // Get settings from Moodle admin configuration
    $SNOWFLAKE_HOST = get_config('local_cortexanalyst', 'snowflake_host');
    $MODEL_PATH = get_config('local_cortexanalyst', 'model_path');
    $tokenFile = get_config('local_cortexanalyst', 'token_file');
    
    // Fallback to environment variables if settings not configured
    if (empty($SNOWFLAKE_HOST)) {
        $SNOWFLAKE_HOST = getenv("SNOWFLAKE_HOST");
    }
    if (empty($MODEL_PATH)) {
        $MODEL_PATH = "@MOODLE_APP.PUBLIC.MOUNTED/moodledata/revenue_timeseries.yaml";
    }
    if (empty($tokenFile)) {
        $tokenFile = "/snowflake/session/token";
    }
    
    if (empty($SNOWFLAKE_HOST)) {
        return ['error' => 'Error: Snowflake host not configured. Please set it in plugin settings or SNOWFLAKE_HOST environment variable.'];
    }
    
    $ANALYST_ENDPOINT = "/api/v2/cortex/agent:run";
    $URL = "https://" . $SNOWFLAKE_HOST . $ANALYST_ENDPOINT;

    // Get the login token
    if (!file_exists($tokenFile)) {
        return ['error' => 'Error: OAuth token file not found at: ' . $tokenFile];
    }
    $token = trim(file_get_contents($tokenFile));
    
    if (empty($token)) {
        return ['error' => 'Error: OAuth token is empty.'];
    }

    // Build the request body
    $request_body = [
        "model" => "llama3.1-8b",
        "messages" => [
            [
                "role" => "user",
                "content" => [["type" => "text", "text" => $prompt]],
            ]
        ],
        "tools" => [
            [
                "tool_spec" => [
                    "type" => "cortex_analyst_text_to_sql",
                    "name" => "Analyst1",
                ],
            ]
        ],
        "tool_resources" => [
            "Analyst1" => ["semantic_model_file" => $MODEL_PATH],
        ],
    ];

    // Initialize Moodle's cURL class
    $curl = new curl();
    
    // Configure cURL for external API calls
    $curl->setopt([
        'CURLOPT_TIMEOUT' => 30,
        'CURLOPT_CONNECTTIMEOUT' => 10,
        'CURLOPT_FOLLOWLOCATION' => true,
        'CURLOPT_SSL_VERIFYPEER' => true,
        'CURLOPT_USERAGENT' => 'Moodle/CortexAnalyst Plugin',
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_HTTPHEADER' => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'X-Snowflake-Authorization-Token-Type: OAUTH'
        ]
    ]);
    
    // Debug information
    $debug_info = [
        'url' => $URL,
        'has_token' => !empty($token),
        'token_length' => strlen($token),
        'request_size' => strlen(json_encode($request_body))
    ];
    
    try {
        $response = $curl->post($URL, json_encode($request_body));
        $http_code = $curl->get_info()['http_code'] ?? 0;
        
        if ($http_code !== 200) {
            $error_details = [
                'http_code' => $http_code,
                'response' => $response,
                'url' => $URL,
                'curl_error' => $curl->get_errno() ? $curl->error : 'No cURL error',
                'debug_info' => $debug_info
            ];
            return ['error' => "API request failed", 'details' => $error_details];
        }
    } catch (Exception $e) {
        return ['error' => 'cURL Exception: ' . $e->getMessage(), 'debug_info' => $debug_info];
    }
    
    // Check if this is a Server-Sent Events (SSE) response from Snowflake
    if (strpos($response, 'event: message.delta') !== false) {
        return parseSSEResponse($response, $debug_info);
    }
    
    // Try to decode as regular JSON
    $decoded_response = json_decode($response, true);
    
    // Handle JSON decode errors
    if ($decoded_response === null && json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => 'JSON decode error: ' . json_last_error_msg(),
            'raw_response' => $response,
            'debug_info' => $debug_info
        ];
    }
    
    // Handle empty response
    if ($decoded_response === null) {
        return [
            'error' => 'Empty or null response from API',
            'raw_response' => $response,
            'debug_info' => $debug_info
        ];
    }
    
    return $decoded_response;
}

// Function to parse Server-Sent Events (SSE) response from Snowflake Cortex
function parseSSEResponse(string $sseData, array $debug_info): array {
    $lines = explode("\n", $sseData);
    $content_pieces = [];
    $message_id = '';
    $sql_query = '';
    $interpretation = '';
    $analysis_results = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and event lines
        if (empty($line) || strpos($line, 'event:') === 0) {
            continue;
        }
        
        // Process data lines
        if (strpos($line, 'data:') === 0) {
            $json_data = substr($line, 5); // Remove "data:" prefix
            $json_data = trim($json_data);
            
            // Skip [DONE] marker
            if ($json_data === '[DONE]') {
                break;
            }
            
            // Parse the JSON chunk
            $chunk = json_decode($json_data, true);
            if ($chunk && isset($chunk['delta']['content'])) {
                $message_id = $chunk['id'] ?? $message_id;
                
                // Process different content types
                foreach ($chunk['delta']['content'] as $content_item) {
                    switch ($content_item['type']) {
                        case 'text':
                            // Regular text response
                            $content_pieces[] = $content_item['text'];
                            break;
                            
                        case 'tool_use':
                            // Tool usage (usually the query being processed)
                            if (isset($content_item['tool_use']['input']['query'])) {
                                $analysis_results['original_query'] = $content_item['tool_use']['input']['query'];
                            }
                            break;
                            
                        case 'tool_results':
                            // Results from Cortex Analyst
                            if (isset($content_item['tool_results']['content'])) {
                                foreach ($content_item['tool_results']['content'] as $result) {
                                    if ($result['type'] === 'json' && isset($result['json'])) {
                                        if (isset($result['json']['sql'])) {
                                            $sql_query = $result['json']['sql'];
                                        }
                                        if (isset($result['json']['text'])) {
                                            $interpretation = $result['json']['text'];
                                        }
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }
    }
    
    // Check what type of response we have
    if (!empty($interpretation) && !empty($sql_query)) {
        // This is an analytical response with SQL
        return [
            'success' => true,
            'message_id' => $message_id,
            'response_type' => 'analysis',
            'interpretation' => $interpretation,
            'sql_query' => $sql_query,
            'original_query' => $analysis_results['original_query'] ?? '',
            'debug_info' => $debug_info
        ];
    } elseif (!empty($content_pieces)) {
        // Regular text response
        $full_content = implode('', $content_pieces);
        return [
            'success' => true,
            'message_id' => $message_id,
            'content' => $full_content,
            'response_type' => 'text',
            'debug_info' => $debug_info
        ];
    } else {
        // Couldn't extract content
        return [
            'error' => 'Failed to extract content from SSE response',
            'raw_response' => $sseData,
            'debug_info' => $debug_info
        ];
    }
}

// Handle form submission  
$user_question = optional_param('question', '', PARAM_TEXT);
$result = null;

// CSRF protection for POST requests only
if (!empty($user_question)) {
    require_sesskey();
}

// If user submitted a question, call the API
if (!empty($user_question)) {
    $result = callCortexAnalystAPI($user_question);
}

// Moodle page output
echo $OUTPUT->header();

// Display configuration notices
$snowflake_host = get_config('local_cortexanalyst', 'snowflake_host');
$model_path = get_config('local_cortexanalyst', 'model_path');

if (empty($snowflake_host)) {
    echo $OUTPUT->notification(
        'Please configure your Snowflake host in the plugin settings: ' .
        html_writer::link(new moodle_url('/admin/settings.php?section=local_cortexanalyst_settings'), 'Plugin Settings'),
        'warning'
    );
}

if (empty($model_path) || !preg_match('/^@[^.]+\.[^.]+\.[^\/]+\//', $model_path)) {
    echo $OUTPUT->notification(
        'Please configure your semantic model stage path correctly. Format: @database.schema.stage/path/to/file.yaml - ' .
        html_writer::link(new moodle_url('/admin/settings.php?section=local_cortexanalyst_settings'), 'Plugin Settings'),
        'warning'
    );
}

// Display the question form
echo $OUTPUT->box_start('box generalbox');
echo '<h3>🤖 Ask Cortex Analyst</h3>';

echo '<form method="post" action="' . $PAGE->url . '" style="margin: 20px 0;" id="questionForm">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">'; // CSRF protection
echo '<div style="margin-bottom: 15px;">';
echo '<label for="question" style="display: block; font-weight: bold; margin-bottom: 5px;">Your Question:</label>';
echo '<textarea name="question" id="question" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit;" placeholder="Ask me anything about your data, analytics, or general questions..." required>' . htmlspecialchars($user_question) . '</textarea>';
echo '</div>';

echo '<div style="margin-bottom: 15px;">';
echo '<button type="submit" id="submitBtn" style="background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">Ask Cortex Analyst</button>';
echo ' <button type="button" onclick="clearForm()" style="background: #666; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 10px;">Clear</button>';
echo ' <button type="button" onclick="askNewQuestion()" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 10px;" id="newQuestionBtn"' . (empty($user_question) ? ' style="display:none;"' : '') . '>Ask Another Question</button>';
echo '</div>';

echo '</form>';

// Add JavaScript for enhanced interaction
echo '<script>
function clearForm() {
    document.getElementById("question").value = "";
    document.getElementById("question").focus();
}

function askNewQuestion() {
    clearForm();
    // Hide results and scroll to form
    const form = document.getElementById("questionForm");
    form.scrollIntoView({behavior: "smooth"});
}

function fillQuestion(text) {
    document.getElementById("question").value = text;
    document.getElementById("question").focus();
}

// Auto-focus on the textarea when page loads
document.addEventListener("DOMContentLoaded", function() {
    const questionField = document.getElementById("question");
    if (questionField && questionField.value === "") {
        questionField.focus();
    }
});

// Add loading state to submit button
document.getElementById("questionForm").addEventListener("submit", function() {
    const submitBtn = document.getElementById("submitBtn");
    submitBtn.innerHTML = "🤔 Thinking...";
    submitBtn.disabled = true;
});
</script>';

echo $OUTPUT->box_end();

// Display results if a question was asked
if (!empty($user_question)) {
    echo $OUTPUT->box_start('box generalbox');
    echo '<h3>📝 Your Question</h3>';
    echo '<div style="background: #f0f8ff; padding: 15px; border-radius: 5px; border-left: 4px solid #0073aa;">';
    echo '<strong>Q:</strong> ' . htmlspecialchars($user_question);
    echo '</div>';
    echo $OUTPUT->box_end();
}

// Check for errors and display the result (only if a question was asked)
if (!empty($user_question) && isset($result['error'])) {
    echo $OUTPUT->notification($result['error'], 'error');
    
    // Display debug information if available
    echo $OUTPUT->box_start('box generalbox');
    echo '<h3>Complete Debug Information:</h3>';
    
    // Show the complete result array for debugging
    echo '<h4>Full Result Array:</h4>';
    echo '<pre>' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . '</pre>';
    
    if (isset($result['details'])) {
        echo '<h4>Error Details:</h4>';
        echo '<pre>' . htmlspecialchars(json_encode($result['details'], JSON_PRETTY_PRINT)) . '</pre>';
    }
    
    if (isset($result['debug_info'])) {
        echo '<h4>Connection Info:</h4>';
        echo '<pre>' . htmlspecialchars(json_encode($result['debug_info'], JSON_PRETTY_PRINT)) . '</pre>';
    }
    
    if (isset($result['raw_response'])) {
        echo '<h4>Raw API Response:</h4>';
        echo '<pre>' . htmlspecialchars($result['raw_response']) . '</pre>';
    }
        
        echo '<h4>Common Solutions:</h4>';
        echo '<ul>';
        
        // Check if it's a model path error (HTTP 400 with stage file URL message)
        if (isset($result['details']['http_code']) && $result['details']['http_code'] == 400 && 
            isset($result['details']['response']) && strpos($result['details']['response'], 'stage file URL') !== false) {
            echo '<li><strong>Model Path Format Issue:</strong> The semantic model path must be in Snowflake stage format</li>';
            echo '<li><strong>Correct Format:</strong> @database.schema.stage/path/to/file.yaml</li>';
            echo '<li><strong>Example:</strong> @MOODLE_APP.PUBLIC.MOUNTED/moodledata/revenue_timeseries.yaml</li>';
            echo '<li>Update your model path in the ' . html_writer::link(new moodle_url('/admin/settings.php?section=local_cortexanalyst_settings'), 'Plugin Settings') . '</li>';
        } else {
            echo '<li>Check if your Snowflake domain is in the <strong>curlsecurityblockedhosts</strong> setting</li>';
            echo '<li>Verify <strong>curlsecurityallowedport</strong> allows port 443 (HTTPS)</li>';
            echo '<li>Ensure <strong>curl_allowed_ports</strong> includes 443</li>';
            echo '<li>Check firewall settings on your Moodle server</li>';
            echo '<li>Verify the SNOWFLAKE_HOST environment variable is correct</li>';
        }
        
        echo '</ul>';
        
    echo $OUTPUT->box_end();
} elseif (!empty($user_question)) {
    // Display the successful result 
    echo $OUTPUT->box_start('box generalbox');
    echo '<h3>✅ Cortex Analyst API Response:</h3>';
    
    // Check if this is our parsed SSE response
    if (isset($result['success'])) {
        if ($result['response_type'] === 'analysis') {
            // Display analytical response with SQL
            echo '<div style="background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #0073aa;">';
            echo '<h4>🔍 Analysis Interpretation:</h4>';
            echo '<p>' . nl2br(htmlspecialchars($result['interpretation'])) . '</p>';
            echo '</div>';
            
            echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745;">';
            echo '<h4>📊 Generated SQL Query:</h4>';
            echo '<pre style="background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; font-family: monospace; font-size: 12px; border: 1px solid #e9ecef;">' . htmlspecialchars($result['sql_query']) . '</pre>';
            echo '</div>';
            
            echo '<div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107;">';
            echo '<p><strong>💡 Note:</strong> This SQL query was automatically generated by Cortex Analyst to answer your question. You can run this query in Snowflake to see the actual data results.</p>';
            echo '</div>';
            
        } elseif (isset($result['content'])) {
            // Display regular text response
            echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;">';
            echo '<h4>Response Content:</h4>';
            echo '<div>' . nl2br(htmlspecialchars($result['content'])) . '</div>';
            echo '</div>';
        }
        
        if (isset($result['message_id'])) {
            echo '<p style="font-size: 12px; color: #666;"><strong>Message ID:</strong> ' . htmlspecialchars($result['message_id']) . '</p>';
        }
    } else {
        // Fallback to JSON display
        echo '<pre>' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . '</pre>';
    }
    
    echo $OUTPUT->box_end();
}

// Show current configuration for debugging (only if no question asked or if there are configuration issues)
if (empty($user_question) || empty($snowflake_host) || empty($model_path)) {
    echo $OUTPUT->box_start('box generalbox');
    echo '<h3>🔧 Current Configuration:</h3>';
    echo '<ul>';
    echo '<li><strong>Snowflake Host:</strong> ' . htmlspecialchars($snowflake_host ?: 'Not configured') . '</li>';
    echo '<li><strong>Model Path:</strong> ' . htmlspecialchars($model_path ?: 'Not configured') . '</li>';
    echo '<li><strong>Token File:</strong> ' . htmlspecialchars(get_config('local_cortexanalyst', 'token_file') ?: '/snowflake/session/token') . '</li>';
    $token_file_path = get_config('local_cortexanalyst', 'token_file') ?: '/snowflake/session/token';
    echo '<li><strong>Token File Exists:</strong> ' . (file_exists($token_file_path) ? '✅ Yes' : '❌ No') . '</li>';
    if (file_exists($token_file_path)) {
        echo '<li><strong>Token File Size:</strong> ' . filesize($token_file_path) . ' bytes</li>';
    }
    echo '</ul>';
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();
?>
