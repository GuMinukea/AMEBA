# AMEBA API

All routes are under `/api`. JSON is used for request and response bodies. Authentication uses a PHP session cookie.

| Method | Route | Description |
|---|---|---|
| GET | `/health` | API and database status |
| GET | `/home/banner` | Homepage banner content |
| GET | `/home/bestsellers` | Popular products |
| GET | `/home/deals` | Discounted products |
| GET | `/products` | Catalog. Optional `q`, `platform`, `region`, `min`, `max`, `sort` (`popularity`, `price_asc`, `price_desc`) |
| GET | `/products/{slug}` | Product and seller offers |
| POST | `/auth/register` | Register with `name`, `email`, `password` |
| POST | `/auth/login` | Login with `email`, `password` |
| POST | `/auth/logout` | End session |
| GET | `/auth/me` | Current user or `null` |
| POST | `/checkout` | Authenticated simulated checkout with `items: [{slug, offer_id?, quantity}]`; a supplied offer ID is validated against the product slug |
| GET | `/orders` | Authenticated order history and delivered demo keys |

If `offer_id` is supplied, checkout uses that seller's offer and inventory. If omitted, it falls back to the lowest priced offer. Checkout returns a unique key from local demo inventory and never contacts a payment service.

The idempotent seed source is `database/demo-catalog.json`. It contains the demo product list and is applied when the API initializes the SQLite database. Catalog artwork references are external URLs and require internet access in the browser.
