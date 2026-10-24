---
doc: prd
status: approved
---
<!-- `status` is the progress state every skill reads. Write `draft` when you first save this file,
     and change it to `approved` when the learner clearly approves the displayed plan ("looks good" counts).
     Do not request a second sign-off. Never skip the draft save —
     an unsaved draft dies with the conversation. -->

# DecisionLedger — Product Requirements

A polished local developer tool for a solo Laravel developer using AI coding agents: record architectural decisions with explicit verification criteria, paste PHP, and get explainable compliance findings without claiming full-repo verification or requiring paid APIs.
Source: `scope.md > The Unique Kernel`, `scope.md > Who It's For`, `scope.md > The POC Boundary`.

## The Core Journey
Source: `scope.md > The Core Loop`, `scope.md > What "Working" Looks Like`.

1. Open DecisionLedger → land on the **overview**: product header, short tagline (“Keep architectural decisions connected to implementation”), summary of saved decisions and findings awaiting review, recent decisions list, **New Decision**, and prominent **Check PHP**. Seeded Laravel sample decisions are already present — no project setup, API keys, or onboarding forms.
2. Open a seeded (or newly created) decision → see **Decision**, **Rationale**, **Verification criteria** (visually distinct), **Evaluation limitations**, **Related findings**, and actions (edit, check PHP, back to library/overview).
3. Choose **Check PHP** (from header or decision detail; if from detail, that decision is preselected) → dedicated check screen with required PHP paste, decision scope (one decision or all compatible), optional context notes (non-evidentiary).
4. Confirm which decisions/criteria will run → submit → same screen keeps the code and shows a results summary plus individual findings (decision link, criterion, evidence lines, explanation, status).
5. Mark a finding reviewed or resolved **in place** with a short verification note; original evidence remains. Optionally paste another snippet and check again without unnecessary navigation.
6. Success for the demo: create a decision, catch a violator with a confirmed finding that cites decision + criterion + lines, show a compliant or ambiguous case that does not over-flag, and complete a human review note — locally, offline baseline.

## Screens and Layout
Three primary surfaces; simple navigation among **Overview**, **Decisions** (library/list + detail), and **Check PHP**. Responsive layout with a particularly clear **desktop** demo experience. No project picker, project settings, or separate full findings-management app area.

### Overview
- Header: **DecisionLedger** name, short explanation, prominent **Check PHP**.
- Compact summary: count of saved decisions; count of findings awaiting review.
- Recent decisions list: title, category (from the fixed list), and decision status `active`; **New Decision** clearly visible.
- Seeded sample content visible immediately so the user can open a sample decision or start checking.

### Decision detail
- Sections clearly distinguished: Decision statement; Rationale; Verification criteria (visually distinct from rationale); Evaluation limitations; Related findings with review statuses; Actions (edit, Check PHP, return).

### Check PHP (dedicated screen)
- Enough space for code input, applicable criteria, and results.
- Required: multiline/code-friendly PHP input; applicable decision(s) (one saved decision or all compatible; preselect when launched from a decision).
- Optional: context notes (context only — never proof).
- Before run: show which decisions and criteria will be evaluated.
- After run: retain pasted code; results summary; individual findings; obvious path to check another snippet.

## Look and Feel
Calm, light, evidence-focused. Light neutral UI with restrained slate tones, crisp typography, subtle borders, limited accent color for status distinctions. Density closer to Linear or GitHub than a generic AI dashboard. Prioritize space for code and evidence; make decision→finding linkage immediately legible. Avoid oversized heroes, excessive cards, heavy gradients, and decorative AI branding.

## Features and Behavior

### Overview and navigation
Source: `scope.md > The Core Loop`.
- Solo user sees seeded content on first open; can explore without creating anything.
- Navigate to overview, decision library/detail, and Check PHP without deep menus.
- Summary and recent list orient the user; awaiting-review count points at unfinished human judgment.

Acceptance (observable):
- [ ] First open shows header, tagline, Check PHP, New Decision, and at least one seeded decision without setup forms.
- [ ] Summary reflects saved decisions and findings awaiting review.
- [ ] User can open a seeded decision or Check PHP within a few seconds of landing.

### Decisions library and detail
Source: `scope.md > The POC Boundary`.
- Create and view decisions with: concise decision statement; rationale; exactly one category from the fixed list (Architecture, Data & Database, Security, Testing, Performance, Other — no custom category management); decision status always `active` for the PoC; one or more explicit machine-checkable verification criteria (must / must-not / insufficient-when style); evaluation limitations text.
- Criteria are visually and semantically distinct from rationale (readable prose ≠ automatic rule).
- Edit a decision; list related findings with their statuses.
- From detail: Check PHP (preselects this decision) or return to overview/library.

Acceptance:
- [ ] Opening a seeded decision shows Decision, Rationale, Verification criteria, Evaluation limitations, Related findings, and actions.
- [ ] User can create a new decision with rationale and at least one explicit criterion.
- [ ] Criteria section is visually distinct from rationale.

### Check PHP
Source: `scope.md > The Unique Kernel`, `scope.md > The POC Boundary`.
- Primary input is pasted PHP; optional notes never count as compliance or violation evidence.
- User selects one decision or all compatible decisions; criteria to be evaluated are shown before run.
- Offline deterministic check against the **small, documented** rule set (priority: Eloquent query operations in a controller when the decision prohibits DB access in controllers).
- Results stay on the check screen with code retained; easy re-check of another snippet.

Acceptance:
- [ ] Cannot run a check with empty PHP paste (clear message).
- [ ] Launching from a decision detail preselects that decision.
- [ ] Before/after run, user can see which criteria applied.
- [ ] Context notes do not create findings by themselves.

### Findings and review
Source: `scope.md > The Unique Kernel`, `scope.md > What "Working" Looks Like`.
- Each finding shows: linked decision title; exact criterion; source lines with line numbers and highlighted evidence; plain-language explanation; status `confirmed`, `needs review`, or `insufficient evidence`.
- Semantics: `confirmed` = deterministic match within supported rules (a rule violation under those criteria — **not** a claim the whole implementation is architecturally wrong); `needs review` = human judgment required; `insufficient evidence` = input cannot support a reliable determination.
- Mark reviewed or resolved **in place** with a short verification note; preserve original finding and evidence; resolving does **not** mean the app verified a fix.
- **Checker result vs human workflow:** Finding statuses from the checker (`confirmed`, `needs review`, `insufficient evidence`) keep their original meanings and must not be overwritten by human review. Reviewed/resolved are separate finding workflow states/actions layered on top of the preserved checker result.
- Check results screen is the primary findings workspace for the PoC.

Acceptance:
- [ ] A violating fixture produces a confirmed finding citing decision, criterion, and evidence lines.
- [ ] A compliant fixture does not produce a false confirmed violation for that rule.
- [ ] An ambiguous/unsupported fixture yields needs review or insufficient evidence — not a confirmed violation.
- [ ] UI copy does not claim full-repository or live-agent verification (pasted text only).
- [ ] After marking reviewed/resolved with a note, original evidence and original checker status remain visible; workflow state does not replace the checker result.

### Seeded demo content
Source: `scope.md > The POC Boundary`.
- Preloaded sample decisions and paste fixtures: violating, compliant, and ambiguous/unsupported examples for the initial Eloquent-in-controller rule.

Acceptance:
- [ ] Demo can be completed locally without paid API credentials using seed data plus one user-created decision.

## States and Boundaries

- **First use** — Seeded decisions and fixtures present; no project creation, API keys, or accounts. User can open a sample decision and Check PHP immediately.
- **Empty PHP** — Check does not run; user is told code is required.
- **No compatible criteria** — If selected decisions have nothing the checker can evaluate for the paste, show a clear message; do not invent confirmed violations.
- **Unsupported / ambiguous input** — Status `needs review` or `insufficient evidence`, never confirmed.
- **After check** — Code and findings remain on the check screen; user can adjust paste and run again.
- **Review update** — Reviewed/resolved + note updates human workflow only; does not overwrite checker status (`confirmed` / `needs review` / `insufficient evidence`); does not rewrite or erase original evidence; does not claim the app re-verified a fix.
- **Decision identity** — Exactly one category from the fixed list; decision status is always `active` (no draft/archived/deprecated/approved lifecycle).
- **Persistence** — Decisions, checks, findings, and review notes remain available when the user returns in a later session (local app data).
- **Trust boundary** — Always communicate: analysis is of pasted text only; confirmed means rule match in the documented set, not whole-architecture judgment.
- **Single-app seed** — No first-class projects; one application with seeded Laravel example content.

## Product Decisions

- Seeded Laravel example is **content inside one app**, not a project entity — avoids project create/select/settings for the PoC.
- **Dedicated Check PHP screen** — code, criteria, and findings need readable space.
- **Code-first**; optional notes are context only, never evidence.
- **Findings reviewed in place** on the results screen — separate findings-management area deferred unless a concrete need appears.
- **Look:** light, calm, slate, crisp, Linear/GitHub-like density; no generic AI-dashboard chrome.
- **Statuses kept precise:** checker statuses confirmed / needs review / insufficient evidence, with the semantics above; human reviewed/resolved are separate and do not overwrite checker results.
- **Decision categories:** fixed list only (Architecture, Data & Database, Security, Testing, Performance, Other); one per decision; no custom category management.
- **Decision status:** `active` only for the PoC.
- Desktop clarity prioritized for the hackathon demo; layout still responsive.

## What We're Building
Source: `scope.md > The POC Boundary`.

- Overview with header, summaries, recent decisions, New Decision, Check PHP, seeded content.
- Decision create/view/edit with distinct rationale vs criteria vs limitations; related findings.
- Dedicated Check PHP flow (one or all compatible decisions; preselect from detail).
- Small documented deterministic rule set; initial priority rule: no direct Eloquent queries in controllers when the decision prohibits it; tokenization/lightweight parsing preferred over naive regex where practical.
- Explainable findings with review/resolve + note; preserve evidence.
- Seed fixtures: violator, compliant, ambiguous/unsupported.
- Offline baseline mandatory; polished light UI as specified.
- Explicit limitation copy so the product does not overclaim.

## Deferred From the POC

- First-class projects / multi-project UX — not needed to prove the kernel.
- Full findings inbox or cross-cutting findings management app — results screen suffices.
- Accounts, teams, sharing — solo PoC.
- Repository/GitHub/IDE integrations and continuous monitoring — imply capabilities outside pasted-text analysis.
- Generic rule engine for arbitrary architectural decisions — scope forbids; trust risk.
- Mandatory or blocking external AI — offline baseline is the product proof.

## Possible Later Enhancements
Richer documented rule catalog; optional AI assist for explanations or drafting criteria; export/share; multi-project personal use; HTML planning companion for product-experience review if useful later.

## Non-Goals
- Evaluate arbitrary architectural decisions beyond the documented rule set.
- Claim repository-wide or live agent-session verification.
- Treat context notes as proof of compliance or violation.
- Let “resolved” imply the application independently verified a fix.
- Generic admin-dashboard or decorative AI product aesthetic.
- Build a generic rule engine “just in case.”

## Open Questions
- Exact wording of evaluation-limitations copy on seeded decisions — finalize in build from `scope.md` checker limitations; not blocking for `4-spec`.
