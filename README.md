# Green Veil Cosmetic Shop

A full-stack e-commerce platform for cosmetics and skincare, built with **Laravel 13**, **Vue 3**, **Inertia.js**, and **Filament 5**.

---

## System Architecture & Infrastructure

Green Veil is built around a practical, production-ready architecture designed for high performance, low maintenance, and cost efficiency on a single server.

```
[ Visitor ] ──HTTPS──> [ Host Proxy (Nginx/Caddy) ] ──Port 8080──> [ FrankenPHP App Container ]
                                                                       ├── MySQL 8.4 (Database)
                                                                       └── Redis (Cache & Sessions)
```

### Key Infrastructure Decisions

- **FrankenPHP over Nginx + PHP-FPM:** Instead of coordinating two separate containers (a web server and a PHP worker), FrankenPHP embeds PHP directly into the Go-based Caddy web server. This runs as a single lightweight container, halves memory overhead, enables OPcache pre-compilation, and delivers faster response times.
- **Lean Multi-Stage Docker Builds:** The Dockerfile builds dependencies, compiles frontend assets with Node/Vite, and copies only the finished build into a minimal Alpine production image. Build tools and heavy `node_modules` are completely excluded from production.
- **Host Reverse Proxy (Port 8080):** The app container runs on port `8080` rather than taking over ports `80` and `443`. A host-level reverse proxy (Nginx or Caddy) manages SSL certificates and forwards traffic, making it easy to run multiple independent web apps on a single VPS without port conflicts.
- **MySQL 8.4 & Redis Separation:** MySQL manages relational e-commerce data (orders, variants, stock, transactions) with container healthchecks, while Redis handles in-memory sessions and hot caching to protect database performance.
- **Intelligent Storefront Caching & Database Indexing:** Production Redis caching (`AppCache`) with a 5-minute bounded TTL, native Redis domain tags, typed `CacheKey`/`CacheTag` enums, and composite SQL indexes (`products_active_featured_sort_idx`, `products_active_price_idx`, `products_cat_active_sort_idx`). Reactive Eloquent observers automatically flush targeted cache tags when products, taxonomies, promotions, or settings change.
- **Automated CI/CD Pipeline:** GitHub Actions automatically runs code style checks (Pint), static analysis (PHPStan), TypeScript validation (`vue-tsc`), and automated tests (Pest) on every push. When tests pass on `master`, it executes a zero-downtime deployment script over SSH.
- **Monolith with Inertia.js:** Instead of maintaining disconnected frontend and API repositories, Inertia pairs Vue 3 directly with Laravel. This gives users a smooth Single Page Application experience without the added burden of API versioning, CORS configuration, or token syncing.

---

## Tech Stack

| Layer                | Technology                            | Purpose                                                       |
| :------------------- | :------------------------------------ | :------------------------------------------------------------ |
| **Backend**          | Laravel 13 & PHP 8.5                  | Application logic, database ORM, and background workers       |
| **Frontend**         | Vue 3, Inertia.js v3, Tailwind CSS v4 | Reactive client interface with fast page transitions          |
| **Admin Panel**      | Filament 5                            | Back-office management for catalog, orders, and content       |
| **Payments**         | Stripe API & Webhooks                 | Secure checkout and event-driven payment handling             |
| **Database & Cache** | MySQL 8.4 & Redis                     | Relational data persistence and fast in-memory caching        |
| **Runtime & Infra**  | Docker, FrankenPHP, GitHub Actions    | Multi-stage container builds, web server, and automated CI/CD |

---

## Core Features

### Storefront & Customer Experience (Vue 3 & Inertia.js)

- **Product Discovery & Filtering:** Browse the cosmetics catalog with multi-attribute filtering (category, skin type, hair type, active ingredients, claims), real-time search, and optimized cached facet aggregations.
- **Dynamic Product Variants:** Seamless variant switching (sizes, scents, formulations) with instant price, imagery, and stock status updates.
- **Cart & Wishlist Synchronization:** Real-time cart state, persistent guest sessions, and automatic wishlist reconciliation upon login/registration.
- **Frictionless Stripe Checkout:** End-to-end checkout flow supporting country-specific shipping options, delivery threshold calculations, and automated order creation powered by Stripe webhooks.
- **Promotions & Discounts:** Flexible discount code engine with cart minimums, usage caps, expiration windows, and instant promotion breakdown in checkout.
- **Customer Account Portal:** Self-service profile to inspect past orders, fulfillment statuses, personal details, and manage up to 3 saved shipping addresses with default address designations and direct checkout integration.

### Back-Office Administration (Filament 5)

- **Catalog & Inventory Control:** Full CRUD management for products, brands, categories, variants, and gallery media with real-time stock tracking.
- **Customer 360 & Order Fulfillment:** Inspect incoming orders, customer details, payment states, active carts, wishlists, and read-only saved shipping addresses.
- **Marketing & Promotions:** Create and schedule coupon codes, homepage slider banners, and promotional campaigns.
- **Granular Cache Management:** Reactive cache invalidation across all models plus a dedicated artisan CLI command (`php artisan app:cache-flush`) for tag- or key-based maintenance.
- **Role-Based Access Control (RBAC):** Granular administrative role and user management separating store staff from regular customers.
- **Dashboard Analytics:** Overview widgets tracking key business metrics, recent order volumes, and inventory alerts.

---

## Local Development

> **Note:** The included `compose.yaml` and `Dockerfile` are strictly for **production**. For local development, use either Lerd or native host tools.

### Option 1: Lerd (Recommended)

Spins up PHP 8.5, MySQL, Redis, Mailpit, and background workers automatically:

```bash
lerd start
composer setup
```

App is live at: `https://green-veil-cosmetic-shop.test`

### Option 2: Host Environment (PHP 8.5 & Node.js)

```bash
cp .env.example .env
composer install && npm install
php artisan key:generate
php artisan migrate --seed
composer run dev
```

---

## Production Deployment

The project deploys with a simple script that pulls changes, rebuilds containers, runs pending migrations, and restarts services:

```bash
# Initial clone and environment
git clone <REPO_URL> /var/www/green-veil-cosmetic-shop
cd /var/www/green-veil-cosmetic-shop
cp .env.docker.example .env

# Deploy containers
chmod +x deploy.sh
./deploy.sh
```

### Host Nginx Proxy Example

```nginx
server {
    server_name shop.yourdomain.com;
    client_max_body_size 100M;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

---

## Testing & Code Quality

Run all quality checks and automated tests with a single command:

```bash
composer test               # Runs Pint, PHPStan, and Pest test suite in parallel
composer lint               # Automatically format code with Laravel Pint
composer types:check        # PHP static analysis (PHPStan / Larastan)
npm run check:fix           # Format and lint frontend code (Biome / Vite)
npm run types:check         # Vue/TypeScript type verification
php artisan app:cache-flush # Flush managed Redis storefront cache by tag or key
```

---

## Project Structure

```
├── app/
│   ├── Enums/           # Domain enums (CacheKey, CacheTag, OrderStatus, etc.)
│   ├── Filament/        # Admin resources, relations, and dashboard widgets
│   ├── Http/Controllers# Storefront and checkout controllers (Cart, Stripe, etc.)
│   ├── Models/          # Database models (Products, Variants, Orders, Promotions)
│   ├── Observers/       # Reactive cache invalidation observers
│   └── Services/        # Domain services & caching layer (AppCache, CartService)
├── resources/js/        # Vue 3 pages, components, and layouts
├── docker/              # Container boot scripts and configurations
├── tests/               # Pest feature and unit tests
├── compose.yaml         # Production Docker services (App, MySQL, Redis)
└── deploy.sh            # Automated server deployment script
```

---

## License

This project is open-source and licensed under the [MIT License](LICENSE).
