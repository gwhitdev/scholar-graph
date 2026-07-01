## Overview

This Laravel application uses **Monolog** as its underlying logging framework, configured through Laravel's standard `config/logging.php` file. The logging system follows Laravel conventions with environment-driven configuration and multiple channel support.

## System Architecture

### Framework & Library
- **Primary framework**: Laravel's logging facade (`Illuminate\Support\Facades\Log`)
- **Underlying library**: Monolog PHP logging library
- **Configuration file**: `config/logging.php`
- **Environment variables**: All logging settings are driven by `.env` variables

### Default Channel Strategy
- **Default channel**: `stack` (configured via `LOG_CHANNEL=stack`)
- **Stack composition**: Controlled by `LOG_STACK` env variable, defaults to `single`
- **Log level**: Configurable via `LOG_LEVEL`, defaults to `debug`

## Available Log Channels

The configuration defines these channels:

1. **stack** — Multi-channel aggregator that writes to all channels listed in `LOG_STACK`
2. **single** — Single file logger at `storage/logs/laravel.log`
3. **daily** — Rotating daily log files (14-day retention by default via `LOG_DAILY_DAYS`)
4. **slack** — Sends critical-level logs to Slack webhook (requires `LOG_SLACK_WEBHOOK_URL`)
5. **papertrail** — Remote syslog via Papertrail service
6. **stderr** — Writes to PHP stderr stream (useful for containerized environments)
7. **syslog** — System syslog integration
8. **errorlog** — PHP error_log function
9. **null** — Discards all log messages (NullHandler)
10. **emergency** — Fallback emergency log path

### Deprecation Logging
A dedicated deprecation channel is configured separately:
- Channel: `LOG_DEPRECATIONS_CHANNEL` (defaults to `null`)
- Trace capture: `LOG_DEPRECATIONS_TRACE` (defaults to `false`)

## Key Design Decisions

### Placeholder Replacement
Most channels enable `replace_placeholders => true`, allowing contextual data injection into log messages using `{key}` syntax.

### Processors
Channels like `papertrail` and `stderr` use `PsrLogMessageProcessor` for PSR-3 compliant message formatting.

### Exception Handling Integration
The `bootstrap/app.php` configures exception rendering behavior:
```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
    );
});
```
This ensures API errors return JSON responses rather than HTML error pages.

## Developer Conventions

### Best Practices (from laravel-best-practices skill)
The repository includes documented best practices in `.agents/skills/laravel-best-practices/rules/error-handling.md`:

1. **Exception context**: Add structured data to exceptions via a `context()` method — Laravel automatically includes it in log entries
2. **ShouldntReport interface**: Implement `ShouldntReport` on exceptions that should never be logged (more discoverable than `dontReport()` lists)
3. **Throttling**: Use `throttle()` to rate-limit high-volume exceptions from flooding error tracking
4. **Duplicate suppression**: Enable `dontReportDuplicates()` to prevent the same exception from being logged multiple times
5. **JSON rendering for APIs**: Explicitly declare JSON error rendering for API routes

### Logging Patterns
Example patterns from the codebase documentation:
```php
// Structured logging with context
Log::error('Processing failed', ['id' => $podcast->id, 'error' => $exception->getMessage()]);

// Batch failure handling
->catch(fn (Batch $batch, Throwable $e) => Log::error('Batch failed'))
```

### Current Usage
No active `Log::` calls were found in the application code (`app/` directory). The logging infrastructure is fully configured but relies on Laravel's automatic exception reporting and framework-level logging.

## Environment Configuration

Key environment variables from `.env.example`:
```
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
```

## File Locations
- Configuration: `config/logging.php`
- Log storage: `storage/logs/` (gitignored)
- Bootstrap exception handling: `bootstrap/app.php`
- Best practices documentation: `.agents/skills/laravel-best-practices/rules/error-handling.md`