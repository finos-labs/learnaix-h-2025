# Moodle Local Docker (PHP 8.2 + Apache + MySQL 8.4)

A simple, one-shot local setup for **Moodle 5.x** using Docker. No PHP or MySQL installs on your machine — everything runs in containers.

## What you get

- **moodle-app**: Apache + PHP 8.2 with all Moodle-required extensions preinstalled (`gd`, `mbstring`, `intl`, `zip`, `mysqli`, `sodium`, `opcache`, `exif`, `soap`).
- **moodle-db**: MySQL **8.4** configured for UTF‑8 (`utf8mb4`).
- **cron** inside the app container (runs Moodle cron every minute).
- A host-mounted `moodledata/` folder for persistent file storage.
- Sensible PHP settings for local dev (larger uploads, OPcache).

## Prerequisites (install once)

- **Docker Desktop** (Windows/macOS) or **Docker Engine** (Linux) with **Compose v2**.
- Allocate at least **2–4 GB** of RAM to Docker Desktop (Settings/Preferences → Resources).

## Quick Start (30 seconds)

1) **Clone the repo**
```bash
git clone <your-repo-url>
cd moodle-local-base
```

2) **Start everything**
- Windows PowerShell / macOS / Linux:
```powershell
docker compose up -d --build
```

This will:
- build the Moodle image,
- pull MySQL 8.4 if needed,
- create a fresh DB volume,
- start both containers.

3) **Open Moodle**
```
http://localhost:8080
```

If you see the installer, continue with the **DB settings** below.

## Database settings (in the Moodle installer)

- **Database type**: MySQL  
- **Host**: `db`  
- **Port**: `3306`  
- **Database name**: `moodle`  
- **User**: `moodle`  
- **Password**: `moodle`  

**Data directory** when asked:
```
/var/www/moodledata
```

## Project structure

- `docker-compose.yml` — defines **db** (MySQL 8.4) and **moodle** (Apache + PHP) services.
- `Dockerfile` — builds PHP 8.2 with required extensions and fetches Moodle 5.x.
- `config.php` — Moodle config pointing to the `db` service.
- `php.ini` — local PHP settings (upload limits, OPcache, etc.).
- `moodledata/` — created automatically (mounted for persistence).

## Common commands

- **Start (or rebuild)**
```powershell
docker compose up -d --build
```

- **Stop**
```powershell
docker compose down
```

- **Full reset** (stop + delete DB/files volume)
```powershell
docker compose down -v
```

- **Logs**
```powershell
docker compose logs -f moodle
docker compose logs -f db
```

- **Open a MySQL shell**
```powershell
docker exec -it moodle-db mysql -umoodle -pmoodle moodle
```

- **Verify PHP extensions are loaded**
  - Windows PowerShell:
    ```powershell
    docker exec moodle-app php -m | findstr /R /I "mbstring intl zip gd mysqli sodium opcache exif soap"
    ```
  - macOS/Linux:
    ```bash
    docker exec moodle-app sh -lc 'php -m | egrep -i "mbstring|intl|zip|gd|mysqli|sodium|opcache|exif|soap"'
    ```

## Troubleshooting

### Moodle says “MySQL 8.4 required”
You’re covered — the compose uses **mysql:8.4**. If Moodle still complains:
1. You might be using an **old DB volume** from a previous run. Do a full reset:
   ```powershell
   docker compose down -v
   docker compose up -d --build
   ```
2. Check that `$CFG->dbhost` in `config.php` is `db` (not `localhost`).

### DB container shows “unhealthy”
- First boot can take time on Windows/macOS. Give it a minute and check:
  ```powershell
  docker logs moodle-db --since=10m
  ```
- We **don’t publish port 3306** to avoid conflicts with any local MySQL.
- If you’re on ARM Windows/macOS and see architecture issues, add:
  ```yaml
  platform: linux/amd64
  ```
  under the `db` service in `docker-compose.yml`.

### Permission issues on `moodledata/`
- You don’t need `chmod` on Windows; the container sets ownership internally.
- If you moved the project, remove the old volume and try again:
  ```powershell
  docker compose down -v
  docker compose up -d --build
  ```

## Cleanup (remove containers/images/volumes)

- Stop & remove containers:
  ```powershell
  docker compose down
  ```
- Also remove the DB/file volumes (⚠️ wipes data):
  ```powershell
  docker compose down -v
  ```
- Remove the built image:
  ```powershell
  docker image rm moodle-local-base-moodle:latest
  ```
- Remove dangling images/volumes:
  ```powershell
  docker system prune -f
  docker volume prune -f
  ```

## Notes & Caveats

- This setup is for **local development** only.
  - Use strong passwords, backups, TLS, and proper tuning for production.
- PHP settings like `upload_max_filesize` and `post_max_size` are increased for convenience. Adjust in `php.ini` if needed.
- Rebuilding after editing the `Dockerfile` or `php.ini`:
  ```powershell
  docker compose up -d --build
  ```

---

**Happy Moodling!** If teammates run into trouble, have them share `docker compose version`, Docker Desktop version, and `docker logs` for `moodle-db` and `moodle`.
