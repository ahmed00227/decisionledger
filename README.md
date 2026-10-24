# DecisionLedger

A local Laravel proof of concept for developers who use AI coding agents: record architectural decisions with explicit verification criteria, paste PHP, and get **explainable** compliance findings from a small offline rule set.

**Trust boundary:** the app analyzes **pasted text only**. It does not scan your repository or claim whole-architecture verification. Confirmed findings are supported rule matches — not a judgment that an entire implementation is wrong.

## Requirements

- PHP 8.2+ with SQLite extension
- Composer
- Node.js + npm (for front-end assets)

No API keys. No paid services.

## Setup

```bash
git clone <your-repo-url> decisionledger
cd decisionledger
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
# .env.example already uses DB_CONNECTION=sqlite
php artisan migrate --seed
npm install
npm run build
```

## Run

```bash
php artisan serve
```

Open the URL printed in the terminal (usually `http://127.0.0.1:8000`).

## Tests

```bash
php artisan test
```

Focused checker tests:

```bash
php artisan test --filter=PhpArchitectureCheckerTest
```

## Demo path (~60 seconds)

1. Open **Overview** — seeded Architecture decision is present.
2. Open the decision — read **Decision**, **Rationale**, **Verification criteria**, and **Evaluation limitations**.
3. Click **Check PHP** (decision preselected).
4. Paste contents of `database/fixtures/php/violating_controller.php` → **Run check** → see a **confirmed** finding with evidence lines.
5. Paste `compliant_controller.php` → clean summary (no confirmed violation). This does **not** mean whole-repo compliance.
6. Paste `ambiguous_repository.php` → **needs review**, never confirmed.
7. Mark a finding **reviewed** or **resolved** with a short note — checker status and evidence stay unchanged.

## Supported rule (PoC)

`no_direct_eloquent_in_controller` — direct Eloquent-style static queries and documented `DB` facade forms inside `*Controller` classes. Comments/strings are ignored. Indirect repository/service access is out of scope for **confirmed**.

Unknown rule keys are reported as **unsupported** (non-confirmed), never silently ignored.

## Project docs

Planning artifacts live under `devpost/` (`scope.md`, `prd.md`, `spec.md`, `checklist.md`).
