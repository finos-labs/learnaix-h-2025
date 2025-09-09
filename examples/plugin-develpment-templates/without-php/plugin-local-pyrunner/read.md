# Moodle Python Runner (HTTP Microservice Example)

This plugin is an advanced example for the Moodle Plugin Development Hackathon. It demonstrates how a standard Moodle plugin, written in PHP, can communicate with a separate, standalone Python microservice over HTTP to perform a task.

---

##### 🎯 Objective

The goal of this plugin is to showcase a modern development pattern where Moodle offloads specific tasks to external services. This is useful for computationally intensive jobs, running machine learning models, or integrating with tools written in other programming languages.

By setting up and using this plugin, you will learn how to:
* Create a Moodle interface (a page with a form) that acts as a front-end.
* Use PHP to make an HTTP `POST` request to an external API from within Moodle.
* Build a simple Python web service using the **FastAPI** framework to act as a backend.
* Define specific Moodle **capabilities** to restrict access to the tool.

---

##### 🏗️ Architecture Overview

This plugin consists of two distinct parts that communicate over the network:

1.  **🐘 The Moodle Plugin (PHP):** A local plugin that provides a user interface. When a user submits a form, the PHP code sends the data to the Python service.
2.  **🐍 The Microservice (Python):** A small web server running locally on port `5050`. It waits for requests, performs a simple analysis on the received text, and returns a JSON response.

The flow is: **Moodle UI -> PHP `curl` Request -> Python FastAPI Service -> JSON Response -> Moodle UI**

---

##### 📂 File Structure Explained

The project is split into two main folders:

#### **`pyrunner/` (The Moodle PHP Plugin)**
* **`index.php`**: The main user-facing page with the input form. It checks for the `local/pyrunner:run` permission before allowing access and displays the output from the Python service.
* **`lib.php`**: Contains the core communication logic. The `local_pyrunner_analyze()` function uses Moodle's `curl` helper to send a `POST` request to `http://localhost:5050/api/analyze`.
* **`db/access.php`**: Defines the `local/pyrunner:run` capability, granting permission only to users with the **Manager** role by default.
* **`version.php`**: Standard Moodle plugin metadata file.
* **`lang/en/local_pyrunner.php`**: Contains all English language strings for the user interface, like "Run Python Service" and "Argument to send:".

#### **`python/` (The Python Microservice)**
* **`app.py`**: A complete web service built with the FastAPI framework. It defines an endpoint at `/api/analyze` that accepts `POST` requests with a JSON body. It processes the input and returns a JSON response.

---

##### 🚀 Setup and Deployment

This is a two-step process. You **must** start the Python service first before the Moodle plugin will work.

### **Step 1: Run the Python Microservice**

This service runs in its own terminal window, completely separate from your Moodle web server (Apache/Nginx).

**Prerequisites:** You need Python 3 and `pip` installed on your machine.

1.  **Open a Terminal** (Command Prompt or PowerShell on Windows, Terminal on macOS/Linux).

2.  **Navigate to the `python` directory** inside the plugin folder.

3.  **Create and activate a virtual environment.** This isolates the Python dependencies.
    * **Windows:**
        ```shell
        python -m venv venv
        .\venv\Scripts\activate
        ```
    * **macOS / Linux:**
        ```shell
        python3 -m venv venv
        source venv/bin/activate
        ```

4.  **Install dependencies.** You will need `fastapi` and an ASGI server like `uvicorn`.
    ```shell
    pip install fastapi "uvicorn[standard]"
    ```

5.  **Start the service.**
    ```shell
    uvicorn app:app --host 127.0.0.1 --port 5050
    ```
    You should see output indicating the server is running. **Keep this terminal window open!** If you close it, the service will stop.

### **Step 2: Install the Moodle Plugin**

1.  Copy the **`pyrunner`** folder (NOT the entire project folder) into your Moodle's `/local/` directory.
    * **Windows (XAMPP):** `C:\xampp\htdocs\moodle\local\`
    * **macOS (MAMP):** `/Applications/MAMP/htdocs/moodle/local/`

2.  Log in to Moodle as an **administrator**.

3.  Navigate to **Site administration > Notifications** to start the plugin installation process. Click "Upgrade Moodle database now" to complete it.

---

##### ✅ Testing the Plugin

1.  Ensure you are logged in as a user with the **Manager** role (or any role that has the `local/pyrunner:run` capability).
2.  Make sure your Python service is still running in its terminal window.
3.  Navigate directly to the plugin's page in your browser. The URL will be:
    `http://<your-moodle-site>/local/pyrunner/index.php`
4.  You will see a heading "Run Python Service" and a text input box.
5.  Enter any text (e.g., "Moodle is great") into the box and click the **"Run"** button.
6.  The page will reload, and you should see a formatted JSON response below the form, which comes directly from your Python service.

```json
{
    "sentiment": "positive",
    "score": 0.95,
    "received": "Moodle is great"
}
````

This confirms that your Moodle plugin is successfully communicating with your Python microservice\!

```
```
