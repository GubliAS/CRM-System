# Team guide

Read this first if you are joining the CRM build. The build plan is in [MASTER_PLAN.md](MASTER_PLAN.md). The record model lives in [ABOUT.md](../ABOUT.md). Local install steps live in [SETUP.md](SETUP.md). Stage prompts live in [prompts/](prompts/).

## Contents

- [What this CRM is](#what-this-crm-is)
- [Who this guide is for](#who-this-guide-is-for)
- [Locked decisions](#locked-decisions)
- [Sharing model](#sharing-model)
- [Pipeline](#pipeline)
- [Out of scope](#out-of-scope)
- [How to work](#how-to-work)
- [Working in parallel](MASTER_PLAN.md#working-in-parallel)
- [Master plan](MASTER_PLAN.md)
- [Where Stage 0 stands](#where-stage-0-stands)
- [Stages](#stages)
- [What not to do](#what-not-to-do)
- [Do this before Stage 1](#do-this-before-stage-1)
- [Manual work by stage](#manual-work-by-stage)
- [Mail](#mail)
- [File storage](#file-storage)
- [Hosting](#hosting)
- [Commit messages](#commit-messages)

## What this CRM is

This is a web app for one company's sales and support. Sales tracks people who might buy, the companies they work for, and the deals moving toward a close. Service tracks support cases until they are resolved. Tasks, a calendar, search, reports, and dashboards sit on top of those records.

A lead is a prospect. Conversion turns a lead into an account (the company), a contact (the person), and optionally an opportunity (the deal). A case is a support ticket. The full list of fields, statuses, and relationships is in [ABOUT.md](../ABOUT.md).

## Who this guide is for

Two teammates are building this from the requirements specification. Agents write code only for the stage you paste. Agents do not commit. After each stage, the agent reminds you and gives a one-line commit message. You commit when you decide the work is ready.

Rules that agents must follow are in `.cursor/rules/`. Product scope is in [README.md](../README.md).

## Locked decisions

These choices override any earlier database or stack notes.

| Topic | Decision |
| --- | --- |
| Database | MySQL on Aiven.io. The service already exists. SSL is required. |
| CA file | `storage/certs/aiven-ca.pem`. That folder is gitignored. |
| SSL path | `MYSQL_ATTR_SSL_CA` must be an absolute path. PDO on Windows often ignores a relative path. |
| Port | Aiven's port is not 3306. Copy it from the service page. |
| PHP extension | `pdo_mysql` must be enabled. On this machine it is commented out in `C:\php\php.ini`. |
| App | Laravel 11.56.1, Breeze 2.4.2, Inertia, Vue 3 `<script setup>`, Tailwind. |
| Stay on Laravel 11 | Do not upgrade to Laravel 12. Composer 2.10 blocks Laravel 11 advisories, so `composer.json` sets `policy.advisories.block` to `false`. |
| PHP | CI uses PHP 8.3 because Pest 4 requires it. Laravel 11 still allows PHP 8.2. |
| Styles | Tokens live in `resources/css/app.css` (`:root`). Vue uses token classes only. No hex and no inline color or font styles. |
| Tests | Pest uses sqlite `:memory:` so CI does not need Aiven secrets. The running app still uses MySQL. |

Token values:

| Token | Value |
| --- | --- |
| Primary | `#032d60` |
| Secondary | `#0176d3` |
| Background | `#f3f3f3` |
| Surface | `#ffffff` |
| Text | `#181818` |
| Muted text | `#5c5c5c` |
| Border | `#e5e5e5` |
| Success | `#2e844a` |
| Danger | `#ba0517` |
| Warning | `#8c4b02` |
| Info | `#0176d3` |
| Font | Arial, Helvetica, sans-serif |
| H1 / H2 / H3 | 1.5rem / 1.25rem / 1rem |
| Body / small | 0.875rem / 0.75rem |
| Line height | 1.5 |

## Sharing model

Implement this in Stage 2, not before. Checks live in Laravel Policies on the server. Hiding a button in Vue is not enough.

| Role | Access |
| --- | --- |
| Administrator | Everything |
| Sales Manager | All sales records |
| Sales Representative | Records they own |
| Service Representative | All cases, read accounts and contacts, own tasks |
| Read-only | Read, never write |

Deleting an opportunity archives the row. Converted leads and closed cases are read-only. Closed cases can be reopened.

## Pipeline

| Stage | Probability |
| --- | --- |
| Qualification | 10% |
| Meeting Scheduled | 20% |
| Proposal/Price Quote | 65% |
| Negotiation/Review | 80% |
| Closed Won | 100% |
| Closed Lost | 0% |

Expected revenue = amount × probability. Changing the stage updates the probability.

## Out of scope

Do not build these unless a later prompt explicitly adds them:

- Campaigns, products, quotes, and recurring events
- AI recommendations, native mobile apps, a workflow builder, and custom objects
- Gmail or Outlook sync
- Multi-factor authentication and file preview
- Report email subscriptions, dashboard auto-refresh, an account hierarchy picture, and advanced search

Home suggestions in Stage 9 are two rules only: accounts with no activity for 30 days, and opportunities near their close date with no recent update.

## How to work

The order and the reason for it are in [MASTER_PLAN.md](MASTER_PLAN.md). Who can build at the same time is in [Working in parallel](MASTER_PLAN.md#working-in-parallel). Use that plan to see what a stage delivers and what it depends on. Use the prompt file to tell the agent what to build.

1. Read the prompt for the current stage.
2. Paste it in Agent mode.
3. Review the result, including tests.
4. Commit yourselves, using the message in that prompt, when you accept the work.
5. Only then paste the next prompt.

Do not let an agent start the next stage. The **Current stage** line in [README.md](../README.md) stays `0 — in progress` until you accept Stage 0. An agent changes that line only after you accept a stage.

## Where Stage 0 stands

The Stage 0 scaffold is in the repo and is still in progress. Pint was clean. 28 Pest tests passed. Aiven was not connected, because `.env` has no database password and `pdo_mysql` is still commented out. A full browser click-through of register, login, logout, Home, and About was not done.

Finish [the steps below](#do-this-before-stage-1) before you treat Stage 0 as accepted or start Stage 1.

## Stages

| Stage | What it builds | Priority | Prompt |
| --- | --- | --- | --- |
| 0 | App shell, docs, rules, design tokens | Foundation | [stage-00.md](prompts/stage-00.md) |
| 1 | Schema, models, factories, seed data | Foundation | [stage-01.md](prompts/stage-01.md) |
| 2 | Roles, password policy, record sharing | P0 | [stage-02.md](prompts/stage-02.md) |
| 3 | Accounts and contacts | P0 | [stage-03.md](prompts/stage-03.md) |
| 4 | Leads and conversion | P0, conversion is P1 | [stage-04.md](prompts/stage-04.md) |
| 5 | Opportunities and stages | P0 | [stage-05.md](prompts/stage-05.md) |
| 6 | Cases | P0 | [stage-06.md](prompts/stage-06.md) |
| 7 | Global search and recent records | P0 | [stage-07.md](prompts/stage-07.md) |
| 8 | Tasks, events, calendar | P1 | [stage-08.md](prompts/stage-08.md) |
| 9 | Home dashboard | P0 | [stage-09.md](prompts/stage-09.md) |
| 10 | Pre-built reports and the report builder | P0 then P1 | [stage-10.md](prompts/stage-10.md) |
| 11 | Dashboards, CSV import/export, notification email | P1 | [stage-11.md](prompts/stage-11.md) |
| 12 | JSON API, Docker, release docs, end-to-end tests | Close-out | [stage-12.md](prompts/stage-12.md) |

## What not to do

- Do not build the whole specification in one pass.
- Do not start with dashboards or charts. Those need records from earlier stages.
- Do not invent Campaign, Product, Quote, or recurring-event objects.
- Do not put permission checks only in Vue.
- Do not load real customer data. Seed data stays obviously fake.
- Do not copy the Salesforce name, logo, or font files.
- Do not commit `.env`, passwords, or the Aiven CA file.

## Do this before Stage 1

Each teammate does this on their own machine. Details are also in [SETUP.md](SETUP.md) and [stage-00.md](prompts/stage-00.md).

1. Uncomment `extension=pdo_mysql` in `C:\php\php.ini`, then open a new terminal and confirm `php -m` lists `pdo_mysql`.
2. From the Aiven MySQL service, copy the host, port, database name, user, and password into `.env` only.
3. Download the CA certificate and save it as `storage/certs/aiven-ca.pem`. Set `MYSQL_ATTR_SSL_CA` to that file's absolute path.
4. Run `php artisan migrate`.
5. In the browser, register, log in, log out, and open Home and About.

If the connection times out, allow your current public IP on the Aiven service.

## Manual work by stage

| Stage | You do this by hand | Detail |
| --- | --- | --- |
| 0 | Enable `pdo_mysql`, fill Aiven `.env`, migrate, click through auth and About | [stage-00.md](prompts/stage-00.md) |
| 1 | Confirm `.env` still points at Aiven, review the migrations, then `php artisan migrate --seed` | [stage-01.md](prompts/stage-01.md) |
| 2 | Keep mail on the `log` driver or Mailpit. Create no production mail account yet | [stage-02.md](prompts/stage-02.md) |
| 3 | Log in as a rep and as a manager and try the list and detail pages | [stage-03.md](prompts/stage-03.md) |
| 4 | Convert a lead in the browser and confirm the new records and the locked lead | [stage-04.md](prompts/stage-04.md) |
| 5 | Move one deal to Closed Won and confirm probability is 100 | [stage-05.md](prompts/stage-05.md) |
| 6 | Close a case, confirm the form is locked, reopen it, and edit the subject | [stage-06.md](prompts/stage-06.md) |
| 7 | Search as a user who must not see another rep's private lead | [stage-07.md](prompts/stage-07.md) |
| 8 | Nothing new to sign up for. Reminder email waits until Stage 11 | [stage-08.md](prompts/stage-08.md) |
| 9 | Open Home as a rep with seed data and follow each widget to a real record | [stage-09.md](prompts/stage-09.md) |
| 10 | Compare the current-year pipeline report total with the home funnel | [stage-10.md](prompts/stage-10.md) |
| 11 | Put a production mail key in `.env` only, keep Mailpit locally, import a small fake CSV | [stage-11.md](prompts/stage-11.md) |
| 12 | Create a production Aiven database, a production mail key, and a PHP 8.3 host with HTTPS and a queue worker | [stage-12.md](prompts/stage-12.md) |

## Mail

Use the `log` driver or [Mailpit](https://mailpit.axllent.org/) through Stage 10. Password-reset mail in development can be read from `storage/logs/laravel.log` or from Mailpit at `127.0.0.1:1025`.

At Stage 11, pick a production provider and store its keys only in `.env` or the host environment:

| Option | Use |
| --- | --- |
| Resend | Recommended. Simple API and a free tier. |
| Postmark | Strong delivery. Paid. |
| Amazon SES | Cheap at volume. More setup. |

Do not use Gmail as the production mailer.

## File storage

Stages 0 through 11 do not upload files. If you later add an attachment stage, keep development files on the local disk (`storage/app`) and use Cloudflare R2 or Amazon S3 in production. Do not store files in MySQL.

## Hosting

Stage 12 needs a PHP 8.3 host that can run a queue worker and HTTPS. Practical options are Laravel Forge on a small VPS, Ploi, or Railway. Point production at a second Aiven database or a second Aiven service, not the database you develop against.

Run the worker as `php artisan queue:work --tries=3`.

Aiven's own backups cover MySQL. Do not promise 99.5% uptime, geo-redundant backups, or a penetration test unless the school provides that infrastructure.

## Commit messages

You run these. The agent does not.

| When | Message |
| --- | --- |
| Stage 0, when you accept the scaffold | Scaffold the CRM app, docs, rules, and design tokens. |
| This guide and the prompt files | Document the team guide and stage prompts. |

Each later prompt file ends with its own suggested message.
