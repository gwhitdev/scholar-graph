## Overview

This Laravel React Starter Kit uses a dual package manager approach: **Composer** for PHP dependencies and **npm** for JavaScript/TypeScript dependencies. Both ecosystems employ lockfiles for deterministic builds and rely on public registries (Packagist and npm) without private registry configuration.

## PHP Dependency Management (Composer)

### Manifest Files
- **`composer.json`**: Declares all PHP dependencies with semantic versioning constraints
- **`composer.lock`**: Locks exact dependency versions for reproducible installs (11,280 lines, ~50+ transitive dependencies)

### Key Configuration Decisions
- **PHP version constraint**: `^8.3` — requires PHP 8.3+
- **Autoloading**: PSR-4 standard with `App\` → `app/`, `Database\Factories\` → `database/factories/`, `Database\Seeders\` → `database/seeders/`
- **Optimization**: `optimize-autoloader: true` for production performance
- **Install preference**: `preferred-install: dist` — downloads distribution archives rather than cloning git repos for faster installs
- **Plugin allowlist**: Only `pestphp/pest-plugin` and `php-http/discovery` plugins are permitted, preventing arbitrary plugin execution
- **Stability policy**: `minimum-stability: stable` with `prefer-stable: true` — only stable releases accepted

### Dependency Scripts
The `composer.json` defines lifecycle scripts that automate project setup and maintenance:
- **`setup`**: Full project initialization — installs deps, creates `.env`, generates app key, runs migrations, installs npm packages, builds assets
- **`post-update-cmd`**: Publishes Laravel assets and runs `boost:update` after dependency updates
- **`post-autoload-dump`**: Discovers Laravel packages automatically
- **`test`**, **`lint`**, **`types:check`**: Integrated quality gates via Composer scripts

### Vendoring Strategy
- **`vendor/` directory is gitignored** — dependencies are NOT committed to version control
- Dependencies are fetched from Packagist at install time via `composer install`

## JavaScript Dependency Management (npm)

### Manifest Files
- **`package.json`**: Declares JS/TS dependencies with caret (`^`) version ranges
- **`package-lock.json`**: Lockfile v3 format ensuring deterministic node_modules resolution (7,730 lines)

### Key Configuration Decisions
- **Module system**: ESM (`"type": "module"`)
- **`.npmrc`**: `ignore-scripts=true` — disables postinstall/preinstall scripts for security, preventing arbitrary code execution during dependency installation
- **pnpm workspace**: A `pnpm-workspace.yaml` exists with the root as the only workspace member and `@inertiajs/core` hoisted publicly, suggesting pnpm compatibility but npm is the primary package manager (evidenced by `package-lock.json`)

### Dependency Categories
- **Production dependencies**: React 19.2, Inertia.js 3.0, Radix UI components, Tailwind CSS 4.0, Vite 8.0, Laravel-specific plugins (`@laravel/passkeys`, `@laravel/vite-plugin-wayfinder`)
- **Dev dependencies**: ESLint 9.x with TypeScript support, Prettier, type definitions
- **Optional dependencies**: Platform-specific native binaries for Rollup, Tailwind Oxide, and LightningCSS (Linux x64 GNU and Windows x64 MSVC variants)

### Build Toolchain Integration
Dependencies are tightly coupled to the Vite build pipeline:
- `laravel-vite-plugin` bridges Laravel and Vite
- `@inertiajs/vite` handles SSR builds
- `@tailwindcss/vite` integrates Tailwind CSS 4.0's new Vite plugin architecture
- `@laravel/vite-plugin-wayfinder` generates type-safe route helpers

## Automated Dependency Updates

### Dependabot Configuration (`.github/dependabot.yml`)
- **Scope**: Only GitHub Actions workflows are monitored for automated updates
- **Schedule**: Weekly checks with 5-day cooldown between PRs
- **Grouping**: All GitHub Actions updates grouped into single PRs
- **Gap**: No Dependabot entries for `composer` or `npm` ecosystems — PHP and JS dependencies must be updated manually or via alternative tooling

## Developer Conventions

### Installation Workflow
1. Run `composer setup` for full project bootstrap (handles both PHP and JS deps)
2. Or run `composer install` + `npm install` separately
3. Lockfiles (`composer.lock`, `package-lock.json`) MUST be committed to ensure team consistency

### Version Pinning Strategy
- PHP deps use caret ranges (e.g., `^13.17` for Laravel framework) allowing minor/patch updates
- JS deps similarly use caret ranges (e.g., `^19.2.0` for React)
- Optional native deps use exact versions (e.g., `4.9.5` for Rollup platform binaries) to prevent ABI incompatibilities

### Security Considerations
- npm postinstall scripts disabled via `.npmrc`
- Composer plugin execution restricted to explicit allowlist
- No private registries configured — all deps sourced from public Packagist/npm registries
- `auth.json` is gitignored, allowing optional private registry credentials without committing secrets