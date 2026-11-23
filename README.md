# ameba

ameba is a responsive digital game marketplace MVP inspired by Eneba's marketplace layout. It uses a React + TypeScript frontend and a PHP JSON REST API with SQLite. Product and homepage content comes from backend endpoints; React contains no catalog data. The idempotent demo seed currently provides 23 products with seller offers, including Nintendo Switch listings.

## Requirements

- Node.js 20+ and npm.
- PHP 8.1+ with PDO SQLite enabled.

The current environment has Node 26 installed under `C:\Program Files\nodejs` (not on PATH), but does not have PHP, Composer, Git, or Docker. Use PHP from a local install or host the API on a PHP service. No Composer packages are required.

## Run locally

1. Copy `.env.example` to `.env` and adjust values if desired. The PHP API reads this file directly; process environment values take precedence. The existing SQLite file remains `data/pixelmarket.sqlite` so the rebrand preserves current data; this is a legacy filename only.
2. Start the API from the project root: `php -S 127.0.0.1:8001 -t api api/index.php`.
3. In a second terminal: `npm install` then `npm run dev`.
4. Confirm `http://127.0.0.1:8001/api/health` returns `{"status":"ok","database":"sqlite"}`, then open `http://localhost:5173`. Vite proxies `/api` requests to `API_URL` from `.env` (default `http://127.0.0.1:8001`). The API initializes SQLite and idempotently fills in any missing demo products, offers, and keys when handling a request; existing products, offers, users, and orders are preserved.

Register an account to try simulated checkout and order history. Passwords are hashed. No real card data or payment is used.

## Install PHP on Windows

For this project, the CLI and built-in web server are enough. On a 64-bit Windows PC:

1. Install the **Microsoft Visual C++ Redistributable 2015–2022 x64** if it is not already installed.
2. From the [official PHP for Windows downloads](https://windows.php.net/download/), download the current stable **x64 Non Thread Safe ZIP** build. NTS is the CLI/FastCGI build type; avoid a source package or debug pack.
3. Extract it to `C:\tools\php` (so `C:\tools\php\php.exe` exists).
4. In that directory, copy `php.ini-development` to `php.ini`. Edit `php.ini`: set `extension_dir = "ext"`, and enable `extension=pdo_sqlite` and `extension=sqlite3` (remove a leading `;` if those lines are already present). Confirm the corresponding DLLs exist in `C:\tools\php\ext`.
5. Add `C:\tools\php` to your Windows user `Path` under **Settings → System → About → Advanced system settings → Environment Variables**. Open a fresh PowerShell window so it reads the updated PATH. Keeping the PHP directory in PATH also makes its SQLite DLL available to Windows.
6. Verify the install:

   ```powershell
   php -v
   php --ini
   php -m | Select-String 'PDO|pdo_sqlite|sqlite3'
   ```

   The module list should include `PDO`, `pdo_sqlite`, and `sqlite3`. PHP's Windows documentation explains the [binary/build choices](https://www.php.net/manual/en/install.windows.manual.php); the PDO docs list the [Windows driver setting](https://www.php.net/manual/en/pdo.installation.php) and the [SQLite DLL requirement](https://www.php.net/pdo-sqlite).

Then start the backend from the project root with `php -S 127.0.0.1:8001 -t api api/index.php` and the frontend in a second terminal with Node on PATH using `npm install` and `npm run dev`.

## Architecture

- `src/ui/App.tsx`: single-page React app, shared state, REST calls, and screens.
- `src/ui/HomePage.tsx`: ameba campaign banner and platform shortcuts, using the existing homepage API data.
- `src/ui/MarketplaceHeader.tsx`: shared search, navigation, account, and cart header.
- `public/ameba-logo.svg` and `public/favicon.svg`: original ameba logo and favicon.
- `src/ui/styles.css`: responsive dark purple marketplace styling.
- `api/index.php`: small PHP router, SQLite initialization/seeding, catalog, session auth, checkout, and order history.
- `database/schema.sql`: database schema reference.
- `docs/API.md`: route reference.

Each homepage block calls its own route (`/home/banner`, `/home/bestsellers`, `/home/deals`). Search supports partial names, with a basic typo fallback. Catalog filters and sorting are API-backed. Product detail URLs use `/product/{slug}` and retain browser history navigation. Checkout reserves inventory inside a database transaction and returns one demo key per purchased unit; selecting a seller offer carries that offer ID through checkout. Display currency supports EUR and USD using an adjustable demo rate in `src/ui/money.ts`; database prices remain EUR.

## Deployment

Deploy `api/` to a PHP 8.1+ host with PDO SQLite enabled and writable persistent storage. Set `DB_PATH` to that storage location, `APP_ORIGIN` to the public frontend origin, and `SESSION_SECURE=true`; use HTTPS. Build the frontend with `npm install && npm run build`, setting `VITE_API_BASE` to the API URL plus `/api` (for example `https://api.example.com/api`), then publish `dist/` to a static host. SQLite is suitable for this demo; use managed PostgreSQL/MySQL and a shared session store for multi-instance production hosting.

## GitHub

Create a repository, run `git init`, commit the project, then add the GitHub remote and push from your machine. No repository remote or Git executable was available in the inspected environment. Review `.gitignore` and keep `.env` and database files out of version control.

## Limitations

- Seller data, images, and keys are demonstration content; keys are not redeemable.
- Payment is simulated; no real payments are processed.
- Search typo tolerance is a lightweight fallback, not a dedicated search index.
- PHP is unavailable in the current environment, so API runtime execution and full end-to-end verification require PHP + PDO SQLite.
- No public repository or deployed URL has been created.

## Conversation log

The human-readable session export is in [`docs/AI_LOG.md`](docs/AI_LOG.md). `docs/AI_LOG_SOURCE.jsonl` is the preserved original source snapshot; `docs/AI_LOG_SOURCE_CURRENT.jsonl` is the newer snapshot. Use `node scripts/export-history.mjs <session.jsonl>` to refresh the Markdown export from a Codex rollout JSONL file.
