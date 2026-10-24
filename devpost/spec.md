---
doc: spec
status: approved
---


# DecisionLedger — Technical Spec

## How This Works, In Plain Language

DecisionLedger is a small Laravel website you run on your own machine. You write down architectural decisions (what the rule is, why it exists, and one or more explicit checkable criteria). When an AI coding agent produces PHP, you paste that code into a Check PHP page. A **checker service** — not the web page itself — looks only at that pasted text, using PHP’s built-in tokenizer (and light structure walking), and compares it against the **small documented rule set** attached to your decisions.

If it finds a clear, supported pattern match, it stores a **finding** with the decision, the exact criterion, the line numbers, and a short explanation, marked `confirmed`. If the code is fine for that rule, you get no confirmed violation (a clean/compliant result). If the code is messy, incomplete, or uses patterns the rule does not claim to understand (for example database access hidden behind `$this->repo->save()`), the checker returns `needs review` or `insufficient evidence` — never a fake “confirmed” hit. You can then add a **human review note** and mark the finding reviewed or resolved; that note does not change or erase what the checker originally said.

Data lives in a local **SQLite** file so nothing is lost when you close the browser. There are no logins, no cloud AI calls required, and no project switcher — just seeded sample decisions and fixtures so the demo works immediately.

**Why this shape:** It matches your Laravel skill, proves the trust kernel (explainable, deterministic, limited claims), and stays inside a hackathon-sized build. A bigger rule engine, full static-analysis suite, or multi-project SaaS would burn time without making the demo clearer.

## The Core Journey Through the System

PRD ref: `prd.md > The Core Journey`.

1. **Open the app** → browser hits Laravel routes → Overview controller loads decision counts, awaiting-review finding counts, and recent decisions from SQLite → Blade renders overview.
2. **Open or create a decision** → form validated → Decision (+ Criteria rows) saved in SQLite → Decision detail Blade shows statement, rationale, criteria (distinct), limitations, related findings.
3. **Check PHP** → user pastes code (+ optional notes, ignored by the checker) and picks one decision or all compatible → Check controller validates input → starts a DB transaction → creates a **new CheckRun** (previous runs and their findings are never overwritten) → **PhpArchitectureChecker** evaluates criteria → writes **Finding** rows always linked to that CheckRun and the submitted code snapshot → renders Check screen with retained code, finding list, and a **check summary** (including clean / no supported violation when appropriate).
4. **Review in place** → user posts a short note and workflow action (reviewed/resolved) → Finding’s **workflow** fields update; **checker_status** and evidence stay immutable → UI still shows original result.
5. **Demo proof** → seeders supply violating / compliant / ambiguous fixtures; Pest/PHPUnit tests assert the three outcomes before the video is recorded locally.

```
User (browser)
    │
    ▼
Blade views  ←→  Thin HTTP controllers  ←→  Eloquent models / SQLite
                      │
                      ▼
              PhpArchitectureChecker (domain)
                      │
                      ▼
              Findings (checker_status + evidence)
                      │
                      ▼
              Human review fields (separate)
```

## Stack


| Choice                                                                             | Detail                                                                         | Rationale / tradeoff                                                                                                                          |
| ---------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------- |
| PHP 8.2+ / Laravel 11.x (or current stable Laravel from `composer create-project`) | [Laravel docs](https://laravel.com/docs)                                       | Learner preference; familiar; PoC-sized. Exact minor version fixed at project create — verify early in build.                                 |
| Blade + Vite                                                                       | [Blade](https://laravel.com/docs/blade), [Vite](https://laravel.com/docs/vite) | Server-rendered UI; enough for polished desktop demo without an SPA.                                                                          |
| Tailwind CSS **if** scaffolding already includes it; else lightweight custom CSS   | [Tailwind](https://tailwindcss.com/docs)                                       | Do not spend the hackathon fighting CSS tooling. Tradeoff: custom CSS may be slightly less utility-dense.                                     |
| SQLite                                                                             | [Laravel SQLite](https://laravel.com/docs/database#sqlite-configuration)       | Local persistence, zero extra services. Tradeoff: not multi-user production DB — fine for PoC.                                                |
| Domain checker service + thin controllers                                          | App code under `app/Services` / `app/Domain`                                   | Mirrors the product’s own architecture rule; independently testable.                                                                          |
| `token_get_all` (+ small helpers)                                                  | [PHP token_get_all](https://www.php.net/manual/en/function.token-get-all.php)  | Syntax-aware enough for the narrow rule; no nikic/php-parser or PHPStan unless a late, time-boxed experiment clearly reduces false positives. |
| Pest **if** present after Laravel create; else PHPUnit                             | [Pest](https://pestphp.com/docs), [PHPUnit](https://phpunit.de/)               | Prefer existing Laravel test setup; avoid unnecessary installs.                                                                               |
| Migrations + seeders + fixture PHP files                                           | Laravel defaults                                                               | Persistent schema + realistic demo/test inputs.                                                                                               |


**Not on the critical path:** auth packages, queues, Redis, external AI SDKs, Docker-required deploys.

## Where It Runs and How Someone Tries It

- **Runtime:** Local Laravel app (`php artisan serve` or Composer `dev` script) + browser. Node only needed to build front-end assets if Vite/Tailwind is used (`npm install && npm run build` or `npm run dev`).
- **Requirements:** PHP with SQLite extension, Composer, Node/npm if assets are compiled. **No API keys.**
- **Demo recording:** Run locally; record the browser. Deployment is **out of the required path.**
- **Public repo README must document:** clone → copy `.env.example` → `composer install` → touch SQLite DB / configure `DB_CONNECTION=sqlite` → `php artisan migrate --seed` → run tests (`./vendor/bin/pest` or `php artisan test`) → `npm` build if needed → `php artisan serve` → open the printed URL. Reviewer must complete this without paid services.

## Look and Feel

Implements `prd.md > Look and Feel` / `scope.md > Inspiration & Identity`.

- Light background, slate neutrals, subtle borders, crisp system-or-stack fonts (prefer a clean sans already easy with Blade/Vite; avoid Inter-as-cliché only if an equally simple alternative is one import away — do not bikeshed fonts).
- Compact developer-tool density (Linear/GitHub-like), not marketing hero layouts.
- Status accents only: e.g. confirmed (restrained red/amber), needs review (amber), insufficient (slate), reviewed/resolved (muted green) — labels always present, not color-only.
- Code/evidence blocks get generous width; decision→finding linkage always visible as text links + criterion id/name.
- Copy tone: precise and honest (“Analyzed pasted text only”). No decorative AI branding, heavy gradients, or card farms.

## Components

### OverviewController + overview Blade

Implements `prd.md > Overview and navigation`, `prd.md > Screens and Layout > Overview`.
Loads summary counts and recent `active` decisions; links to New Decision and Check PHP.

### DecisionController + decision views

Implements `prd.md > Decisions library and detail`.
CRUD-lite: index/list, show, create, edit. Validates category ∈ fixed list; `status` always `active`. Criteria as related records. Shows evaluation limitations and related findings.

### CheckController + check Blade

Implements `prd.md > Check PHP`.
Dedicated screen: PHP textarea, decision scope (one id or “all compatible”), optional notes (stored on CheckRun, **not** passed into rule matching). Prefills decision when `?decision=` present. Shows criteria preview before/after run; retains paste on validation errors and after results.

### FindingReviewController (or nested action on CheckController)

Implements `prd.md > Findings and review`.
Accepts verification note + workflow action (`reviewed` / `resolved`). Updates only workflow columns; never mutates `checker_status` or evidence.

### PhpArchitectureChecker (domain service)

Implements `prd.md > Check PHP`, `prd.md > Findings and review`, `scope.md > The POC Boundary`.
Pure-ish input→output: pasted PHP string + list of criteria → list of finding DTOs (status, explanation, evidence line ranges, criterion id). No HTTP, no Eloquent writes inside the pure evaluate path (orchestration layer persists).

### Rule: `no_direct_eloquent_in_controller` (initial documented catalog)

**Precision requirements (reliability):**

- Token scanning **ignores comments and string literals** — query-like text inside `//`, `/* */`, `#`, or quoted strings must never produce `confirmed`.
- Correctly identify **controller class boundaries** and **method bodies** before attributing a call to a controller.
- Report **exact evidence lines** (start/end + snippet) for each supported direct static query call that triggers `confirmed`.
- Ambiguous or incomplete input must **never** produce `confirmed` merely because a token pattern was found somewhere in the file.

**Narrow definition (supported direct calls):**

- **Controller context:** a `class` whose name ends with `Controller` (optionally noting `extends …Controller` when present). Non-controller classes with Eloquent are **not** violations of this rule.
- **Eloquent static queries** inside that controller’s method bodies: `Identifier::method(...)` where `method` ∈ fixed allowlist: `query`, `where`, `whereIn`, `find`, `findOrFail`, `first`, `firstOrFail`, `get`, `all`, `create`, `update`, `delete`, `destroy`, `paginate`, `cursor`, `value`, `pluck`.
- **Supported `DB` facade forms** (same method allowlist where applicable): `DB::method(...)`; `\DB::method(...)`; `Illuminate\Support\Facades\DB::method(...)`; leading-backslash variants of the FQCN. Other facades or aliases are **not** confirmed unless added to this documented list later.
- Emit **`confirmed` only** when controller context **and** a supported direct operation are both established outside comments/strings, with exact evidence lines.

**Explicitly out of scope for confirmed (do not claim):**

- Indirect DB access via services, repositories, helpers, magic `__call`, variable methods (`$model->$method()`), invokable classes, jobs, form requests, or “business logic” generally.
- Proving a controller has “no business logic,” whole-controller compliance, or repository-wide compliance.
- Whole-repository or multi-file analysis.
- Query-like text in comments or strings; incomplete snippets that cannot establish controller + call reliably.

**Result semantics (do not invent a fifth finding status):**

| Result | How it is represented | Meaning |
| --- | --- | --- |
| Confirmed match | Finding with `checker_status = confirmed` | Supported direct violation with evidence. Does **not** mean the whole implementation is architecturally wrong. |
| No supported violation detected (clean) | **Check summary** on the Check UI (and/or empty confirmed list for that criterion) — **not** a new finding status | Controllers clearly parsed; no supported direct pattern. Does **not** imply whole-controller or repository compliance. |
| Needs review | Finding with `checker_status = needs_review` | Possible DB access outside supported patterns (e.g. `$this->repository->save(...)`) — human judgment; **not** confirmed. |
| Insufficient evidence | Finding with `checker_status = insufficient_evidence` | Untokenizable/severely incomplete PHP, or cannot establish required controller context; empty paste blocked by validation before checker. |

**Compatible / unknown criteria:** A criterion stores `rule_key` + human must/must-not text. “All compatible” = criteria whose `rule_key` is implemented. **Unknown `rule_key`:** create a visible finding (or check-summary item) stating the rule is **unsupported**, with `insufficient_evidence` (or equivalent non-confirmed status) — **never silently ignore**, never `confirmed`.

**Persistence:** Every finding belongs to the exact **CheckRun** that produced it; the CheckRun stores the submitted `php_code` snapshot. Each new check **inserts** a new CheckRun and new findings; prior runs/findings are immutable history.

### DatabaseSeeder + fixture files

Implements `prd.md > Seeded demo content`.
Seeds ≥1 Architecture decision prohibiting direct DB/Eloquent in controllers, with evaluation-limitations copy matching this spec. Fixture PHP files: **violating** controller with `Model::where`, **compliant** controller delegating to a service (no direct Eloquent in controller), **ambiguous/unsupported** (e.g. `$this->repository->save($data)` or incomplete snippet).

### Automated tests (Pest or PHPUnit)

Focused suite — **false `confirmed` on the ambiguous fixture is a release blocker**:

- Direct Eloquent query in a controller → `confirmed` + exact evidence lines.
- Compliant controller that delegates (no direct Eloquent/DB in controller) → clean summary / no confirmed.
- Query-like text inside **comments** and **strings** → not `confirmed`.
- Eloquent usage in a **non-controller** class → not `confirmed` for this rule.
- Ambiguous repository-style calls → `needs_review` or `insufficient_evidence`, never `confirmed`.
- Unknown `rule_key` → visibly unsupported / non-confirmed (not silent).
- Empty paste → validation error (HTTP layer).
- A few HTTP tests: overview seed data; new check creates a new CheckRun without overwriting prior findings; review updates workflow without changing `checker_status`.

## Data Model

SQLite via Eloquent. Minimal tables:

**decisions**

- `id`, `title`, `statement`, `rationale`, `category` (enum/string in fixed list), `status` (always `active`), `evaluation_limitations` (text), timestamps.

**criteria**

- `id`, `decision_id`, `rule_key`, `summary` (human must/must-not text), `sort_order`, timestamps.

**check_runs**

- `id`, `php_code` (text), `context_notes` (nullable text; non-evidentiary), `decision_scope` (`single` / `all_compatible`), `decision_id` (nullable when all), timestamps.

**findings**

- `id`, `check_run_id`, `decision_id`, `criterion_id`
- `checker_status`: `confirmed`  `needs_review`  `insufficient_evidence` (**immutable after create**)
- `explanation` (text)
- `evidence_start_line`, `evidence_end_line`, `evidence_snippet` (text)
- `workflow_status`: `null`  `reviewed`  `resolved` (human layer only)
- `verification_note` (nullable text)
- `reviewed_at` (nullable)
- timestamps.

**Relationships:** Decision hasMany Criteria, hasMany Findings; CheckRun hasMany Findings; Finding belongsTo Decision, Criterion, CheckRun.

**Persistence:** Each check creates a **new** CheckRun + findings in a transaction (never overwrite prior runs). Findings always reference their CheckRun (and thus the submitted code). Validate all request input (Form Requests). Returning later reads the same SQLite file.

**No** projects, users, or API-credential tables.

## File Structure

```
decisionledger/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── OverviewController.php
│   │   │   ├── DecisionController.php
│   │   │   └── CheckController.php      # run check + review actions OK here if thin
│   │   └── Requests/                    # StoreDecision, RunCheck, ReviewFinding, …
│   ├── Models/
│   │   ├── Decision.php
│   │   ├── Criterion.php
│   │   ├── CheckRun.php
│   │   └── Finding.php
│   └── Services/                        # or Domain/Checking/
│       ├── PhpArchitectureChecker.php
│       └── Rules/
│           └── NoDirectEloquentInControllerRule.php
├── database/
│   ├── migrations/
│   ├── seeders/DatabaseSeeder.php
│   └── fixtures/php/                    # violating.php, compliant.php, ambiguous.php
├── resources/
│   ├── views/
│   │   ├── layouts/app.blade.php
│   │   ├── overview/index.blade.php
│   │   ├── decisions/                   # index, show, create, edit
│   │   └── checks/show.blade.php        # form + results
│   ├── css/app.css
│   └── js/app.js
├── routes/web.php
├── tests/
│   ├── Unit/                            # checker + rule
│   └── Feature/                         # overview, check, review
├── devpost/                             # planning docs (this curriculum)
├── .env.example                         # sqlite, no secrets required
├── README.md                            # clone → migrate --seed → test → serve
└── composer.json
```

(Exact Laravel scaffolding files omitted; keep curriculum `devpost/` and skills untouched as non-app content.)

## External Services and Dependencies

- **None required at runtime.** SQLite is a local file.
- **Composer/npm** only for installing the app itself.
- **Optional later (explicitly non-blocking):** any AI API — not wired on the critical path; must not be required to seed, test, or demo.

## Important Failure Modes

- **Invalid / empty PHP paste** → validation error on Check form; checker not invoked for empty input.
- **Parse/tokenize ambiguity** → `insufficient_evidence` or `needs_review` with honest explanation; never `confirmed`.
- **Unknown or unimplemented `rule_key` on a criterion** → visibly identified as unsupported (non-confirmed finding or summary line); never silently ignored; never `confirmed`.
- **SQLite file missing / migrate not run** → README + `.env.example` make setup obvious; artisan errors are acceptable failure until migrated.

## What Was Simplified and Why

- **Single SQLite app with seeds** instead of multi-project or hosted DB — proves the kernel without infra.
- **One documented rule** (+ extension point via `rule_key`) instead of a generic rule engine — avoids overclaiming and scope creep.
- `**token_get_all` + dedicated rule class** instead of PHPStan/php-parser — enough for the narrow Eloquent-in-controller detection; revisit only if false positives block the demo.
- **Blade multipage** instead of SPA/Livewire complexity — faster, testable, polished enough.
- **Findings workspace = Check results screen** instead of a findings module — PRD deferred.
- **Local-only demo** instead of deployment pipeline — submission needs video + public repo, not hosting.
- **No auth** — solo PoC.

## Decisions and Open Issues

**Learner decisions (agreed):**

- Laravel + Blade + Vite; Tailwind only if already convenient, else light custom CSS.
- SQLite; domain checker; tokenizer/lightweight approach; Pest if convenient else PHPUnit.
- Local record only; README must enable unpaid clone→seed→test→serve.
- Checker reliability and honest limitation docs are the learning/design priority.
- Data model separates immutable checker result from human workflow.

**Derived implementation details (from those choices):**

- Table/column shape above; `rule_key` = `no_direct_eloquent_in_controller` for the seed criterion.
- Controllers stay thin; rule class owns detection allowlists.

**One useful unknown (learner priority — checker design):**
How to detect supported patterns reliably with line evidence and low false positives. **Resolved in spec / to verify in build:** narrow rule + ignore comments/strings + controller/method boundaries + exact evidence lines + explicit DB facade forms; clean results via check summary (no new status); false `confirmed` on ambiguous fixture = release blocker. Implement checker + tests first in `5-build`. Optional time-boxed `nikic/php-parser` only if tokenizer false positives block the demo — do not expand the rule catalog to justify it.

**Open (non-blocking):**

- Exact Tailwind-vs-custom decision at `composer create-project` / Breeze-less scaffolding time.
- Exact Laravel minor version at create time.
- Final seeded evaluation-limitations prose (must mirror this rule’s limitations).

