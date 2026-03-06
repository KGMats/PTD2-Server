# PTD2 Server
An open-source server for the Flash game Pokémon Tower Defense 2, made by reverse engineering the original game SWF


# Goals
- [x] Make Story mode playable
- [x] Make 1v1 mode playable
- [x] Make Gym Challenges playable
- [ ] Implement Mystery gifts
- [ ] Recreate pokecenter
- [x] Implement multiplayer (Trainer VS)
- [x] Implement MySQL-based saves

# How to host my own PTD2 server?
## Running with Docker (Recommended way)

**Requirements:**
* Docker + Docker Compose

1. Clone the repo using `git clone https://github.com/KGMats/PTD2-Server-Code.git` or Download the [server code](https://github.com/KGMats/PTD2-Server-Code/archive/refs/heads/master.zip) and unzip it.
2. Open a terminal in the server code directory.
3. Copy .env.example to .env
4. (optional) edit `.env` if you want to change ports / passwords / …
5. Run:

```bash
docker compose up -d --build
```

The server should now be available at http://localhost:8000 (or whatever `APP_PORT` you set).


### Configuring Environment Variables
The server reads configuration from a `.env` file. A template is included as `.env.example`.  


`DB_HOST` should remain db (the Docker service name), unless you change your Compose service.
`DB_USER` / `DB_PASS` / `DB_NAME` match the defaults in the db service in docker-compose.yml.

## Running without Docker (advanced users)

This path requires more manual setup and is **not recommended** for most people.

**Requirements:**
* PHP-FPM 8.1+ (with mysqli extension)
* Web server (nginx / Apache)
* MySQL/MariaDB (or stick to JSON saves)

1. Clone or download & unzip the repo
2. Copy `.env.example` to `.env` and configure database, credentials and ports
3. Point web server root to /{absolute path to PTD2-Server-Code}/app/public
4. Start PHP-FPM and web server


# For developers:
The code is divided as follows:
* Logic related to saves is in `json.php` (for JSON saves, use only as a fallback) and `MySQL.php` for SQL saves.
* Server methods (save/load accounts etc.) are in `ptd2_save_12.php`
* Functions to obfuscate and deobfuscate data are in `obfuscation.php`
* Pull requests welcome, especially for bug fixes, missing PTD2 features, or better error handling


Thanks for checking out / contributing to the project!
