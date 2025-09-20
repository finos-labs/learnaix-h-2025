# Moodle Local Setup — macOS (MAMP + PostgreSQL)

This guide helps you set up Moodle locally on **macOS** using **MAMP** (for Apache & PHP) and **PostgreSQL**.  
PostgreSQL is chosen over MariaDB since it supports **vector embeddings**, useful for AI plugin development.

---

## 1. Local Development Architecture

Your local setup simulates a live web server on your computer. It consists of:

- **Apache** → Web server  
- **PHP** → Programming language Moodle is built on  
- **PostgreSQL** → Database (instead of MariaDB, since PostgreSQL supports AI/Vector embeddings)  

---

## 2. Install MAMP (Apache + PHP)

1. Download **MAMP for macOS** from [MAMP official site](https://www.mamp.info/en/). (Or use [direct link](https://downloads.mamp.info/MAMP-PRO/macOS/MAMP-PRO/MAMP-MAMP-PRO-7.2-Intel-x86.pkg)) 
2. Install and launch **MAMP**.
3. Click on Preferences button on the top left of **MAMP** and choose 'Ports' tab. Here you need to click the '80 & 3306' button. This will set your Apache and Nginx to use port 80 (for which you don't need to enter port number everytime in the url.) 
4. Start **Apache** and **Nginx** from the MAMP control panel by clicking 'Start' button on the top right. (MySQL is included but not required if using PostgreSQL).

### Enable PHP Extensions (if needed)

1. Open the `php.ini` file used by your version of PHP inside MAMP, e.g.:  
   ```
   /Applications/MAMP/bin/php/php8.x.x/conf/php.ini
   ```
   If you are using PHP version 8.3.14, then your command to open the file in nano for example would be :
   ```
   nano /Applications/MAMP/bin/php/php8.3.14/conf/php.ini
   ```
3. Ensure the following extensions are enabled (remove `;` if present):  
   ```ini
   extension=intl.so
   extension=pgsql.so
   extension=pdo_pgsql.so
   ```

---

## 3. Install PostgreSQL (with pgAdmin)

🍎 On macOS, you have two great options: a graphical installer or Homebrew.  

### Option 1: The Graphical Installer (Easiest)
1. Go to the [PostgreSQL Downloads page for macOS](https://www.postgresql.org/download/macosx/). (Version 13-16)
2. Download the latest `.dmg` installer from EDB.  
3. Run the installer and follow the wizard (similar to Windows setup):
   - ✅ PostgreSQL Server  
   - ✅ pgAdmin 4  
   - ✅ Command Line Tools  
   - ❌ Stack Builder (optional)  
4. Set a strong password for the `postgres` user.  
5. After installation, open **pgAdmin 4** from Applications.
6. Connect to local server with the **postgres** password.  
7. **Using Query Tool in PgAdmin**
   ```sql
   CREATE USER moodleuser WITH PASSWORD 'your_strong_password';
   CREATE DATABASE moodle WITH OWNER moodleuser ENCODING 'UTF8';
   ```


### Option 2: Using Homebrew (Developer-Focused)
1. Install Homebrew (if not already installed):  
   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```
2. Install PostgreSQL:  
   ```bash
   brew install postgresql@16
   ```
3. Start PostgreSQL service:  
   ```bash
   brew services start postgresql@16
   ```
4. Secure the `postgres` user:  
   ```bash
   psql postgres
   \password postgres
   \q
   ```
5. Install pgAdmin 4:  
   ```bash
   brew install --cask pgadmin4
   ```
6. Use **pgAdmin 4** to create the `moodleuser` and `moodle` database (UTF8).  

---

## 4. Moodle Setup on macOS (with MAMP)

### Pre-requisites:
- macOS (Monterey or later recommended. Works even on Tahoe)  
- MAMP ([Download here](https://www.mamp.info/en/))  
- PostgreSQL (via EDB or Homebrew, see above)  

### Steps:
1. Start **MAMP**.  
2. Download Moodle (from [here](https://download.moodle.org/releases/latest/)) → Extract the `moodle` zip file into the `htdocs` folder. It will look like below path in the end :  
   ```
   /Applications/MAMP/htdocs/moodle
   ```

4. Run Moodle Installer:  
   - Visit → <http://localhost/moodle>  
   - Select **PostgreSQL** as database  
   - Enter credentials for `moodleuser`  
5. Configure PHP (if needed):  
   Edit php.ini at:  
   ```
   /Applications/MAMP/bin/php/php<version>/conf/php.ini
   ```
6. Create `moodledata` directory **outside** `/Applications/MAMP/htdocs/`.  
7. Done! Site available at → <http://localhost:8888/moodle>  

---

## 5. Troubleshooting (macOS)

- ⏱ **“Maximum execution time exceeded”** → Increase `max_execution_time` in `php.ini`.  
- 🐘 **PostgreSQL not detected** → Uncomment (remove `;`) for:  
   ```ini
   extension=pgsql.so
   extension=pdo_pgsql.so
   ```  
- 🌐 **“PHP extension ... must be installed/enabled”** → Uncomment e.g. `extension=intl.so`.  
- 🔗 **Database connection failed”** → Ensure PostgreSQL service is running and credentials are correct.  
- 📂 **“Cannot write to the data directory”** → Ensure write permissions are granted.  

---

## 6. Start Moodle Locally

1. Start **MAMP** (Apache + PHP).  
2. Ensure PostgreSQL is running (via EDB installer or Homebrew).  
3. Visit: **[http://localhost/moodle](http://localhost/moodle)**  

---

## 7. Moodle User Roles Explained

- **Admin** → Full site control  
- **Manager** → Manage courses/users  
- **Course Creator** → Create courses  
- **Teacher** → Manage activities  
- **Student** → Participate in courses  

---

## 8. Types of Moodle Plugins
All below sub folder resides in /Applications/MAMP/htdocs/moodle

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

✨ You now have a complete macOS development setup with Moodle + PostgreSQL.  
