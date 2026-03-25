# BancoSystem - Project Overview

BancoSystem is a comprehensive banking and loan management platform built with Laravel 12 and React. It features a multi-role administrative interface, an agent portal for referral management, and a customer-facing application wizard that synchronizes between web and WhatsApp.

## Core Technologies

- **Backend:** Laravel 12 (PHP 8.2+), Filament 3 (Admin Panels), Inertia.js (React Adapter)
- **Frontend:** React 19, TypeScript, Tailwind CSS 4, Vite, Radix UI, Lucide React
- **Database:** MySQL/PostgreSQL (configured via `.env`)
- **PDF Generation:** Barryvdh DomPDF
- **Communication:** Twilio (SMS/WhatsApp), CodelSms
- **Payments:** Paynow PHP SDK
- **Testing:** Pest (PHP), Vitest (JS/TS)
- **Deployment:** Docker (Sail), Fly.io (via `fly.toml`)

## Key Features

- **Application Wizard:** Multi-step process for loan and account applications with cross-platform (Web/WhatsApp) synchronization.
- **Admin Panels:** Specialized dashboards for ZB Admin, Accounting, HR, Stores, and Partners built using Filament.
- **Agent Portal:** Dedicated space for agents to manage referrals and generate product links.
- **PDF Management:** Automated generation and batch processing of bank-standard application forms.
- **Payment Integration:** Deposit payment initiation and callback handling via Paynow.
- **Delivery Tracking:** System for tracking the progress of applications and related documentation.

## Getting Started

### Prerequisites

- PHP 8.2+
- Node.js & npm
- Composer
- Docker (optional, for Sail)

### Installation

1.  **Clone the repository and install dependencies:**
    ```bash
    composer install
    npm install
    ```

2.  **Environment Setup:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Configure your database and external service credentials in `.env`.*

3.  **Database Migration & Seeding:**
    ```bash
    php artisan migrate --seed
    ```

### Running the Project

- **Development Mode (Vite + Laravel):**
  ```bash
  composer run dev
  ```
  *This command uses `concurrently` to run the Laravel server, Vite, queue worker, and Pail logs.*

- **Build for Production:**
  ```bash
  npm run build
  php artisan optimize
  ```

### Testing

- **Backend Tests:**
  ```bash
  php artisan test
  # or
  ./vendor/bin/pest
  ```

- **Frontend Tests:**
  ```bash
  npm run test
  # or
  npx vitest
  ```

## Development Conventions

- **Architecture:** 
    - Use **Interfaces** for services (see `app/Contracts`) to ensure swappable implementations (e.g., `SmsProviderInterface`).
    - Follow the **Repository Pattern** for complex data access (see `app/Repositories`).
    - Utilize **Observers** for model-driven events (see `app/Observers/ApplicationStateObserver.php`).
- **Filament:** Custom admin logic should reside within `app/Filament`. Each role has its own panel configuration.
- **Frontend:** 
    - React components are located in `resources/js`. 
    - Use **Tailwind CSS 4** for styling.
    - Prefer functional components and hooks.
- **PDFs:** PDF templates are likely handled via views; logic is centralized in `PDFGeneratorService`.
- **Formatting:** 
    - PHP: `php artisan pint`
    - JS/TS: `npm run format` (Prettier)
    - Linting: `npm run lint` (ESLint)
