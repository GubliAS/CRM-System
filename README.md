# CRM System

**Current stage:** 0 — in progress

Web CRM for sales pipeline and customer support. Sales tracks leads, accounts, contacts, and opportunities. Service tracks cases. Shared tasks, calendar, reports, dashboards, and search sit on top of both.

Agents never commit. After a stage is ready, remind the team and give a one-line commit message for someone to run.

## About

This product is a small customer relationship system for a sales team and a service team that share the same customers. Sales moves a lead through qualification into an account, a contact, and an optional opportunity. Service opens cases against those accounts. Everyone works from one record model, one sharing model, and one web app.

The full description of records, roles, pipeline math, and the interface is in [ABOUT.md](ABOUT.md).

The build process, stage prompts, and manual steps are in [docs/TEAM_GUIDE.md](docs/TEAM_GUIDE.md). The build plan is in [docs/MASTER_PLAN.md](docs/MASTER_PLAN.md).

## Tech stack

| Layer | Choice |
| --- | --- |
| Language | PHP 8.3+ (Laravel 11 allows 8.2; Pest 4 from Breeze needs 8.3) |
| Framework | Laravel 11 |
| UI | Vue 3 Composition API with `<script setup>` |
| Bridge | Inertia |
| CSS | Tailwind CSS, design tokens in `resources/css/app.css` |
| Database | MySQL on Aiven (SSL) |
| Tests | Pest |
| Style | Pint |
| Queue | Database queue |
| Mail | `log` / Mailpit in development |
| Auth starter | Laravel Breeze (Vue) |

## Scope

**P0.** Auth, roles, CRUD for leads, accounts, contacts, opportunities, and cases, list and detail pages, global search, pre-built reports, and a home dashboard.

**P1.** Tasks, events, calendar, lead conversion, dashboards, a report builder, import/export, and email notifications.

**P2.** Only if a later stage is explicitly added: report subscriptions, dashboard auto-refresh, account hierarchy visualization, advanced search, attachment preview, and MFA.

**P3.** Will not be built: native apps, a workflow builder, AI, custom objects, an integration marketplace, Gmail/Outlook sync, campaigns, products, quotes, and recurring events.

## Build stages

0. Shell and docs (this stage, in progress).
1. Schema, models, factories, and seed data.
2. Roles, policies, password policy, lockout, session, and password history.
3. Accounts and contacts.
4. Leads and conversion.
5. Opportunities.
6. Cases.
7. Global search and recent records.
8. Tasks, events, and calendar.
9. Home dashboard.
10. Reports.
11. Dashboards, import/export, and notification email.
12. JSON API, deployment docs, Docker, and end-to-end tests.

## Coding standards

- Validate with Form Requests. Authorize with Policies. Each write goes through one Action class.
- Multi-record writes use a database transaction.
- Eager-load relationships a page displays. Paginate lists (25 default, 200 maximum).
- Format with Pint. Ship Pest tests with each feature.
- Call `config()` in application code. Call `env()` only inside config files.
- Colors, type, and spacing come from `resources/css/app.css`. Do not hard-code a palette in Vue.
- Do not commit secrets, `.env`, or `storage/certs`.

## Sharing model

Documented here for later stages. Not implemented in Stage 0.

| Role | Access |
| --- | --- |
| Administrator | Everything |
| Sales Manager | All sales records |
| Sales Representative | Own records |
| Service Representative | All cases, read accounts and contacts, own tasks |
| Read-only | Read everything, write nothing |

Deleting an opportunity archives it. Converted leads and closed cases are read-only.

## Pipeline

| Stage | Probability |
| --- | --- |
| Qualification | 10% |
| Meeting Scheduled | 20% |
| Proposal/Price Quote | 65% |
| Negotiation/Review | 80% |
| Closed Won | 100% |
| Closed Lost | 0% |

Expected revenue = amount × probability.

## Aiven MySQL

1. Create a MySQL service in Aiven and note the host, the non-3306 port, the database name, the user, and the password.
2. Download the CA certificate to `storage/certs/aiven-ca.pem`. That path is gitignored. Do not commit the file.
3. In `.env`, set `DB_CONNECTION=mysql` and the host, port, database, username, and password from Aiven. No real passwords belong in git or in `.env.example`.
4. Set `MYSQL_ATTR_SSL_CA` to the **absolute** path of the CA file. PDO on Windows often ignores a relative path.
5. PHP must have the `pdo_mysql` extension.
6. If the connection times out, allow the current public IP in the Aiven service allowlist.

Local tests do not use Aiven. PHPUnit overrides the connection to sqlite `:memory:`. See [docs/SETUP.md](docs/SETUP.md).

## Local commands

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
npm run dev
php artisan migrate
php artisan test
```

On PowerShell, `copy .env.example .env` is the same idea as `cp .env.example .env`.
