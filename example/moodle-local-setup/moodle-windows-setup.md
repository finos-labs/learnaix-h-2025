# Moodle Local Setup — Windows (XAMPP + PostgreSQL)

This guide helps you set up Moodle locally on **Windows 10/11** using **XAMPP** (for Apache & PHP) and **PostgreSQL** (via the EDB installer). PostgreSQL is chosen over MariaDB since it supports **vector embeddings**, useful for AI plugin development.

---

## 1. Local Development Architecture

Your local setup simulates a live web server on your computer. It consists of:

- **Apache** → Web server  
- **PHP** → Programming language Moodle is built on  
- **PostgreSQL** → Database (instead of MariaDB, since PostgreSQL supports AI/Vector embeddings)  

---

## 2. Install XAMPP (Apache + PHP)

1. Download **XAMPP for Windows** from [Apache Friends](https://www.apachefriends.org/).  
2. Run the installer and choose default options (usually installs in `C:\xampp`).  
3. Open the **XAMPP Control Panel** → Start **Apache**. (MySQL is included but not needed if using PostgreSQL).  
4. Copy your Moodle codebase into:  
   ```
   C:\xampp\htdocs\moodle
   ```

### Enable PHP Extensions (if needed)

1. Open `C:\xampp\php\php.ini`.  
2. Ensure the following extensions are enabled (remove `;` if present):  
   ```ini
   extension=intl
   extension=pgsql
   extension=pdo_pgsql
   ```

---

## 3. Install PostgreSQL (with pgAdmin)

💻 The easiest way to install PostgreSQL on Windows is via the **EnterpriseDB (EDB) installer**, which bundles PostgreSQL, pgAdmin, and CLI tools.  

### Step 1: Download the Installer
1. Go to the [PostgreSQL Downloads page for Windows](https://www.postgresql.org/download/windows/).  
2. Choose the latest stable version supported by Moodle (13–16 is fine).  
3. Select **Windows x86-64** and click **Download**.  

### Step 2: Run the Installation Wizard
1. Open the downloaded `.exe` file.  
2. **Installation Directory** → Default is fine (`C:\Program Files\PostgreSQL\<version>`).  
3. **Select Components** → Check:
   - ✅ PostgreSQL Server  
   - ✅ pgAdmin 4  
   - ✅ Command Line Tools  
   - (❌ Stack Builder can be unchecked — not needed)  
4. **Data Directory** → Leave default.  
5. **Password** → Set a strong password for the `postgres` superuser. Save it securely. 🔑  
6. **Port** → Default is `5432`. Keep it.  
7. **Locale** → Default is fine.  
8. Review summary → Install → Finish.  

### Step 3: Create Moodle Database & User in pgAdmin
1. Open **pgAdmin 4** (from Start Menu).  
2. Connect to local server with the **postgres** password.  
7. **Using Query Tool in PgAdmin**
   ```sql
   CREATE USER moodleuser WITH PASSWORD 'your_strong_password';
   CREATE DATABASE moodle WITH OWNER moodleuser ENCODING 'UTF8';
   ```

✅ Now you have a database `moodle` owned by `moodleuser`.  

---

## 4. Configure Moodle to Connect to PostgreSQL

When running the Moodle web installer in your browser:

- **Database type** → PostgreSQL  
- **Host** → `localhost` or `127.0.0.1`  
- **Database name** → `moodle`  
- **Database user** → `moodleuser`  
- **Database password** → password you set above  
- **Tables prefix** → `mdl_` (default)  
- **Port** → `5432`  

Click **Next** → Moodle will connect and continue installation. 🎉  

---

## 5. Troubleshooting (Windows)

- ⏱ **“Maximum execution time exceeded”** → Increase `max_execution_time` in `php.ini`.  
- 🐘 **PostgreSQL not detected** → Uncomment (remove `;`) for:  
  ```ini
  extension=pgsql
  extension=pdo_pgsql
  ```  
- 🌐 **“PHP extension ... must be installed/enabled”** → Uncomment e.g. `extension=intl`.  
- 🔗 **Database connection failed** → Ensure PostgreSQL service is running and credentials are correct.  
- 📂 **“Cannot write to the data directory”** → Give write permission to the `moodledata` folder (Users group).  

---

## 6. Start Moodle Locally

1. Open **XAMPP Control Panel**.  
2. Start **Apache** (PostgreSQL runs separately as a Windows service).  
3. Visit: **[http://localhost/moodle](http://localhost/moodle)**  
4. Install: Follow the moodle instructions, add your database credentials when asked

---

## 7. Moodle User Roles Explained

- **Admin** → Full site control  
- **Manager** → Manage courses/users  
- **Course Creator** → Create courses  
- **Teacher** → Manage activities  
- **Student** → Participate in courses  

---

## 8. Types of Moodle Plugins
All below sub folder resides in C:\xampp\htdocs\moodle

| Plugin Type       | Path Example  | Description |
|-------------------|--------------|-------------|
| **Activity modules** | `/mod/`    | Add new activities like forums, assignments |
| **Blocks**          | `/blocks/` | Sidebar widgets for navigation, info, tools |
| **Themes**          | `/theme/`  | Customize site appearance |
| **Local plugins**   | `/local/`  | Custom functionality not tied to activities |

[more on moodle plugins](https://moodledev.io/docs/4.1/apis/plugintypes)
---

## 9. Important Links
- **[Moodle Plugins](https://moodle.org/plugins/)**
- **[Moodle Developer Docs](https://moodle.org/dev/)**
- **[Moodle Dev Environment](https://moodledev.io/)**
- **[Moodle Developer Forum](https://moodle.org/mod/forum/view.php?id=50)**

✨ You now have a complete Windows development setup with Moodle + PostgreSQL.  
