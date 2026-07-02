# Phase 10 — Workspace redesign (2a "Field Notes")

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD. Branch: `feature/workspace-redesign-2a`.

## Goal (plain English)

Rebuild the project workspace screen (`resources/js/pages/projects/show.tsx`) from the flat two-column layout
into the **"Field Notes" (2a)** design: a four-region workspace that makes the **Find → Library → Discuss**
flow explicit. Regions: a slim **icon rail**, a **project sidebar** (workflow stepper + collections + "Edit
prompt"), a **library column** (command bar + paper cards), and a docked **discussion panel**. This is a
visual/IA overhaul of one screen plus the prop plumbing to keep runtime values (assistant model name, search
corpus size) out of the markup. No backend data model changes.

The source of truth for the visual design is [`ScholarGraph Redesign.dc.html`](./ScholarGraph%20Redesign.dc.html) —
frame **2a**. Reference it while building; do not copy its inline styles or `div onClick` patterns (see
Accessibility below).

**Depends on:** Phase 3 (workflow stepper + per-paper status come from the `project_papers` pivot) and
Phase 11 (the sidebar renders real collections). Do **11 before 10**.

## Scope guardrail (read this first)

This phase re-skins **`projects/show` only**. The icon rail is *workspace chrome*, not a replacement for the
global `app-sidebar.tsx`. Every other screen (dashboard, settings, and the Admin/Support areas from Phases 4/6)
keeps the existing `AppLayout` sidebar. This keeps the redesign from colliding with phases that add global nav
items. Do **not** touch `app-sidebar.tsx` or the global layout.

## Questions to confirm before you start

1. **Term for the middle workflow step — "Library" or "Collect"?** The mock's header says "Collect" but its
   sidebar says "Library", and the rest of the app says "Library". *Recommended: standardise on "Library"
   everywhere.* Confirm.
2. **Assistant model label** — what user-facing string should the discussion header show? The real model comes
   from `config('services.openrouter.model')` (e.g. `qwen-plus`). *Recommended: add a friendly
   `services.openrouter.label` (default a humanised version of the model id) and display that.* Confirm the
   label text.
3. **Dark mode** — reuse the existing app-wide `use-appearance` toggle (light/dark/system), or give the
   workspace its own local toggle like the mock? *Recommended: reuse the global `use-appearance` hook; do not
   add a second, competing theme state.* Confirm.
4. **"Add via DOI"** — build the DOI-lookup add action in this phase, or ship the button disabled for a later
   phase? *Recommended: build it — `OpenAlexSearchService` already resolves works, and it's a small action.*
   Confirm.

## What already exists

- `projects/show.tsx` renders `PaperSearch`, `PaperCard`, `ChatThread`, `ChatInput`, `PromptDrawer`.
- `ProjectController::show` returns `project`, `papers` (with `enrichment` and, after Phase 3, `pivot.status`),
  `chatMessages` (with `synthesis`), `syntheses`, and the global prompts.
- `ChatThread` + `ChatInput` are complete and reusable — the discussion panel wraps them, it does not
  reimplement chat.
- `PaperCard` owns the enrich/poll/delete logic (`useHttp`, `usePoll`) — restyle it, keep the logic.
- `PaperSearch` owns the debounced OpenAlex fetch — fold its logic into the command bar, keep the behaviour.
- `use-appearance` hook drives light/dark; the UI kit in `resources/js/components/ui` is the styling vocabulary.
- Layout is chosen in `resources/js/app.tsx` by page name; `projects/show` currently gets the default
  `AppLayout`.

## Step 1 — Prop plumbing (TDD, backend) — inject variables, don't hardcode

Write `tests/Feature/Projects/WorkspacePropsTest.php` **first**:
- `it('passes the assistant label to the workspace')` — `show` Inertia response `has('assistant.label')` and
  `has('assistant.model')`.
- `it('passes the search corpus label')` — response `has('openalex.corpusLabel')`.
- `it('passes collections to the workspace')` — response `has('collections')` (from Phase 11).

Then:
- Add `config/services.php` → `openrouter.label` (`env('QWEN_MODEL_LABEL', 'Qwen Plus')` or humanise the model
  id) and, optionally, `openrouter.provider`.
- Add `config/services.php` → `openalex.corpus_label` (`env('OPENALEX_CORPUS_LABEL', '250M+ papers')`).
- In `ProjectController::show`, add to the Inertia props:
  ```php
  'assistant' => [
      'model' => config('services.openrouter.model'),
      'label' => config('services.openrouter.label'),
  ],
  'openalex' => ['corpusLabel' => config('services.openalex.corpus_label')],
  ```
- The app name / logo "S" comes from the shared `name` prop (`config('app.name')`) — never a literal.

> Rule for this whole phase: any string that is a runtime/config value (model name, provider, corpus size,
> app name) is a **prop**, not text in JSX. The mock's `"GPT-4o"`, `"250M+ papers"`, `"eucalyptus"`,
> `"ScholarGraph"` are placeholders, not final copy.

## Step 2 — Theme tokens

Map frame 2a's light/dark eucalyptus palette to **CSS custom properties / Tailwind theme tokens in one place**,
driven by the existing `use-appearance` hook. Do not inline `--accent` etc. on individual elements the way the
mock does. Reuse existing token names where they exist; add a scoped accent token if needed. `prefers-reduced-motion`
must be respected for any transitions.

## Step 3 — Workspace shell + components (TDD, frontend)

Opt `projects/show` out of the global sidebar layout so the four-region layout isn't nested inside it. In
`resources/js/app.tsx`, route `projects/show` to a new workspace layout (or set the page's `layout` to the new
shell). Keep all other pages on `AppLayout`.

Build these as **reusable components** (ground rule 4), each with a render/interaction test in
`tests/Browser/` or a component test as appropriate. Suggested location: `resources/js/components/workspace/`.

- `WorkspaceShell` — the four-region grid; owns min-height/flex, not fixed `912px` heights.
- `IconRail` — logo (`name` prop), nav icons (Projects, etc.), user avatar/menu. Reuse `nav-user`/user-menu
  content; **do not** duplicate `app-sidebar` logic wholesale.
- `ProjectSidebar` — breadcrumb, "Find papers" CTA, `WorkflowSteps`, `CollectionsList` (from Phase 11),
  footer "Edit prompt" that opens the existing `PromptDrawer`.
- `WorkflowSteps` — Find / Library / Discuss, state derived from real paper counts and `pivot.status`
  (not hardcoded). Current step is programmatically indicated (not colour-only).
- `CommandBar` — search field (folds in `PaperSearch`'s debounced fetch + "Added ✓" logic from Phase 3),
  "Add via DOI", and the dark-mode control wired to `use-appearance`. `corpusLabel` is a prop.
- `LibraryHeader` — "N papers collected" (from `papers.length`), sort control.
- `DiscussionPanel` — header shows `{assistant.label}` (prop); **wraps the existing `ChatThread` + `ChatInput`**.

## Step 4 — Restyle existing pieces (behaviour unchanged)

- Restyle `PaperCard` to the 2a article treatment (tag chip, TL;DR row, citation/DOI footer, "Read summary")
  while keeping its enrich/poll/delete logic and existing tests green. Visual change only.
- Confirm `PaperSearch` behaviour is preserved after folding into `CommandBar` (debounce, abort, min-length,
  "Added ✓"). Its existing tests must stay green.

## Step 5 — "Add via DOI" (if Q4 = build)

TDD a small `AddPaperByDoiAction` that resolves a DOI through `OpenAlexSearchService` (works support a
`doi:` lookup) and reuses `SavePaperToProjectAction` to attach it. Wire it to the command-bar control.
Write the feature test first (valid DOI attaches a paper; unknown DOI surfaces a friendly error).

## Step 6 — Accessibility (WCAG AA — ground rule 6)

- Every control is a real `<button>` / `<Link>` — **no `div onClick`** (the mock's pattern is not carried over).
- Icon-only controls have `aria-label`; the search input and DOI input have associated labels.
- Visible focus states; keyboard-reachable rail, stepper, sort, and toggle.
- Verify eucalyptus muted-on-panel text meets **4.5:1**; adjust the token if it fails. Never rely on colour
  alone for workflow state or collection identity.
- Respect `prefers-reduced-motion`.

Verify the light/dark toggle and the four regions end-to-end (use the `verify` skill / a browser test) before
opening the PR.

## Step 7 — Finish

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
php artisan wayfinder:generate        # routes changed only if Q4 added a DOI route
```

## Done when

- [ ] `projects/show` renders the four-region 2a workspace; all other screens keep the global sidebar.
- [ ] Assistant label, corpus label, and app name are **props/config**, not literals in JSX.
- [ ] Workflow stepper and paper status reflect real pivot data; collections render from Phase 11.
- [ ] `PaperCard` enrich/poll/delete and `PaperSearch` debounce/"Added ✓" behaviour unchanged (tests green).
- [ ] Discussion panel reuses `ChatThread` + `ChatInput`; header shows the injected assistant label.
- [ ] (If Q4) Add-via-DOI attaches a paper via OpenAlex.
- [ ] No `div onClick`; keyboard-navigable; contrast ≥ 4.5:1; reduced-motion respected.
- [ ] Full suite green, Pint clean, Wayfinder regenerated (if routes changed), PR opened noting Q1–Q4 answers.
