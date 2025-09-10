from flask import Flask, request, jsonify
import subprocess
import os
import sys
import json

app = Flask(__name__)

# Mounted storage configuration
PYTHON_FILES_PATH = os.getenv('PYTHON_FILES_PATH', '/app/python')

def get_file_from_storage(filename):
    """Get a file from mounted storage"""
    try:
        # Construct the full path to the file
        file_path = os.path.join(PYTHON_FILES_PATH, filename)
        
        # Check if file exists
        if os.path.exists(file_path):
            return file_path
        else:
            # Check if the file exists with different casing
            if os.path.exists(PYTHON_FILES_PATH):
                files = os.listdir(PYTHON_FILES_PATH)
                for file in files:
                    if file.lower() == filename.lower():
                        return os.path.join(PYTHON_FILES_PATH, file)
            
            raise FileNotFoundError(f"File '{filename}' not found in mounted storage")
            
    except Exception as e:
        raise Exception(f"Error accessing file from storage: {str(e)}")

@app.route("/")
def hello_world():
    return "Hello, World!"

@app.route("/api/execute", methods=["POST"])
def execute_python():
    try:
        # Get JSON data from request
        data = request.get_json()
        
        # Check if filename is provided
        if not data or 'filename' not in data:
            return jsonify({
                "error": "Missing 'filename' in request body"
            }), 400
        
        filename = data['filename']
        
        # Get optional arguments - can be object, list, or string
        arguments = data.get('arguments', {})
        
        # Convert arguments to JSON string for passing to Python script
        if arguments:
            if isinstance(arguments, (dict, list)):
                # If it's already a dict or list, convert to JSON string
                json_arguments = json.dumps(arguments)
            elif isinstance(arguments, str):
                # If it's a string, try to parse as JSON, otherwise treat as plain string
                try:
                    # Test if it's already valid JSON
                    json.loads(arguments)
                    json_arguments = arguments
                except json.JSONDecodeError:
                    # If not valid JSON, wrap it in a simple object
                    json_arguments = json.dumps({"query": arguments})
            else:
                # For other types, convert to string and wrap
                json_arguments = json.dumps({"value": str(arguments)})
        else:
            json_arguments = None
        
        # Clean up filename - remove any path prefixes
        if '/' in filename:
            filename = filename.split('/')[-1]  # Get just the filename
        
        # Basic validation - ensure it's a .py file
        if not filename.endswith('.py'):
            return jsonify({
                "error": "File must have .py extension"
            }), 400
        
        # Get file from mounted storage
        try:
            file_path = get_file_from_storage(filename)
            
        except FileNotFoundError as e:
            return jsonify({
                "error": str(e),
                "filename": filename,
                "status": "file_not_found",
                "suggestion": f"Make sure '{filename}' exists in mounted storage at {PYTHON_FILES_PATH}"
            }), 404
            
        except Exception as e:
            return jsonify({
                "error": f"Failed to access file from mounted storage: {str(e)}",
                "filename": filename,
                "status": "access_error"
            }), 500
        
        # Execute the Python file with JSON arguments and capture output
        try:
            # Build command: python script.py '{"key": "value", ...}'
            if json_arguments:
                command = [sys.executable, file_path, json_arguments]
            else:
                command = [sys.executable, file_path]
            
            # Use subprocess to run the Python file safely
            result = subprocess.run(command, 
                                  capture_output=True, 
                                  text=True, 
                                  timeout=60,  # Increased timeout for API calls
                                  cwd=PYTHON_FILES_PATH)  # Run in mounted storage directory
            
            # Get the output
            stdout = result.stdout
            stderr = result.stderr
            return_code = result.returncode
            
            # Try to parse stdout as JSON if possible (for structured responses)
            parsed_output = None
            try:
                if stdout.strip():
                    parsed_output = json.loads(stdout.strip())
            except (json.JSONDecodeError, ValueError):
                parsed_output = None  # If parsing fails, keep as string
            
            # Prepare response
            response = {
                "filename": filename,
                "arguments": arguments,
                "json_arguments": json_arguments,
                "status": "executed",
                "return_code": return_code,
                "stdout": stdout,
                "stderr": stderr,
                "success": return_code == 0,
                "storage_path": PYTHON_FILES_PATH,
                "file_path": file_path,
                "command": command
            }
            
            # Add parsed output if available
            if parsed_output is not None:
                response["parsed_output"] = parsed_output
            
            if return_code == 0:
                response["message"] = f"Python file '{filename}' executed successfully"
            else:
                response["message"] = f"Python file '{filename}' executed with errors"
            
            return jsonify(response), 200
            
        except subprocess.TimeoutExpired:
            return jsonify({
                "error": "Python file execution timed out (60 seconds limit)",
                "filename": filename,
                "arguments": arguments,
                "json_arguments": json_arguments,
                "status": "timeout"
            }), 408
            
        except Exception as exec_error:
            return jsonify({
                "error": f"Error executing Python file: {str(exec_error)}",
                "filename": filename,
                "arguments": arguments,
                "json_arguments": json_arguments,
                "status": "execution_error"
            }), 500
        
    except Exception as e:
        return jsonify({
            "error": f"Error processing request: {str(e)}"
        }), 500

@app.route("/api/execute", methods=["GET"])
def get_execute_info():
    return jsonify({
        "endpoint": "/api/execute",
        "method": "POST",
        "description": "Execute a Python file from mounted storage with optional arguments",
        "storage_path": PYTHON_FILES_PATH,
        "request_format": {
            "filename": "string (required) - Python file to execute",
            "arguments": "array or string (optional) - Arguments to pass to the script"
        },
        "example_requests": [
            {
                "filename": "cortex.py",
                "arguments": ["What is the total sales for 2024?"]
            },
            {
                "filename": "analysis.py", 
                "arguments": ["dataset.csv", "--mode", "summary"]
            },
            {
                "filename": "simple_script.py"
            }
        ],
        "response_format": {
            "success": "boolean - True if execution successful",
            "stdout": "string - Raw output from Python script",
            "stderr": "string - Error output if any",
            "parsed_output": "object - Parsed JSON if stdout contains valid JSON",
            "return_code": "number - Script exit code",
            "arguments": "array - Arguments that were passed"
        }
    })

if __name__ == "__main__":
    app.run(host='0.0.0.0', debug=True)