# Moodle "My First Plugin" Block

This is a simple Moodle block plugin developed as a learning example for the Moodle Plugin Development Hackathon. This guide provides all the necessary information to install and test this block.

---

##### 🎯 Objective

This "My First Plugin" block serves as a hands-on example for hackathon participants. Its goal is to demonstrate the fundamental structure and logic required to create a Moodle block, which is a content item that can be added to the side of Moodle pages.

By examining this plugin, you will learn how to:
* Structure the files for a **block** plugin.
* Implement the main block class to control its title and content.
* Define where the block can be placed (e.g., course pages, Dashboard).
* Use language files to manage text strings.
* Set up permissions (capabilities) to control who can add the block.

---

##### 📂 File Structure Explained

The plugin is contained within the `myplugin` directory. Here is a breakdown of the key files and their roles:

* **`block_myplugin.php`**: **Main Block Logic.** This file contains the core `block_myplugin` class which extends Moodle's `block_base`. It defines the block's title (`init()`), what content it displays (`get_content()`), and on which types of pages it can be added (`applicable_formats()`). The content includes a "Hello" message and a link to the Dashboard in the footer.
* **`version.php`**: **Plugin Metadata.** A mandatory file that defines the plugin's component name (`block_myplugin`), version, and Moodle dependency. This plugin requires Moodle 4.1 or newer.
* **`db/access.php`**: **Permissions & Capabilities.** This file defines who can use the block. It creates two capabilities: `block/myplugin:addinstance` for adding the block to pages like courses (allowed for roles like Manager and Editing Teacher) and `block/myplugin:myaddinstance` for adding it to a user's personal Dashboard page.
* **`lang/en/block_myplugin.php`**: **Language File.** This contains the English language strings used by the plugin, such as the plugin's name ('My First Plugin'), the content ('Hello from My First Plugin!'), and the capability descriptions.

---

##### ⚙️ Installation and Deployment

Follow these steps to install the plugin in your Moodle environment. Note that the folder for **block** plugins is different from local plugins.

#### **Step 1: Place the Plugin Folder**

You need to copy the entire `myplugin` folder into the `/blocks/` directory of your Moodle installation.

* **Windows (using a server stack like XAMPP):**
    ```
    C:\xampp\htdocs\<your_moodle_folder>\blocks\
    ```
* **macOS (using a server stack like MAMP):**
    ```
    /Applications/MAMP/htdocs/<your_moodle_folder>\blocks\
    ```
* **Linux (typical Apache setup):**
    ```
    /var/www/html/<your_moodle_folder>/blocks/
    ```

After copying, your directory structure should look like this:

<moodle_root>/
└── blocks/
└── myplugin/
├── db/
│   └── access.php
├── lang/
│   └── en/
│       └── block_myplugin.php
├── block_myplugin.php
└── version.php


#### **Step 2: Trigger the Moodle Installation**

1.  Log in to your Moodle site as an **administrator**.
2.  Navigate to **Site administration** > **Notifications**.
3.  Moodle will detect the new plugin and prompt you to install it. Click the **"Upgrade Moodle database now"** button to proceed.
4.  Review the installation results and click "Continue".

---

##### ✅ Testing the Plugin

After successful installation, you can add the block to a course page or your Dashboard.

#### **Adding to a Course Page**
1.  Navigate to any course where you have editing rights (e.g., as a teacher or manager).
2.  Click the **"Turn edit mode on"** button in the top-right corner.
3.  In the right-hand navigation drawer, find and click the **"Add a block"** link.
4.  Select **"My First Plugin"** from the pop-up list.

#### **Adding to your Dashboard**
1.  Navigate to your **Dashboard** (usually the first page after logging in).
2.  Click the **"Customise this page"** button.
3.  Find and click the **"Add a block"** link.
4.  Select **"My First Plugin"** from the pop-up list.

Once added, a new block will appear with the title "My First Plugin". The body of the block will display the text "Hello from My First Plugin!", and the footer will contain a link that says "Go to Dashboard".
```
