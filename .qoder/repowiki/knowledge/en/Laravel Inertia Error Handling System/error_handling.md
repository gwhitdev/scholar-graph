## Overview

This Laravel React Starter Kit uses a layered error handling approach combining Laravel's built-in exception handling with Inertia.js page-level validation errors and toast-based flash messaging.

## Backend Exception Handling

### Centralized Configuration (`bootstrap/app.php`)

Exception handling is configured via Laravel's `withExceptions()` callback:

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
    );
});
```

This ensures API routes always receive JSON error responses, while web routes render appropriate HTML/Inertia responses.

### Validation Errors via Form Requests

Validation errors flow through Laravel Form Request classes (e.g., `ProfileUpdateRequest`, `PasswordUpdateRequest`). When validation fails:
- Laravel automatically redirects back with errors
- Inertia.js intercepts the redirect and exposes errors to the React component via the `errors` prop
- No manual try/catch needed for standard validation failures

### Documented Best Practices (`.agents/skills/laravel-best-practices/rules/error-handling.md`)

The project documents two valid exception handling approaches:
1. **Co-location on exception class** — `report()` and `render()` methods on custom exceptions
2. **Centralized in `bootstrap/app.php`** — all handlers in one place

Additional documented patterns:
- Use `ShouldntReport` interface for exceptions that should never be logged
- Throttle high-volume exceptions to prevent log flooding
- Enable `dontReportDuplicates()` to avoid duplicate logging
- Add structured context via `context()` method on custom exceptions

## Frontend Error Presentation

### Validation Error Components

Two reusable components handle form-level errors:

- **`InputError`** (`resources/js/components/input-error.tsx`) — Displays inline field-level validation messages below individual inputs. Renders nothing when no error exists.

- **`AlertError`** (`resources/js/components/alert-error.tsx`) — Displays a destructive-styled alert box with an error icon and bulleted list of multiple errors. Deduplicates errors before rendering.

### Flash Toast Notifications

Success/info/warning/error notifications use the Sonner toast library:

- **`useFlashToast` hook** (`resources/js/hooks/use-flash-toast.ts`) — Listens for Inertia `flash` events and triggers toast notifications based on the payload type
- **`Toaster` component** (`resources/js/components/ui/sonner.tsx`) — Renders the Sonner toast container with theme-aware styling
- **`FlashToast` type** (`resources/js/types/ui.ts`) — Defines the toast payload structure: `{ type: 'success' | 'info' | 'warning' | 'error', message: string }`

Controllers trigger flash toasts via:
```php
Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);
```

### Inertia Form Error Handling

React pages use Inertia's `<Form>` component which provides `errors` and `processing` state to its render function:

```tsx
<Form {...store.form()}>
    {({ processing, errors }) => (
        <>
            <InputError message={errors.email} />
            <InputError message={errors.password} />
        </>
    )}
</Form>
```

For multi-field error scenarios (e.g., two-factor authentication), `AlertError` displays all errors at once.

## Architecture Summary

| Layer | Mechanism | Purpose |
|-------|-----------|---------|
| Backend validation | Form Request classes | Automatic validation with redirect-back errors |
| Backend exceptions | `bootstrap/app.php` configuration | Centralized exception reporting/rendering |
| Inertia bridge | `HandleInertiaRequests` middleware | Shares auth/user data; Inertia auto-propagates validation errors |
| Frontend field errors | `InputError` component | Inline per-field validation messages |
| Frontend block errors | `AlertError` component | Multi-error alert display |
| Frontend notifications | Sonner + `useFlashToast` | Toast notifications for success/info/warning/error |

## Developer Conventions

1. **Use Form Requests for validation** — Never validate directly in controllers; use dedicated Form Request classes
2. **Display field errors with `InputError`** — Place below each form input that can fail validation
3. **Use `AlertError` for grouped errors** — When multiple related errors exist (e.g., 2FA setup), show them together
4. **Trigger toasts via `Inertia::flash()`** — Use for user-facing success/info messages after mutations
5. **Follow documented exception patterns** — Either co-locate `report()`/`render()` on exception classes or centralize in `bootstrap/app.php`, but stay consistent
6. **API routes get JSON errors** — The `shouldRenderJsonWhen` config ensures API consumers always receive structured JSON error responses