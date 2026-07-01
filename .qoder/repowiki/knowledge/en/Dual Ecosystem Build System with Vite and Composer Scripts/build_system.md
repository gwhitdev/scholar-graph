## Overview

This Laravel React Starter Kit uses a **dual-ecosystem build system** combining PHP/Composer for backend dependency management and Node.js/npm for frontend tooling, unified through Vite as the asset bundler. The project relies on **script-based orchestration** via `composer.json` scripts rather than a Makefile or shell scripts.

## Build Tools & Frameworks

### Backend (PHP)
- **Dependency Manager**: Composer (`composer.json`, `composer.lock`)
- **Testing Framework**: Pest PHP (`pestphp/pest`) configured via `phpunit.xml`
- **Static Analysis**: PHPStan via `larastan/larastan` (command: `composer types:check`)
- **Code Formatting**: Laravel Pint (`laravel/pint`) for PHP code style
- **Development Server**: Laravel's built-in `artisan dev` command

### Frontend (JavaScript/TypeScript)
- **Package Manager**: npm (`package.json`, `package-lock.json`)
- **Build Tool**: Vite v8 with plugins:
  - `laravel-vite-plugin` — Laravel integration with HMR support
  - `@inertiajs/vite` — Inertia.js SSR support
  - `@vitejs/plugin-react` — React compilation with React Compiler plugin
  - `@tailwindcss/vite` — Tailwind CSS v4 processing
  - `@laravel/vite-plugin-wayfinder` — Route/type generation
- **Type Checking**: TypeScript strict mode (`tsc --noEmit`)
- **Linting**: ESLint v9 with TypeScript support
- **Formatting**: Prettier with Tailwind plugin

## Key Build Commands

### Development
```bash
# Start both backend and frontend dev servers
composer dev          # Runs `php artisan dev`
npm run dev           # Starts Vite dev server only
```

### Production Build
```bash
npm run build         # Vite production build (CSS + JS)
npm run build:ssr     # Dual build for SSR support
```

### Quality Checks
```bash
# Full CI check suite (lint + format + types + tests)
composer ci:check

# Individual checks
composer lint         # Run Pint formatter
composer lint:check   # Check PHP code style
composer types:check  # Run PHPStan
npm run lint          # ESLint with auto-fix
npm run lint:check    # ESLint check only
npm run format        # Prettier format
npm run format:check  # Prettier check only
npm run types:check   # TypeScript type checking
```

### Testing
```bash
composer test         # Full test suite with config clear, lint, types, PHPUnit
php artisan test      # Run Pest tests directly
```

### Project Setup
```bash
composer setup        # Install deps, create .env, generate key, migrate, build assets
```

## CI/CD Pipeline (GitHub Actions)

Two workflows run on push/PR to `develop`, `main`, `master`, `workos`:

### `tests.yml` — Test Matrix
- Runs on Ubuntu with PHP versions **8.3, 8.4, 8.5** (matrix strategy)
- Node.js 22 fixed version
- Steps: checkout → PHP setup → Node setup → npm install → composer install → env setup → key generation → asset build → type analysis → test execution
- Uses Xdebug for coverage

### `lint.yml` — Code Quality
- Runs on PHP 8.4 only
- Installs both Composer and npm dependencies
- Executes: Pint (PHP), Prettier (frontend format), ESLint (frontend lint)
- Has commented-out auto-commit step for style fixes

## Architecture Decisions

1. **No Docker in CI**: Tests run directly on GitHub-hosted runners without containerization. SQLite in-memory database is used for testing (configured in `phpunit.xml`).

2. **Vite-Centric Asset Pipeline**: All frontend assets flow through Vite with Laravel plugin integration. The `public/build/` directory holds compiled assets.

3. **SSR Support Available**: The `build:ssr` script indicates server-side rendering capability, though not exercised in CI.

4. **Strict TypeScript**: `tsconfig.json` enforces strict mode, noEmit (type-checking only), ESNext modules, and path aliases (`@/*` → `./resources/js/*`).

5. **React Compiler Enabled**: Babel plugin `babel-plugin-react-compiler` is configured in Vite for automatic memoization optimization.

6. **Wayfinder Integration**: Laravel Wayfinder generates TypeScript route helpers from PHP routes, bridging backend routing to frontend type safety.

## Developer Conventions

- **Run `composer ci:check` before committing** — this executes the full quality gate (frontend lint/format/types + backend tests)
- **Use `composer setup` for fresh installs** — handles environment file creation, key generation, migrations, and asset compilation
- **PHP 8.3+ required** — minimum version enforced in `composer.json`
- **Node 22 recommended** — matches CI configuration
- **No Makefile** — all build orchestration happens through Composer scripts and npm scripts
- **Testing uses Pest** — not plain PHPUnit; tests live in `tests/Feature` and `tests/Unit`
- **Database migrations use SQLite in tests** — configured via `phpunit.xml` environment variables
