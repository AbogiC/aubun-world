# Aubun World Backend

Lightweight PHP 8 + MySQL API for the existing Vue storefront.

## Structure

- `public/index.php`: front controller and route registration
- `src/Core`: request, response, router, database wiring
- `src/Controllers`: HTTP handlers
- `src/Repositories`: MySQL access and payload mapping
- `src/Middleware/AuthMiddleware.php`: bearer-token authentication gate
- `src/Services/AuthService.php`: token issuing and validation
- `database/schema.sql`: table definitions
- `database/seed.sql`: starter catalog data

## Setup

1. Copy `.env.example` to `.env` and update your MySQL credentials.
2. Create the database named `aubun_world`.
3. Run `schema.sql`, then `seed.sql`.
4. Start the API from `backend/public`.

Example:

```powershell
php -S localhost:8000 -t public
```

## Main Endpoints

- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/me`
- `GET /api/products`
- `GET /api/products/{id}`
- `GET /api/categories`
- `GET /api/cart`
- `POST /api/cart/items`
- `PATCH /api/cart/items/{id}`
- `DELETE /api/cart/items/{id}`
- `POST /api/cart/apply-discount`
- `DELETE /api/cart`
- `GET /api/orders`
- `POST /api/orders/checkout`

## Mix & Match

- `GET /api/mix-match` — slot definitions, curated style presets and live product categories for the Look Studio.
- `POST /api/mix-match/look` — validate and resolve a composed look (country-aware pricing).
  Body: `{ "pieces": [{ "slot": "top", "productId": 6, "size": "M", "color": "White" }] }`
- `GET /api/mix-match/looks` — list the signed-in user's saved looks (auth).
- `POST /api/mix-match/looks` — save a look (auth). Body: `{ "name": "My Look", "pieces": [...] }`
- `GET /api/mix-match/looks/{id}` — fetch a saved look (auth).
- `PATCH /api/mix-match/looks/{id}` — rename / replace a look's pieces (auth).
- `DELETE /api/mix-match/looks/{id}` — delete a saved look (auth).
- `POST /api/mix-match/looks/{id}/add-to-cart` — add every piece of a saved look to the cart (auth).

Slots: `top`, `bottom`, `outer`, `dress`. See `src/Services/MixMatchService.php` for the authoritative slot/preset configuration.
