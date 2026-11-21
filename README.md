# Green Veil

Green Veil is an e-commerce shop: browse a product catalogue, filter it every which way, fill a bag, apply promo codes and pay with Stripe. Behind it sits a Filament admin for running the store, managing orders, refunds and stock, and the whole thing ships as one Laravel app.

The application will process everything as it should, however seeding will prepare database ready for demo purposes.

**Stack:** Laravel 13 · PHP 8.5 · Vue 3 + TypeScript · Inertia.js v3 · Tailwind CSS v4 · Filament 5 · Stripe · MySQL 8.4 · Redis · FrankenPHP · Docker · Pest

---

## Why it's built the way it is

### One app, no separate API: Inertia + Vue

The storefront is a Vue SPA, but Laravel still owns routing, auth, validation and data. Inertia passes props straight from controllers to Vue pages, so there's no REST layer to version, no CORS, no token juggling, and no duplicated validation. [Wayfinder](https://github.com/laravel/wayfinder) generates typed TypeScript helpers for every route, so the frontend never hardcodes a URL.

It still behaves like an API where that matters. Pagination is a _partial reload_: it asks only for the `products` prop, and the facet and category props are lazy closures the server skips entirely. Bestsellers and related products are deferred props, and product links prefetch on hover.

### Actions for things that _happen_, services for things that _are_

- **Actions** (`app/Actions`) are single-purpose classes with one `execute()` method: `PlaceOrder`, `MarkOrderPaid`, `RefundOrder`, `ReleaseOrderReservations`… Each one is a business event that owns its own transaction and row locks. The reason: the same event gets triggered from very different places. Releasing an order's stock happens on a gateway failure, a failed or expired payment, the stale-order sweep, an admin refund, an admin cancel and Stripe's refund webhook. With one action, that logic lives in exactly one place. Controllers, queued jobs, Filament buttons and Artisan commands all call the same class.
- **Services** (`app/Services`) wrap ongoing state or read models that need many related methods. `CartService` hides whether a cart lives in the session (guest) or the database (signed in). `ProductCatalogService` builds filter queries and facet counts.
- **Controllers stay thin:** validate with a Form Request, call an action or service, return a page.

I skipped a full repository layer or DDD-style modules on purpose. For a shop this size, Eloquent plus actions keeps things readable without extra indirection.

### Payments behind a contract

`PaymentGateway` is an interface. `PaymentManager` picks Stripe or an offline driver (bank transfer, cash on delivery) per order. Checkout and refunds never `if`/`else` on the provider, so adding a new one means adding one class.

### Money correctness over convenience

- **Stock is reserved when the order is placed, not when it's paid.** Two people can't buy the last item at the same time, because rows are locked in ID order to avoid deadlocks.
- **Reservations are released exactly once.** A `reservations_released_at` stamp makes it idempotent, so a refund followed by Stripe's refund webhook can't restock twice.
- **Webhooks are stored first, then processed by a queued job** under a row lock. Stripe retries are free and duplicates are no-ops.
- **Promotions are resolved server-side by one resolver** shared by the cart page and order placement, so the total you see is the total you're charged. The browser never decides which discount applies.

### Guest-first cart

The bag lives in `localStorage` and syncs to the server in the background (debounced), so the UI never waits on a request. When a guest signs in, their bag and wishlist merge into the account. Earlier guest orders attach only after the email is verified, so nobody can claim someone else's order history by registering with their address.

### Caching that actually invalidates

`AppCache` wraps Redis with typed `CacheKey` / `CacheTag` enums, so there are no magic strings. Model observers flush the right tags when products, categories, promotions or settings change. Short TTLs act as a safety net, not the main mechanism.

### A design system instead of one-off styling

- **Design tokens:** colours, type scale, radii and shadows live in one place, `resources/css/app.css`.
- **Shared UI:** `Button`, `Card`, `Drawer`, `Modal`, `Badge` and friends live in `resources/js/components/ui`.
- **A spacing rule:** only clean rem steps (0.25, 0.5, 0.75, 1, 1.5, 2, 3, 4…).

### Filament for the back office

The admin side (catalogue, orders, refunds, promotions, roles) is Filament instead of a hand-built dashboard. It's internal tooling, and Filament gets it to production quality with a fraction of the code, leaving the custom work for the customer-facing storefront.

### Boring, cheap infrastructure

One FrankenPHP container (PHP embedded in Caddy, no separate FPM), plus MySQL and Redis. It uses a multi-stage Docker build and sits behind a host reverse proxy on port 8080, so several apps can share one VPS. GitHub Actions runs every check and deploys over SSH when `master` is green.

---

## Features

**Storefront:** catalogue with multi-facet filters (brand, skin type, ingredients, claims…), live search, product variants and shade swatches, bag and wishlist that work for guests, Stripe Checkout or offline payment, promo codes with automatic offers, order tracking, saved addresses and email verification.

**Admin (Filament):** products, variants and media; categories and brands; orders with refunds (partial or full) and cancellations; promotions; homepage slider; countries and delivery and payment options; Stripe event log; role-based staff access; dashboard metrics.

---

## Running it locally

With Lerd (PHP, MySQL, Redis, Mailpit and workers handled for you):

```bash
lerd start
composer setup
```

Or on your own PHP 8.5 + Node setup:

```bash
cp .env.example .env
composer install && npm install
php artisan key:generate
php artisan migrate --seed   # also downloads the seed product photos once
composer run dev
```

Seeded logins (password `password` for both): `customer@example.com` for the storefront, `admin@example.com` for `/admin`.

> `compose.yaml` and the `Dockerfile` are for production only.

## Quality checks

```bash
composer test          # Pint, PHPStan and Pest
npm run check          # lint and format (Vite+)
npm run types:check    # vue-tsc
```

## Deploying

```bash
git clone <REPO_URL> /var/www/green-veil-cosmetic-shop
cd /var/www/green-veil-cosmetic-shop
cp .env.docker.example .env
./deploy.sh            # pull, rebuild, migrate, restart
```

Point a host Nginx or Caddy at `127.0.0.1:8080` for TLS.

## Where things live

```
app/
├── Actions/      # Business events: PlaceOrder, MarkOrderPaid, RefundOrder…
├── Services/     # Cart, catalogue queries, cache layer
├── Payments/     # Gateway contract + Stripe and offline drivers
├── Jobs/         # Queued Stripe webhook processing
├── Http/         # Thin controllers, Form Requests, API Resources
├── Filament/     # Admin panel
└── Observers/    # Cache invalidation
resources/
├── css/app.css   # Design tokens
└── js/           # Vue pages, components/ui primitives, composables
tests/            # Pest feature and unit tests
```

## License

MIT
