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
