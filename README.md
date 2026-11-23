# ameba

A responsive digital game marketplace MVP inspired by Eneba. Built with React, TypeScript, PHP REST API, and SQLite.

## Features

- Dynamic homepage with banners, bestsellers, and deals
- Game catalog with search, filters, and sorting
- Genre, platform, region, and price filters
- Product pages with multiple seller offers
- Shopping cart and wishlist
- User registration and authentication
- Simulated checkout and digital key delivery
- User-specific order history
- EUR and USD currency display
- Responsive dark-themed interface

## Tech Stack

- **Frontend:** React, TypeScript, Vite
- **Backend:** PHP 8.1+
- **API:** JSON REST API
- **Database:** SQLite
- **Database access:** PDO SQLite

## Requirements

- Node.js 20+
- npm
- PHP 8.1+ with PDO SQLite enabled

No Composer packages are required.

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/GuMinukea/AMEBA.git
cd AMEBA
```

### 2. Configure environment

Copy `.env.example` to `.env`.

Default local configuration:

```env
APP_ENV=development
APP_URL=http://localhost:5173
APP_ORIGIN=http://localhost:5173
API_URL=http://127.0.0.1:8001
VITE_API_BASE=/api
DB_PATH=./data/pixelmarket.sqlite
SESSION_SECURE=false
```

### 3. Start the backend

```bash
php -S 127.0.0.1:8001 -t api api/index.php
```

### 4. Install and start the frontend

In a separate terminal:

```bash
npm install
npm run dev
```

Open `http://localhost:5173`.

### 5. Verify the API

Visit `http://127.0.0.1:8001/api/health`.

Expected response:

```json
{
  "status": "ok",
  "database": "sqlite"
}
```

The API initializes the SQLite database and adds missing demo data automatically.

## Project Structure

```text
ameba/
├── api/                  # PHP REST API
├── database/             # SQLite schema and demo seed data
├── design-references/    # Design reference screenshots
├── docs/                 # API documentation and AI logs
├── public/               # Static assets and logos
├── scripts/              # Development utilities
├── src/                  # React application
├── .env.example
├── .gitignore
├── package.json
└── README.md
```

## API & Database

- Homepage content is loaded from backend endpoints.
- Catalog search, filtering, and sorting are API-backed.
- Product pages support multiple seller offers.
- Checkout validates inventory and uses database transactions.
- Digital keys are delivered per purchased unit.
- Wishlist and order history are associated with authenticated users.

See [`docs/API.md`](docs/API.md) for API documentation.

## Deployment

The frontend and backend must be hosted separately or on a provider supporting both.

- Build the frontend with `npm run build`.
- Publish the generated `dist/` directory.
- Deploy `api/` to a PHP host with PDO SQLite enabled.
- Configure `VITE_API_BASE`, `DB_PATH`, `APP_ORIGIN`, and `SESSION_SECURE`.
- Use HTTPS and persistent storage for the SQLite database.

**Deployment status:** Not yet deployed.

## Limitations

- Products, sellers, images, and digital keys are demo content.
- Digital keys are not redeemable.
- Payments are simulated; no real transactions are processed.
- Currency conversion uses a configurable demo exchange rate.
- SQLite is intended for this MVP, not large-scale production use.

## GitHub

[https://github.com/GuMinukea/AMEBA](https://github.com/GuMinukea/AMEBA)

## AI-Assisted Development

AI-assisted development conversation history is included in `docs/`:

- `AI_LOG.md` – Human-readable conversation export.
- `AI_LOG_SOURCE.jsonl` – Original source snapshot.
- `AI_LOG_SOURCE_CURRENT.jsonl` – Updated source snapshot.
