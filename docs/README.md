# ScholarGraph — Implementation Docs

This folder contains **per-phase implementation plans** for the post-MVP product roadmap.
Each phase is a self-contained task you can hand to any model (including cheaper ones) and have it
implemented end-to-end using strict TDD.

## How to use these docs

1. **Always read [`00-conventions.md`](./00-conventions.md) first.** It contains the ground rules,
   the exact TDD loop, and the shell commands you will use in every phase. It is short. Do not skip it.
2. Open the phase file you have been assigned. Read it **fully** before writing any code.
3. Each phase file has a **"Questions to confirm before you start"** section near the top. You MUST ask
   the user those questions and wait for answers before writing code. Do not guess.
4. Work top-to-bottom through the phase. Write tests first, watch them fail, then implement.
5. Stop at the "Done when" checklist. Everything on it must be true before you open a PR.

## Phase index & order

Do them in this order unless the user says otherwise. Later phases depend on earlier ones.

| Phase | File | Depends on | One-line goal |
|-------|------|-----------|----------------|
| 1 | [phase-1-ownership-hardening.md](./phase-1-ownership-hardening.md) | — | No user can touch another user's data |
| 2 | [phase-2-usage-logging.md](./phase-2-usage-logging.md) | 1 | Log every LLM/API call with tokens, cost, duration |
| 3 | [phase-3-shared-paper-library.md](./phase-3-shared-paper-library.md) | 1 | Saved papers become shared, deduplicated records |
| 4 | [phase-4-admin-portal.md](./phase-4-admin-portal.md) | 2 | Admin-only metrics dashboard |
| 5 | [phase-5-monetisation.md](./phase-5-monetisation.md) | 2, 4 | Free tier + credits + licence keys |
| 6 | [phase-6-support-tickets.md](./phase-6-support-tickets.md) | 1, 4 | Users file support tickets; admins reply |
| 7 | [phase-7-cms.md](./phase-7-cms.md) | 4 | Admin-managed public pages |
| 8 | [phase-8-help-guide.md](./phase-8-help-guide.md) | 7 | In-app help centre for logged-in users |
| 9 | [phase-9-pwa.md](./phase-9-pwa.md) | — | Installable PWA + push notifications |
| 11 | [phase-11-collections.md](./phase-11-collections.md) | 1, 3 | Group papers into colour-tagged collections |
| 10 | [phase-10-workspace-redesign.md](./phase-10-workspace-redesign.md) | 3, 11 | Rebuild the project workspace as the "Field Notes" design |

> **Phases 10 & 11 are the UI redesign, added after the original 1–9 roadmap.** Both depend on Phase 3's
> shared-paper pivot. Build **11 before 10** (listed in that order above): Phase 11 adds the collections data
> model so Phase 10's redesigned sidebar renders real collections instead of a stub. Phase 10 re-skins the
> `projects/show` workspace only and leaves the global sidebar (and Phases 4–9's nav additions) untouched.

## Rule of thumb

If a phase file and `00-conventions.md` ever seem to conflict, `00-conventions.md` wins.
If anything is unclear or missing, **stop and ask the user** — never invent architecture.
