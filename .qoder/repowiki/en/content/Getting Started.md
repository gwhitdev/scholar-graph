# Getting Started

<cite>
**Referenced Files in This Document**
- [composer.json](file://composer.json)
- [package.json](file://package.json)
- [.env.example](file://.env.example)
- [config/app.php](file://config/app.php)
- [config/database.php](file://config/database.php)
- [database/migrations/0001_01_01_000000_create_users_table.php](file://database/migrations/0001_01_01_000000_create_users_table.php)
- [database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php](file://database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php)
- [vite.config.ts](file://vite.config.ts)
- [bootstrap/app.php](file://bootstrap/app.php)
- [routes/web.php](file://routes/web.php)
- [AGENTS.md](file://AGENTS.md)
- [hackathon/HACKATHON_SPEC.md](file://hackathon/HACKATHON_SPEC.md)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [System Requirements](#system-requirements)
3. [Environment Setup](#environment-setup)
4. [Database Configuration](#database-configuration)
5. [Initial Project Setup](#initial-project-setup)
6. [Development Workflow](#development-workflow)
7. [Common Setup Issues](#common-setup-issues)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Next Steps](#next-steps)
10. [Appendices](#appendices)

## Introduction
This guide walks you through installing and running ScholarGraph from the ground up. It covers prerequisites, environment configuration, database setup, dependency installation, and launching the development servers. By the end, you will have a working local environment capable of developing the persistent, queryable memory features described in the hackathon scope.

## System Requirements
- PHP runtime: 8.3 or higher
- Node.js: latest LTS recommended
- PostgreSQL: required for production-like environments and advanced features
- Git: for cloning and version control

These requirements align with the project’s PHP version constraint and the PostgreSQL driver configuration present in the repository.

**Section sources**
- [composer.json:11-19](file://composer.json#L11-L19)
- [config/database.php:87-100](file://config/database.php#L87-L100)

## Environment Setup
Follow these steps to prepare your environment:

1. Clone the repository and navigate into the project directory.
2. Copy the example environment file to create your local configuration:
   - Use the Composer post-create-project script or manually copy `.env.example` to `.env`.
3. Generate the application key:
   - Use the Composer script that runs after environment creation.
4. Configure your environment variables:
   - Set the application name, environment, and URL.
   - Configure database connection defaults and optional overrides.

Key environment variables you will likely customize:
- Application: APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL
- Database: DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- Sessions, queues, cache, and mailers as needed

Notes:
- The default database connection is SQLite, which is convenient for local development.
- PostgreSQL is supported and configured; you can switch DB_CONNECTION to pgsql and set credentials accordingly.

**Section sources**
- [composer.json:91-95](file://composer.json#L91-L95)
- [composer.json:46-53](file://composer.json#L46-L53)
- [.env.example:1-66](file://.env.example#L1-L66)
- [config/app.php:16,29,55,81,83,85:16-16](file://config/app.php#L16-L16)
- [config/database.php:20](file://config/database.php#L20)

## Database Configuration
The application supports multiple database drivers. The default is SQLite, but PostgreSQL is fully supported and recommended for production-like workflows.

Default configuration highlights:
- Default connection: sqlite
- Available drivers: sqlite, mysql, mariadb, pgsql, sqlsrv
- PostgreSQL defaults host/port/database/username/password are provided for easy switching

Important settings to review:
- config/database.php defines connection arrays for each driver
- config/app.php reads APP_KEY and APP_URL from environment
- The users table migration and related sessions/password reset tables are included

To use PostgreSQL:
1. Set DB_CONNECTION=pgsql in your .env
2. Provide DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
3. Run database migrations to create tables

SQLite (default):
- No additional setup required; the connection uses a local database file

**Section sources**
- [config/database.php:20,33-100:20-100](file://config/database.php#L20-L100)
- [database/migrations/0001_01_01_000000_create_users_table.php:14-37](file://database/migrations/0001_01_01_000000_create_users_table.php#L14-L37)
- [database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php:14-18](file://database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php#L14-L18)

## Initial Project Setup
Complete these steps to bootstrap the project:

1. Install PHP dependencies:
   - Run the Composer setup script which installs dependencies and prepares the environment.

2. Prepare the environment:
   - Copy .env.example to .env if missing.
   - Generate the application key.

3. Run database migrations:
   - Apply the schema to your chosen database (SQLite by default, or PostgreSQL if configured).

4. Install JavaScript dependencies:
   - Install Node.js packages via npm or pnpm.

5. Build assets:
   - Run the Vite build script to compile frontend assets.

6. Launch development servers:
   - Start the Laravel development server and the Vite dev server concurrently.

The Composer scripts orchestrate these tasks. They also handle vendor publishing and asset updates when packages change.

**Section sources**
- [composer.json:46-53](file://composer.json#L46-L53)
- [composer.json:84-98](file://composer.json#L84-L98)
- [package.json:5-14](file://package.json#L5-L14)

## Development Workflow
With the project bootstrapped, you can develop features iteratively:

- Backend:
  - Use Artisan commands for common tasks (routes, config inspection, tests).
  - The application registers routes and middleware in bootstrap/app.php and routes/web.php.

- Frontend:
  - Vite is configured with React, Inertia, Tailwind, and Laravel-specific plugins.
  - Use npm scripts for dev/build/format/lint/types checking.

- Routing and middleware:
  - Routes define the home and dashboard pages, protected by auth and email verification.
  - Middleware handles appearance preferences, Inertia requests, and cookie encryption.

- Testing:
  - The project uses Pest for testing. Run tests via Composer scripts or Artisan.

- Documentation and guidelines:
  - Refer to AGENTS.md for development conventions, tools, and best practices.
  - The hackathon spec outlines the scope and priorities for building persistent memory features.

**Section sources**
- [bootstrap/app.php:11-30](file://bootstrap/app.php#L11-L30)
- [routes/web.php:5-9](file://routes/web.php#L5-L9)
- [vite.config.ts:1-32](file://vite.config.ts#L1-L32)
- [AGENTS.md:90-101](file://AGENTS.md#L90-L101)
- [AGENTS.md:175-177](file://AGENTS.md#L175-L177)

## Common Setup Issues
Below are frequent problems and their resolutions:

- Missing APP_KEY
  - Symptom: Application fails to start or encrypt cookies.
  - Fix: Run the Composer setup script or generate a fresh key.

- Database connection errors
  - Symptom: Cannot connect to SQLite or PostgreSQL.
  - Fix: Verify DB_CONNECTION and credentials in .env; ensure PostgreSQL service is running if using pgsql.

- Vite manifest errors
  - Symptom: “Unable to locate file in Vite manifest”.
  - Fix: Rebuild assets with npm run build or start Vite dev server.

- Frontend changes not appearing
  - Symptom: UI does not reflect code changes.
  - Fix: Ensure Vite dev server is running or rebuild assets.

- PHP version mismatch
  - Symptom: Composer install fails due to PHP version.
  - Fix: Upgrade to PHP 8.3+ as required by the project.

- PostgreSQL-specific issues
  - Symptom: Connection refused or invalid credentials.
  - Fix: Confirm host/port/database/username/password; enable SSL if required by your environment.

**Section sources**
- [composer.json:46-53](file://composer.json#L46-L53)
- [config/database.php:20,87-100](file://config/database.php#L20,L87-L100)
- [AGENTS.md:175-177](file://AGENTS.md#L175-L177)
- [composer.json:11-19](file://composer.json#L11-L19)

## Troubleshooting Guide
Use these steps to diagnose and resolve issues:

- Verify environment variables
  - Confirm APP_ENV, APP_URL, DB_CONNECTION, and database credentials.

- Check database connectivity
  - For SQLite: ensure the database file exists and is writable.
  - For PostgreSQL: test connection externally and verify user privileges.

- Rebuild frontend assets
  - Clear caches and re-run npm install and build.

- Review middleware and routes
  - Ensure routes are registered and middleware stacks are applied as expected.

- Inspect configuration
  - Use Artisan to inspect configuration values and verify defaults.

- Consult project guidelines
  - Refer to AGENTS.md for tooling and workflow recommendations.

**Section sources**
- [.env.example:1-66](file://.env.example#L1-L66)
- [config/database.php:20,33-100](file://config/database.php#L20,L33-L100)
- [bootstrap/app.php:17-25](file://bootstrap/app.php#L17-L25)
- [routes/web.php:5-9](file://routes/web.php#L5-L9)
- [AGENTS.md:90-101](file://AGENTS.md#L90-L101)

## Next Steps
Once your environment is ready:

- Explore the hackathon scope to understand the core memory features to implement:
  - Projects, papers, syntheses, and chat messages
  - Retrieval without a vector store for simplicity
  - Qwen integration for synthesis and chat

- Implement authentication and project CRUD as the foundation
- Integrate Semantic Scholar search and paper ingestion
- Build the synthesis endpoint that pulls project context and calls the LLM
- Validate cross-session recall with a simple demo flow

**Section sources**
- [hackathon/HACKATHON_SPEC.md:39-75](file://hackathon/HACKATHON_SPEC.md#L39-L75)
- [hackathon/HACKATHON_SPEC.md:106-117](file://hackathon/HACKATHON_SPEC.md#L106-L117)

## Appendices

### Appendix A: Quick Commands Reference
- Install PHP dependencies: composer install
- Prepare environment and run migrations: composer setup
- Generate app key: php artisan key:generate
- Run migrations: php artisan migrate
- Install JS dependencies: npm install
- Build assets: npm run build
- Start dev servers: composer dev (runs Laravel dev and Vite)

**Section sources**
- [composer.json:46-57](file://composer.json#L46-L57)
- [package.json:5-14](file://package.json#L5-L14)

### Appendix B: Database Schema Overview
- Users table with timestamps and related session/password reset tables
- Optional two-factor authentication columns added via migration

**Section sources**
- [database/migrations/0001_01_01_000000_create_users_table.php:14-37](file://database/migrations/0001_01_01_000000_create_users_table.php#L14-L37)
- [database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php:14-18](file://database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php#L14-L18)