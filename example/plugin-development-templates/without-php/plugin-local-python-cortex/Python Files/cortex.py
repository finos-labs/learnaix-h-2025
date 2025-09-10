import os
import json
import requests
import sys
from datetime import datetime

MODEL_PATH = "@MOODLE_APP.PUBLIC.MOUNTED/moodledata/revenue_timeseries.yaml"  # FIXME: EDIT HERE
SNOWFLAKE_HOST = os.getenv("SNOWFLAKE_HOST")
SNOWFLAKE_ACCOUNT = os.getenv("SNOWFLAKE_ACCOUNT")
ANALYST_ENDPOINT = "/api/v2/cortex/agent:run"

# Construct account hostname to route through internal network
URL = "https://" + SNOWFLAKE_HOST + ANALYST_ENDPOINT
print(URL)

def get_login_token():
    """Fetches the SPCS OAuth token"""
    with open("/snowflake/session/token", "r") as f:
        return f.read()

def send_request(semantic_model_file, prompt):
    """Sends the prompt using the semantic model file """
    headers = {
        "Content-Type": "application/json",
        "accept": "application/json",
        "Authorization": f"Bearer {get_login_token()}",
        "X-Snowflake-Authorization-Token-Type": "OAUTH"
    }
    # Can be whatever; but it must conform with
    # https://docs.snowflake.com/en/user-guide/snowflake-cortex/cortex-agents-rest-api#sample-request
    request_body = {
        "model": "llama3.1-8b",
        "messages": [
            {
                "role": "user",
                "content": [{"type": "text", "text": prompt}],
            }
        ],
        "tools": [
            {
                "tool_spec": {
                    "type": "cortex_analyst_text_to_sql",
                    "name": "Analyst1",
                },
            }
        ],
        "tool_resources": {
            "Analyst1": { "semantic_model_file": semantic_model_file, },
        },
    }
    return requests.post(URL, headers=headers, data=json.dumps(request_body))

def main():
    """Main function to process JSON arguments and send request to Cortex Analyst"""
    # Check if JSON arguments were provided
    if len(sys.argv) < 2:
        error_response = {
            "error": "No arguments provided",
            "usage": "This script expects JSON arguments with a 'question' field",
            "timestamp": datetime.now().isoformat()
        }
        print(json.dumps(error_response, indent=2))
        return 1
    
    try:
        # Parse JSON arguments from Flask
        json_args = sys.argv[1]
        args = json.loads(json_args)
        
        # Extract the question from arguments
        question = args.get('question', '').strip()
        user_id = args.get('user_id', 'Unknown')
        timestamp = args.get('timestamp', datetime.now().isoformat())
        session_id = args.get('session_id', 'N/A')
        
        # Validate that a question was provided
        if not question:
            error_response = {
                "error": "No question provided in arguments",
                "received_args": args,
                "usage": "Please provide a 'question' field in the JSON arguments",
                "timestamp": datetime.now().isoformat()
            }
            print(json.dumps(error_response, indent=2))
            return 1
        
        # Send request to Cortex Analyst with the dynamic question
        try:
            response = send_request(MODEL_PATH, question)
            
            # Check if request was successful
            if response.status_code == 200:
                # Print the response text (this will be captured by Flask)
                print(response.text)
                return 0
            else:
                error_response = {
                    "error": f"Cortex API returned status {response.status_code}",
                    "response_text": response.text,
                    "question": question,
                    "user_id": user_id,
                    "timestamp": datetime.now().isoformat()
                }
                print(json.dumps(error_response, indent=2))
                return 1
                
        except requests.exceptions.RequestException as e:
            error_response = {
                "error": f"Failed to connect to Cortex API: {str(e)}",
                "question": question,
                "user_id": user_id,
                "endpoint": URL,
                "timestamp": datetime.now().isoformat()
            }
            print(json.dumps(error_response, indent=2))
            return 1
            
    except json.JSONDecodeError as e:
        error_response = {
            "error": "Invalid JSON arguments",
            "details": str(e),
            "received": sys.argv[1] if len(sys.argv) > 1 else "No arguments",
            "timestamp": datetime.now().isoformat()
        }
        print(json.dumps(error_response, indent=2))
        return 1
        
    except Exception as e:
        error_response = {
            "error": f"Unexpected error: {str(e)}",
            "timestamp": datetime.now().isoformat()
        }
        print(json.dumps(error_response, indent=2))
        return 1

if __name__ == "__main__":
    sys.exit(main())
