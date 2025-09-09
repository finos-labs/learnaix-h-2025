# Moodle "Hello World" Local Plugin

A fundamental example of a Moodle local plugin, created for the Moodle Plugin Development Hackathon. This guide provides all the necessary information to get this basic plugin up and running.

---

##### 🎯 Objective

This "Hello World" plugin serves as a foundational starting point for hackathon participants. Its primary goal is to demonstrate the essential file structure and basic components required to create an installable Moodle local plugin. By examining and installing this plugin, you'll understand how to:

* **Structure** a plugin's files and folders correctly.
* **Define** plugin metadata like version and Moodle dependencies.
* **Add a new page** to the Moodle Site Administration area.
* **Use** the Moodle Page API (`$OUTPUT`) and language strings (`get_string`).

---

##### 📂 File Structure Explained

The plugin is contained within the `helloworld` directory. Here’s a breakdown of the essential files and their purpose:

* **`version.php`**: **Plugin Metadata.** This is a mandatory file for any Moodle plugin. It tells Moodle the plugin's unique component name (`local_helloworld`), its version, and the minimum Moodle version it requires to run.
* **`settings.php`**: **Admin Navigation.** This file adds a link to our plugin's main page in the Site Administration menu. Specifically, it places a link under *Site administration > Plugins > Local plugins* which points to `/local/helloworld/index.php`.
* **`index.php`**: **The Main Page.** This is the core page of our plugin that gets displayed to the user. It handles user authentication (`require_login()`) and uses the Moodle Page API to render the standard Moodle header, footer, and a heading. The heading uses the 'helloworld' string.
* **`db/access.php`**: **Permissions & Capabilities.** This file defines the capabilities (i.e., permissions) for the plugin. In this simple example, it is empty as we aren't creating any new, specific permissions.
* **`lang/en/local_helloworld.php`**: **Language File.** This file contains the English language strings for the plugin, such as its name ('Hello World') and the content for the heading ('Hello, World').

---

##### ⚙️ Installation and Deployment

Follow these steps to install the plugin in your Moodle environment.

#### **Step 1: Place the Plugin Folder**

You need to copy the entire `helloworld` folder into the `/local/` directory of your Moodle installation. The exact path will vary depending on your operating system and server setup.

* **Windows (using a server stack like XAMPP):**
    ```
    C:\xampp\htdocs\<your_moodle_folder>\local\
    ```
* **macOS (using a server stack like MAMP):**
    ```
    /Applications/MAMP/htdocs/<your_moodle_folder>/local/
    ```
* **Linux (typical Apache setup):**
    ```
    /var/www/html/<your_moodle_folder>/local/
    ```

After copying, your directory structure should look like this:

<moodle_root>/
└── local/
└── helloworld/
├── db/
│   └── access.php
├── lang/
│   └── en/
│       └── local_helloworld.php
├── index.php
├── settings.php
└── version.php


#### **Step 2: Trigger the Moodle Installation**

Once the files are in the correct location, Moodle needs to recognize and install the new plugin.

1.  Log in to your Moodle site as an **administrator**.
2.  Navigate to **Site administration** > **Notifications**.
3.  Moodle will automatically detect the new plugin and show an installation screen. Click the **"Upgrade Moodle database now"** button to proceed.
4.  You should see a success message indicating that the `local_helloworld` plugin has been installed correctly.

---

##### ✅ Testing the Plugin

After a successful installation, you can access the plugin's page to verify it's working.

1.  As an administrator, navigate to **Site administration**.
2.  Click on the **Plugins** tab.
3.  Scroll down to the **Local plugins** section.
4.  You will find a link named **"Hello World"**. Click on it.

You should be redirected to a new page that displays the heading **"Hello, World"** within the standard Moodle theme. This confirms that your plugin is working correctly! 🎉

Good luck with the hackathon!
```
