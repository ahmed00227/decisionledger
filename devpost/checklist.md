---
doc: checklist
status: approved
---
<!-- `status` is the progress state every skill reads. Write `draft` when you first save this file,
     and change it to `approved` when the learner clearly approves the displayed plan ("looks good" counts).
     Do not request a second sign-off. Never skip the draft save —
     an unsaved draft dies with the conversation. -->

# Build Checklist

Build mode: fast

## Slices

- [x] **1. Checker proves the trust kernel offline**
  Becomes usable: Laravel app scaffolded (SQLite, Blade/Vite); `PhpArchitectureChecker` + `no_direct_eloquent_in_controller` rule run via focused automated tests — violating → confirmed + evidence lines; compliant → clean; comments/strings/non-controller/ambiguous/unknown rule_key never falsely confirmed.
  Why now: The unique kernel and highest technical risk are the checker’s reliability. Runnable evidence is the test suite; false confirmed on ambiguous is a release blocker. Scaffold lives inside this slice, not separately.
  PRD ref: `prd.md > Check PHP`, `prd.md > Findings and review`, `prd.md > Seeded demo content`
  Spec ref: `spec.md > Components > PhpArchitectureChecker`, `spec.md > Components > Rule: no_direct_eloquent_in_controller`, `spec.md > Components > Automated tests`, `spec.md > Stack`, `spec.md > File Structure`
  Build: Create Laravel project in the repo (preserve `devpost/` and curriculum skill folders). Configure SQLite. Prefer Pest if scaffolding makes it convenient, else PHPUnit. Implement tokenizer-based rule (ignore comments/strings; controller/method boundaries; exact evidence lines; documented DB facade forms). Add fixture PHP files and unit tests for all required cases. No full UI required yet.
  Verify (mechanical): Run the checker test suite (`./vendor/bin/pest` or `php artisan test` filtered to checker/rule tests); all listed cases pass; ambiguous fixture must not be `confirmed`.
  Learner check: Skim one failing-vs-passing fixture and the matching test names; confirm the rule’s documented limitations still match what you want claimed.
  Commit: `Add Laravel scaffold and offline architecture checker tests`

- [x] **2. Decisions and check runs persist locally**
  Becomes usable: Migrations/models for decisions, criteria, check_runs, findings; seeder loads sample Architecture decision + criteria; running the checker through a thin orchestration path creates a **new** CheckRun each time and stores findings linked to that run’s code snapshot (prior runs untouched).
  Why now: Persistence and immutability of runs/findings must work before UI polish; catches data-model mistakes early.
  PRD ref: `prd.md > Decisions library and detail`, `prd.md > States and Boundaries`, `prd.md > Seeded demo content`
  Spec ref: `spec.md > Data Model`, `spec.md > Components > DatabaseSeeder + fixture files`, `spec.md > The Core Journey Through the System`
  Build: Add migrations and Eloquent models; Form Request–ready validation rules on models/services as needed; DatabaseSeeder + evaluation-limitations copy from the rule; a small application service or artisan/feature path that persists check results. Unknown `rule_key` stored as visible non-confirmed finding.
  Verify (mechanical): `php artisan migrate:fresh --seed` succeeds; feature/unit test creates two check runs and asserts both exist with separate findings; unknown rule_key not silent; review fields absent or null until slice 4 is fine.
  Learner check: After seed, open the SQLite DB or a simple tinker/query output and confirm the sample decision and criteria exist.
  Commit: `Persist decisions, check runs, and findings in SQLite`

- [ ] **3. Core journey UI: overview, decisions, Check PHP**
  Becomes usable: Browser UI for overview (header, summaries, recent decisions, New Decision, Check PHP), decision create/show/edit, and dedicated Check PHP screen (paste, scope, criteria preview, results + clean summary, evidence). Look and feel follows spec (light slate, compact).
  Why now: Makes the product demonstrable end-to-end after the kernel and data are proven.
  PRD ref: `prd.md > The Core Journey`, `prd.md > Screens and Layout`, `prd.md > Overview and navigation`, `prd.md > Decisions library and detail`, `prd.md > Check PHP`, `prd.md > Look and Feel`
  Spec ref: `spec.md > Components > OverviewController`, `spec.md > Components > DecisionController`, `spec.md > Components > CheckController`, `spec.md > Look and Feel`, `spec.md > Where It Runs and How Someone Tries It`
  Build: Routes + thin controllers + Blade layout/views; Tailwind if already present else light custom CSS; wire check form to persistence from slice 2; trust copy (“pasted text only”); preselect decision from query param.
  Verify (mechanical): Feature tests for overview seed visibility, create decision, run check producing confirmed finding; `php artisan serve` starts; manually or via HTTP tests hit key routes without 500s.
  Learner check: Start the app, open a seeded decision, run Check PHP with the violating fixture, and say whether the finding (decision, criterion, lines, status) is clear enough for a 60s demo.
  Commit: `Add overview, decisions, and Check PHP UI`

- [ ] **4. Review workflow, polish, and README**
  Becomes usable: In-place reviewed/resolved + verification note without overwriting checker_status/evidence; polished desktop UI pass; README documents clone → sqlite → migrate --seed → test → serve with no paid services.
  Why now: Completes demo success criteria and ship readiness without expanding scope.
  PRD ref: `prd.md > Findings and review`, `prd.md > What We're Building`
  Spec ref: `spec.md > Components > FindingReviewController`, `spec.md > Important Failure Modes`, `spec.md > Where It Runs and How Someone Tries It`
  Build: Review action on findings; HTTP test that workflow updates leave checker_status intact; UI polish for status accents and evidence blocks; `.env.example` + README setup; ensure `.gitignore` covers Laravel artifacts while keeping learner-profile ignore.
  Verify (mechanical): Full relevant test suite green; review feature test passes; README steps dry-run locally (migrate --seed, tests, serve).
  Learner check: Mark a finding reviewed with a note, confirm original checker status and evidence remain visible, and skim the README for anything a stranger would trip on.
  Commit: `Add finding review workflow, polish, and setup README`

## Hands-on Checkpoints

- [ ] Early usable behavior explored — after slice 3 (first time the full UI journey is tryable; feedback can still shape slice 4 polish)
- [ ] Final kick-the-tires exploration and feedback completed

## Final Review

- [ ] Final review complete — feedback resolved and learner confirms ready to ship

## Code Tour and App Map

- [ ] Learning activity complete — guided route, focused alternative, prior practice connected, or brief recap
- [ ] Optional edit and transfer reflection addressed — offered/declined/already covered/not applicable as appropriate
- [ ] `devpost/app-map.html` generated from finished code, checked, and shown, including a project-grounded practice to reuse

Activity and evidence: [what actually happened; real document/test/code references; unfinished work if interrupted]
Route and stops: [actual paths and symbols; guided stops completed, or reference-only route]
Edit outcome: [tried/kept/reverted/declined/not applicable; verification if changed]
Reflection: [offered/answered/declined/already covered — personal answer belongs only in the ignored profile]
Activity mode: [live app and editor, explicit static fallback, focused alternative, prior practice, or recap]

## Revisions
