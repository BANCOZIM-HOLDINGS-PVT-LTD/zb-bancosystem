# ZB Bancosystem

## Overview

ZB Bancosystem is a Laravel 12 application with a React/Inertia frontend built for digital loan and account application processing. It is designed to support:

- online application wizard flows
- referral and agent-driven application entry
- WhatsApp and web application synchronization
- payment deposit initiation and webhook handling
- document upload and PDF generation
- admin and agent portals
- Docker-based local and production deployment

## Repository Structure

- `app/` — Laravel backend code, models, controllers, services, observers, providers
- `resources/js/` — React frontend components and pages
- `routes/` — route definitions for web and API endpoints
- `config/` — Laravel configuration files and third-party service mappings
- `docker-compose.yml` — multi-container application stack definition
- `Dockerfile` — production image build instructions
- `.github/workflows/ci-cd.yml` — CI/CD workflow definitions
- `fly.toml` — Fly.io deployment configuration
- `composer.json` / `package.json` — PHP and JavaScript dependencies
- `scripts/` — deployment helper scripts

## Quick Start: Local Development

### Prerequisites

- PHP 8.2
- Composer
- Node.js 18
- npm
- MySQL 8.0
- Redis 7
- Optional: Docker and Docker Compose for containerized local development

### Native Local Setup

1. Clone the repository.
2. Install backend dependencies:
   ```bash
   composer install
   ```
3. Install frontend dependencies:
   ```bash
   npm ci
   ```
4. Create the environment file:
   ```bash
   cp .env.example .env
   ```
5. Update `.env` with local values for database, Redis, and integrations.
6. Generate the app key:
   ```bash
   php artisan key:generate
   ```
7. Run database migrations:
   ```bash
   php artisan migrate --force
   ```
8. Create the storage symlink:
   ```bash
   php artisan storage:link
   ```
9. Start the Vite frontend server:
   ```bash
   npm run dev
   ```
10. Start the Laravel backend server:
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```

Open the site at `http://127.0.0.1:8000`.

### Composer Convenience Command

Use the `composer dev` command to run frontend, backend, and queue listener together:

```bash
composer dev
```

This runs:
- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `npm run dev`

### Docker Compose Local Setup

1. Create or update `.env` with values for `DB_PASSWORD`, `DB_ROOT_PASSWORD`, and optional third-party credentials.
2. Start containers:
   ```bash
   docker compose up -d --build
   ```
3. Run migrations inside the app container:
   ```bash
   docker compose exec app php artisan migrate --force
   ```
4. Optionally cache configuration:
   ```bash
   docker compose exec app php artisan config:cache
   ```
5. Access the app at `http://localhost`.

Included containers:
- `app` — Laravel application service
- `nginx` — reverse proxy
- `database` — MySQL 8.0
- `redis` — Redis cache, queue, and sessions
- `queue` — Laravel queue worker
- `scheduler` — Laravel scheduler loop
- optional `monitoring` and `elasticsearch`

## Environment Variables

This application depends on environment variables stored in `.env`. Do not commit secret values.

Important variables:

- `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT`
- `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `TWILIO_*`, `WHATSAPP_CLOUD_API_*`, `RAPIWHA_API_KEY`, `PAYNOW_*`, `CODEL_*`
- `ECOCASH_*`, `DIDIT_*`, `SSB_*`
- `MAX_FILE_SIZE`, `ALLOWED_FILE_TYPES`, `FORCE_HTTPS`

The application loads integration settings from `config/services.php`.

## Deployment

### GitHub Actions CI/CD

The repository includes `.github/workflows/ci-cd.yml` with the following pipeline:

- `test` — install dependencies, run migrations, build frontend, run static analysis, run PHPUnit, and lint
- `security` — run Composer audit, npm audit, and Snyk scan
- `build` — build and push Docker image to GitHub Container Registry
- `deploy-staging` — deploys staging on `develop` branch via SSH
- `deploy-production` — deploys on release events via SSH
- `notify` — notifies Slack on deployment status

Deployment steps use remote `docker-compose` commands and include:

- `docker-compose pull`
- `docker-compose up -d`
- `php artisan migrate --force`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `docker-compose restart queue`

### Fly.io Deployment

There is a `fly.toml` configuration file for Fly.io deployment.

Key settings:
- App name: `bancosystem`
- Primary region: `jnb`
- Build: `Dockerfile` production target
- Port: `80`
- `force_https = true`
- Persistent storage mount for `/var/www/html/storage/app/public`
- `release_command = "php artisan migrate --force"`

> Note: `fly.toml` sets `DB_CONNECTION = "pgsql"`, while local compose uses MySQL. Confirm the production database type before deploying.

### Local Windows Helper

A helper script exists at `deploy.bat` to deploy through Fly.io using `flyctl`.

## Integrations

### Payment

- **Paynow** — Zimbabwe payment gateway
  - Configured via `config/services.php` under `paynow`
  - Uses `paynow/php-sdk`
  - Endpoints include `/deposit/initiate`, `/deposit/callback`, and `/deposit/status/{referenceCode}`

### Messaging and Notifications

- **Twilio** — SMS and WhatsApp messaging
  - Configured via `config/services.php` under `twilio`
  - Supports an SMS sender and WhatsApp sender
- **WhatsApp Cloud API** — Meta WhatsApp Cloud integration
  - Configured under `whatsapp_cloud`
  - Supports webhook verification and message delivery
- **Rapiwha** — legacy WhatsApp provider
  - Included for compatibility, but currently marked as deprecated in comments
- **Codel** — SMS bulk messaging service
- **Didit** — identity/verification API placeholder
- **SSB** — external employer/service verification integration

### PDF

- **DomPDF** — PDF generation package
  - Used for account opening forms, receipts, and application PDFs
- PDF logging and monitoring configuration exists in `config/pdf_logging.php` and `config/pdf_visual_testing.php`

### Caching and Queues

- **Redis** — used for cache, queues, sessions, and more in production container setup
- Queue workers are run by the `queue` container and scheduled tasks by `scheduler`

### Observations

- `google-gemini-php/client` is present in `composer.json` but not actively referenced in code. It may be a future or deprecated dependency.
- `fly.toml` and local compose use different database drivers and require verification.
- GitHub Actions references `npm run test:unit`, but the repository currently does not define that script in `package.json`.

## Core Routes

### Public routes
- `/` — homepage
- `/application` — application wizard start
- `/apply` — referral entry point
- `/application/resume/{identifier}` — resume saved application
- `/application/status` — application status page
- `/application/success` — success page
- `/download-ssb-form`, `/download-zb-account-form`, `/download-account-holders-form`, `/download-sme-account-opening-form`

### API routes
- `/api/products/*` — product and catalog endpoints
- `/api/boosters/*` — SME booster assets
- `/api/school-boosters/*` — school booster assets
- `/api/states/*` — application session state management
- `/api/agents/*` — agent lookup and referral validation
- `/api/documents/*` — upload and delete document files
- `/api/whatsapp/*` — WhatsApp webhook and status callbacks

### Admin and Agent routes
- `/admin/portal` — admin portal entry page
- `/agent/login` — agent portal login
- `/agent/dashboard` — agent portal dashboard

## Developer Notes

### Frontend
- React components live in `resources/js/`
- The application uses Inertia to render React pages from Laravel routes
- UI libraries include Radix UI, Tailwind CSS, and Lucide icons

### Backend
- Routes are defined in `routes/web.php` and `routes/api.php`
- Middleware configuration is in `bootstrap/app.php`
- Filament admin provider classes are registered in `bootstrap/providers.php`
- Key service providers and third-party integrations are declared through `config/services.php`

### Testing and Quality
- Run PHP tests:
  ```bash
  php artisan test
  ```
- Run ESLint:
  ```bash
  npm run lint
  ```
- Run TypeScript type check:
  ```bash
  npm run types
  ```
- Build frontend assets:
  ```bash
  npm run build
  ```

## Known Issues and Next Steps

- Add a proper `npm run test:unit` script if frontend tests exist, or update CI to the correct command.
- Confirm whether production uses MySQL or PostgreSQL, and align `fly.toml` to match.
- Review and remove unused/deprecated messaging providers if no longer required.
- Keep all secret credentials out of the repository and in environment provisioning systems.

## Contact and Support

For questions about deployment, environment variables, or integrations, review the config files in `config/` and the route definitions in `routes/`.

If you need a more specific internal architecture diagram, refer to:
- `routes/web.php`
- `routes/api.php`
- `config/services.php`
- `docker-compose.yml`
- `Dockerfile`
- `fly.toml`
