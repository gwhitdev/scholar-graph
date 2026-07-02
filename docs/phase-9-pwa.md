# Phase 9 — PWA + push notifications + install button

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD where testable. Branch: `feature/pwa-notifications`.

## Goal (plain English)

Make the app **installable** (Add to Home Screen / desktop install) with an in-app **install button**, and
send **web-push notifications** for events like "AI summary ready" and "support reply received". This phase
has two parts: Part A (installable PWA — no new dependencies) and Part B (push — needs a package, approval-gated).

**Depends on:** none hard. Notifications are more useful after Phase 2 (enrichment) and Phase 6 (tickets).

## Questions to confirm before you start

1. **Manifest/service-worker approach:** (a) **hand-rolled** manifest + `sw.js` — no new dependency, or
   (b) `vite-plugin-pwa` — **⚠ NEEDS APPROVAL** (npm dependency). *Recommended: (a) hand-rolled.* Confirm.
2. **Do we want push notifications now (Part B)?** It requires a composer package
   `laravel-notification-channels/webpush` — **⚠ NEEDS APPROVAL**. *Recommended: ship Part A now, do Part B
   once approved.* Confirm whether to install the package.
3. **App icons** — do you have 192×192 and 512×512 PNG icons/branding? If not, we need placeholder icons or a
   source logo. Confirm what to use for `public/icons/`.
4. **Which events trigger a push?** Suggested: enrichment complete (`EnrichPaperJob`) and support staff reply
   (Phase 6). Confirm the initial set.

## Part A — Installable PWA (no new dependencies)

### Step A1 — Manifest
Create `public/manifest.webmanifest`:
```json
{
  "name": "ScholarGraph",
  "short_name": "ScholarGraph",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#ffffff",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/icons/icon-512-maskable.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ]
}
```
Add icons to `public/icons/` (per Q3). In the root Blade layout (`resources/views/app.blade.php`) add inside
`<head>`:
```html
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#ffffff">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
```

### Step A2 — Service worker
Create `public/sw.js` — a minimal cache-first worker for built assets with an offline fallback. Keep it simple
and versioned (bump `CACHE` string on change). Register it in `resources/js/app.tsx`:
```ts
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}
```
> Do not aggressively cache HTML/Inertia responses — cache static assets only, so app updates still land.

### Step A3 — Install button (accessible)
Create `resources/js/components/install-pwa-button.tsx`:
- Listen for the `beforeinstallprompt` event, `preventDefault()`, stash it in state.
- Render a button only when a prompt is available; on click call `deferredPrompt.prompt()` and await
  `userChoice`, then clear it.
- Hide the button when already installed (`window.matchMedia('(display-mode: standalone)').matches`).
- On iOS Safari (no `beforeinstallprompt`), show an "Add to Home Screen" hint instead of a dead button.
- Give the button an accessible label and keyboard focus styles.
Place it somewhere sensible (e.g. sidebar footer or settings). Reuse the existing `Button` UI component.

### Step A4 — Smoke test (Pest browser)
Add `tests/Browser/PwaSmokeTest.php` (Pest 4 browser testing):
- `it('exposes a web manifest link')` — visit `/`, assert the `<link rel="manifest">` is present.
- `it('renders the install button component')` where applicable.
(Service-worker registration is hard to assert headlessly; a manifest + button check is enough for CI.)

## Part B — Web push (⚠ NEEDS APPROVAL before installing the package)

Only after the user approves Q2.

### Step B1 — Package + subscriptions
- Install `laravel-notification-channels/webpush` (**ask first**). Publish + run its `push_subscriptions` migration.
- Generate VAPID keys: `php artisan webpush:vapid` → put `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`,
  `VAPID_SUBJECT` in `.env` and `.env.example`.
- Add the package's `HasPushSubscriptions` trait to `User`.

### Step B2 — Subscribe flow (frontend)
- After install/opt-in, request `Notification.permission`. If granted, get the SW registration and
  `registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: <VAPID_PUBLIC_KEY> })`.
- `POST` the subscription JSON to a new route `push.subscriptions.store` → `PushSubscriptionController@store`
  which calls `$user->updatePushSubscription(...)`.
- Extend `public/sw.js` with a `push` event listener that shows the notification, and a `notificationclick`
  listener that focuses/opens the relevant URL.

### Step B3 — Notifications
- `EnrichmentReadyNotification` (via `WebPushChannel`) dispatched from `EnrichPaperJob` on completion.
- `SupportReplyNotification` dispatched from the Phase 6 admin reply path.
- Tests: `it('stores a push subscription for the user')`; use `Notification::fake()` +
  `Notification::assertSentTo(...)` to assert dispatch on the trigger events.

## Done when

- [ ] Manifest + service worker present; app is installable; theme-color + icons set.
- [ ] Install button prompts on supported browsers, hides when installed, shows an iOS hint otherwise.
- [ ] Browser smoke test passes.
- [ ] (If approved) Users can subscribe to push; enrichment + support-reply events deliver a notification.
- [ ] No dependency added without approval. Full suite green, Pint clean, PR opened noting Q1–Q4 answers.
```
