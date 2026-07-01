## Configuration Architecture

This repository uses **Laravel's standard configuration system**, which follows a well-established pattern of layered configuration loading:

### Configuration Loading Order

1. **Environment variables** (`.env` file) — highest priority, environment-specific secrets and settings
2. **Config files** (`config/*.php`) — default values with `env()` fallbacks
3. **Framework defaults** — built-in Laravel defaults when neither env nor config specifies a value

### Core Mechanism

The application bootstraps via `bootstrap/app.php` using `Application::configure()`, which automatically loads all PHP files from the `config/` directory. Each config file returns an associative array where values are resolved using Laravel's `env()` helper function.

The `env()` helper follows this pattern:
```php
'key' => env('ENV_VAR_NAME', 'default_value')
```

This means:
- If the environment variable exists, use it
- Otherwise, fall back to the hardcoded default

## Key Configuration Files

### Environment Template (`.env.example`)
Defines all required and optional environment variables across categories:
- **Application**: `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`
- **Localization**: `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`
- **Logging**: `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`
- **Database**: `DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- **Session**: `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`
- **Cache/Queue**: `CACHE_STORE`, `QUEUE_CONNECTION`
- **Mail**: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, credentials
- **AWS/S3**: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`
- **Vite/Frontend**: `VITE_APP_NAME` (exposed to client-side)

### Config Directory Structure (`config/`)

| File | Purpose |
|------|---------|
| `app.php` | Core app identity, debug mode, locale, encryption key, maintenance mode |
| `auth.php` | Authentication guards, password reset settings |
| `cache.php` | Cache stores (array, database, file, redis, memcached, dynamodb) |
| `database.php` | Database connections (sqlite, mysql, pgsql, sqlsrv), Redis config |
| `filesystems.php` | Storage disks (local, public, S3) |
| `fortify.php` | Laravel Fortify auth features (registration, 2FA, passkeys, rate limiting) |
| `inertia.php` | Inertia.js SSR settings, page component paths, testing config |
| `logging.php` | Log channels, stacks, deprecation handling |
| `mail.php` | Mailer transports, SMTP settings, mail-from defaults |
| `queue.php` | Queue connections, job retry settings |
| `services.php` | Third-party service credentials |
| `session.php` | Session driver, lifetime, cookie settings, security options |

### Bootstrap Configuration

- **`bootstrap/app.php`**: Application entry point that configures routing, middleware pipeline, and exception handling. Uses the modern `Application::configure()` API introduced in Laravel 11.
- **`bootstrap/providers.php`**: Lists service providers to load (`AppServiceProvider`, `FortifyServiceProvider`). This replaces the older `config/app.php` providers array.

### Frontend Build Configuration

- **`vite.config.ts`**: Vite build configuration using plugins for Laravel integration (`laravel-vite-plugin`), Inertia.js (`@inertiajs/vite`), React (`@vitejs/plugin-react`), Tailwind CSS (`@tailwindcss/vite`), and Laravel Wayfinder route generation (`@laravel/vite-plugin-wayfinder`).

## Design Patterns and Conventions

### 1. Environment Variable Naming Convention

All environment variables follow the `APP_*`, `DB_*`, `SESSION_*`, `CACHE_*`, `MAIL_*`, `REDIS_*`, `AWS_*` prefix patterns. This provides clear namespacing and prevents collisions.

### 2. Secure Defaults

- `APP_DEBUG=false` by default (production-safe)
- `SESSION_ENCRYPT=false` explicitly configurable
- `SESSION_HTTP_ONLY=true` by default
- `SESSION_SAME_SITE='lax'` for CSRF protection
- Password rules enforce 12+ characters with mixed case, numbers, symbols in production (configured in `AppServiceProvider::configureDefaults()`)

### 3. Feature-Based Configuration

`config/fortify.php` uses a feature-flag pattern via the `Features` class:
```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
    Features::passkeys(['confirmPassword' => true]),
]
```

This allows enabling/disabling authentication features without code changes.

### 4. Cross-Config References

Configs reference each other using `config()` helper:
- `config/fortify.php` references `config('app.url')` and `config('app.key')` for passkey relying party configuration
- `config/cache.php` derives cache prefix from `APP_NAME`
- `config/session.php` derives session cookie name from `APP_NAME`

### 5. Production-Safe Behaviors in Service Provider

`AppServiceProvider::boot()` configures runtime behaviors based on environment:
- Uses `CarbonImmutable` for all date operations
- Prohibits destructive database commands in production
- Enforces strong password rules only in production

### 6. Cookie Encryption Exceptions

In `bootstrap/app.php`, specific cookies are excluded from encryption:
```php
$middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
```
This allows client-side JavaScript to read these UI state cookies.

## Rules for Developers

### Adding New Configuration

1. **Add env var to `.env.example`** with a sensible default or empty placeholder for secrets
2. **Create or update a config file** in `config/` using the `env()` helper pattern
3. **Never commit `.env`** — it is gitignored; only `.env.example` is tracked
4. **Use descriptive prefixes** for new environment variables (e.g., `MYFEATURE_API_KEY`)

### Accessing Configuration

- In PHP: `config('app.name')` or `env('APP_NAME')` (prefer `config()` in application code)
- In TypeScript/React: Only `VITE_*` prefixed env vars are exposed to the frontend via Vite
- Avoid calling `env()` outside config files — use `config()` after bootstrapping

### Security Guidelines

- Never hardcode secrets in config files — always use `env()`
- Use `APP_KEY` for encryption; rotate via `php artisan key:generate`
- Keep `APP_DEBUG=false` in production
- Review `SESSION_SECURE_COOKIE` and `SESSION_SAME_SITE` for HTTPS deployments
- Passkey configuration in `fortify.php` dynamically derives `relying_party_id` from `APP_URL` — ensure `APP_URL` matches your deployment domain

### Testing Configuration

- Tests use `database` as the default connection for sessions, cache, and queues (defined in `.env.example`)
- The `database.sqlite` file in `database/` is used for local development
- Config assertions in tests should use `config()->set()` to override values temporarily
