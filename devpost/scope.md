---
doc: scope
status: approved
---
<!-- `status` is the progress state every skill reads. Write `draft` when you first save this file,
     and change it to `approved` when the learner clearly approves the displayed plan ("looks good" counts).
     Do not request a second sign-off. Never skip the draft save —
     an unsaved draft dies with the conversation. -->

# DecisionLedger

A local Laravel web app where a solo developer records architectural decisions with machine-checkable criteria, pastes agent-produced PHP, and gets explainable compliance findings — without claiming to verify a whole repository or requiring paid AI APIs.

## The Unique Kernel
Architectural decisions stop being forgotten chat history and become a **persistent, actionable checklist**: each finding cites the exact decision, criterion, and code evidence, and clearly labels **confirmed violation**, **needs review**, or **insufficient evidence** — so trust comes from explainability, not a green/red score.

## Who It's For
A single developer building a real Laravel app with an AI coding agent (Cursor, Claude Code, Codex, etc.). Today they rely on memory and re-prompting when the agent ignores earlier constraints. For the PoC they use DecisionLedger alone — no teams or accounts.

## The Core Loop
1. Record (or open a seeded) engineering decision: title, rationale, and one or more structured must / must-not / insufficient-if criteria.
2. Paste PHP (controller/service excerpt) as the primary check input; optional notes are context only, never proof of compliance.
3. Run a deterministic offline checker; review findings linked to decision + criterion + lines.
4. Mark findings reviewed/resolved with a short verification note; come back when the next agent output needs checking.

## Inspiration & Identity
Feels like a sharp engineering tool next to the agent: clear, polished, easy to understand — not a SaaS dashboard. Trust-first language (“analyzed pasted text only”). Provider-agnostic; fits personal tracking/management workflows the learner already cares about.

## Why This Matters to the Learner
Wants DecisionLedger in their own agent workflows so context isn’t lost, and to consolidate agent-driven + Laravel skills on something they’ll actually use. Personal use first; share later once it clearly solves the problem.

## What "Working" Looks Like
~60-second local demo, no paid API credentials:
- Show a preloaded Laravel architectural decision.
- Create a new decision with rationale + explicit verification rule.
- Paste a short violating Laravel controller → finding with offending line(s), linked decision, criterion, explanation.
- Show a compliant or ambiguous example so the checker doesn’t flag everything.
- Mark a finding reviewed/resolved with a short note.

Success = a developer can see **why** a finding exists, trace it to a decision they control, and tell a deterministic hit from uncertainty.

## The POC Boundary
- Laravel/PHP web UI; single-user, no auth/accounts/teams.
- CRUD-ish for decisions (create/view) with human-readable title, rationale, and structured machine-checkable criteria (must / must-not / insufficient-when).
- Paste PHP code (primary) + optional notes (non-evidentiary); run offline deterministic check.
- **Small, documented set of deterministic Laravel/PHP rules only** — not a claim to evaluate arbitrary architectural decisions. Do not build a generic rule engine unless a later official workflow step identifies it as essential.
- **Initial rule catalog (priority):** when the associated decision explicitly prohibits database queries in controllers, flag a controller that contains direct Eloquent query operations. Prefer PHP tokenization / lightweight parsing over naive regex where practical.
- **Fixtures required:** a violating example, a compliant example, and an ambiguous or unsupported example — so the checker is shown not to flag everything.
- Findings: every **confirmed** finding must reference the exact decision, criterion, and evidence lines. Rules that cannot be evaluated reliably return `needs review` or `insufficient evidence`, never a confirmed violation. Never claim full-repo verification.
- Finding lifecycle: reviewed/resolved + short verification note.
- Seeded sample decisions + realistic paste examples for an immediate demo.
- Polished, understandable UI.

**Checker limitations (explicit — do not overclaim):**
- Analyzes **pasted text only**, not a live repository or agent session.
- Supports only the **small, documented** deterministic rule set above; it does **not** evaluate arbitrary architectural decisions.
- Unsupported or unreliable cases → `needs review` / `insufficient evidence`, not confirmed violations.
- Optional notes never count as compliance proof.
- No mandatory external AI; optional AI only if time remains and it improves results without blocking the baseline.
- No generic “evaluate any decision” rule engine in this PoC.

## Later
Optional AI assist for explanations or drafting criteria; richer rule catalog (only if needed); export/share; personal multi-project use beyond the seeded demo set.

## Explicitly Cut
- Repository scanning / watching the filesystem — would imply repo verification the PoC doesn’t do.
- GitHub / IDE / Cursor plugins — provider lock-in and integration scope.
- Continuous monitoring of agent changes — ops product, not the kernel.
- AI-generated architecture recommendations — different product; paid API risk.
- Accounts, teams, collaboration — not needed for solo PoC demo.
- Generic “understand any PHP,” free-form LLM judging as the baseline, or a generic rule engine for arbitrary decisions — undermines trust and the offline mandate.
