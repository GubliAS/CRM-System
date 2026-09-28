# Master plan

This is the build plan for the CRM. The teammate guide explains how to set up and how to run a stage. The files in [prompts/](prompts/) are what you paste into an agent. This document is the map of what gets built, in what order, and why.

## Purpose

Two teammates build this product one stage at a time. You review the prompt, run that stage, review the result, and commit only when you accept the work. The commit message is the one in that stage's prompt file.

Agents do not commit. Agents do not start the next stage. The **Current stage** line in [README.md](../README.md) changes only after you accept a stage.

## Locked decisions

| Topic | Decision |
| --- | --- |
| Database | MySQL on the Aiven service that already exists. SSL is required. |
| CA file | `storage/certs/aiven-ca.pem`. That folder is gitignored. |
| SSL path | `MYSQL_ATTR_SSL_CA` is an absolute path. The Aiven port is not 3306. |
| App | Laravel 11.56.1, Breeze 2.4.2, Inertia, Vue 3 `<script setup>`, Tailwind. |
| Stay on Laravel 11 | Do not upgrade to Laravel 12. |
| Styles | Tokens live only in `resources/css/app.css`. Vue uses those token classes. No hex in Vue files. |
| Tests | Pest uses sqlite in memory so CI does not need Aiven secrets. The running app uses MySQL. |
| CI PHP | PHP 8.3, because Pest 4 requires it. Laravel 11 still allows PHP 8.2. |

## The product

Sales and service share one web app.

Sales starts with a lead, a person who might buy. Conversion creates an account (the company), a contact (the person), and optionally an opportunity (the deal). Service tracks cases, which are support tickets, until they are closed.

A deal moves through this pipeline. Changing the stage sets the probability.

| Stage | Probability |
| --- | --- |
| Qualification | 10% |
| Meeting Scheduled | 20% |
| Proposal/Price Quote | 65% |
| Negotiation/Review | 80% |
| Closed Won | 100% |
| Closed Lost | 0% |

Expected revenue = amount × probability. Field lists, statuses, and relationships are in [ABOUT.md](../ABOUT.md).

## Sharing model

Use this from Stage 2 onward. Policies on the server decide what a user can open. Hiding a button is not enough.

| Role | Access |
| --- | --- |
| Administrator | Everything |
| Sales Manager | All sales records |
| Sales Representative | Records they own |
| Service Representative | All cases, read accounts and contacts, own tasks |
| Read-only | Read, never write |

Deleting an opportunity archives the row. A converted lead is read-only. A closed case is read-only until someone reopens it.

## Priority cut

| Band | Stages |
| --- | --- |
| Foundation | [0](prompts/stage-00.md), [1](prompts/stage-01.md) |
| P0 | [2](prompts/stage-02.md), [3](prompts/stage-03.md), [5](prompts/stage-05.md), [6](prompts/stage-06.md), [7](prompts/stage-07.md), the pre-built reports inside [10](prompts/stage-10.md), and home in [9](prompts/stage-09.md) |
| P1 | Lead conversion in [4](prompts/stage-04.md), [8](prompts/stage-08.md), [9](prompts/stage-09.md), the report builder in [10](prompts/stage-10.md), [11](prompts/stage-11.md) |
| Close-out | [12](prompts/stage-12.md) |

P2 and P3 are not scheduled. Do not build them unless a new prompt says so.

P2: report subscriptions, dashboard auto-refresh, an account hierarchy picture, advanced search, attachment preview, multi-factor authentication.

P3: native apps, a workflow builder, AI, custom objects, an integration marketplace, Gmail or Outlook sync, campaigns, products, quotes, recurring events.

## Stages

| Stage | Delivers | Depends on | Priority | Prompt |
| --- | --- | --- | --- | --- |
| [0](prompts/stage-00.md) | Shell, docs, rules, tokens, auth starter | Nothing. Already scaffolded. Still in progress until you accept it | Foundation | [stage-00.md](prompts/stage-00.md) |
| [1](prompts/stage-01.md) | Schema, models, factories, seed data | Stage 0 accepted, and Aiven credentials in `.env` | Foundation | [stage-01.md](prompts/stage-01.md) |
| [2](prompts/stage-02.md) | Roles, policies, password policy, lockout, session, password history | Stage 1 | P0 | [stage-02.md](prompts/stage-02.md) |
| [3](prompts/stage-03.md) | Accounts and contacts | Stage 2 | P0 | [stage-03.md](prompts/stage-03.md) |
| [4](prompts/stage-04.md) | Leads and conversion | Stage 3 | P0 list and detail; conversion is P1 | [stage-04.md](prompts/stage-04.md) |
| [5](prompts/stage-05.md) | Opportunities and stages | Stage 4 | P0 | [stage-05.md](prompts/stage-05.md) |
| [6](prompts/stage-06.md) | Cases | Stage 5 | P0 | [stage-06.md](prompts/stage-06.md) |
| [7](prompts/stage-07.md) | Global search and recent records | Stage 6 | P0 | [stage-07.md](prompts/stage-07.md) |
| [8](prompts/stage-08.md) | Tasks, events, calendar | Stage 7 | P1 | [stage-08.md](prompts/stage-08.md) |
| [9](prompts/stage-09.md) | Home dashboard | Stage 8 | P0 | [stage-09.md](prompts/stage-09.md) |
| [10](prompts/stage-10.md) | Pre-built reports and the report builder | Stage 9 | P0 reports, then P1 builder | [stage-10.md](prompts/stage-10.md) |
| [11](prompts/stage-11.md) | Dashboards, CSV import/export, notification email | Stage 10 | P1 | [stage-11.md](prompts/stage-11.md) |
| [12](prompts/stage-12.md) | JSON API, Docker for the app and local MySQL, deployment docs, three end-to-end journeys | Stage 11 | Close-out | [stage-12.md](prompts/stage-12.md) |

## Why this order

The database shape comes before screens, so later pages sit on real tables. Sign-in and roles come before customer records, so the first saved account already has an owner and a permission check.

Accounts and contacts come before leads and deals, because a deal and a converted lead both need a company and a person to attach to. Search comes after those records exist. Tasks come before the home page, because the home page lists today's tasks and events. Reports come before dashboards, because a dashboard embeds a saved report. Mail and CSV import come after the records they write to. The public API comes last, and it uses the same rules as the pages.

## Definition of done

A stage is done when all of these are true:

- The acceptance list in that stage's prompt is true.
- The Pest tests for that stage pass.
- Pint is clean.
- You have clicked through the manual checks in the prompt file.
- You have accepted the work. Only then does **Current stage** in the README move forward.

## Rules for every stage

- Validate with Form Requests. Authorize with Policies. Each write goes through one Action class.
- A write that touches more than one record uses a database transaction.
- Eager-load relationships a page shows. Paginate lists at 25 rows, with a maximum of 200.
- Do not commit secrets, `.env`, or the Aiven CA file.
- Seed data stays fake. Do not load real customer data.
- Do not use the Salesforce name, logo, or font files.
- Do not put hex colors or inline font styles in Vue files. Use the tokens in `resources/css/app.css`.

## What not to do

- Do not build a later stage's features inside an earlier prompt.
- Do not start with charts. They need records from earlier stages.
- Do not invent objects the current stage does not name.
- Do not hide unauthorized records only in the Vue page. The server must refuse them.
- Do not connect production mail or file storage before the stage that needs them. Mail waits until Stage 11. File uploads are not in stages 0 through 12.

## Where things live

| Path | What it is |
| --- | --- |
| [README.md](../README.md) | Product summary, scope, and current stage |
| [ABOUT.md](../ABOUT.md) | Records, roles, pipeline, and screen layout |
| [docs/SETUP.md](SETUP.md) | Local install and Aiven steps |
| [docs/TEAM_GUIDE.md](TEAM_GUIDE.md) | How the two of you run the build |
| [docs/prompts/stage-00.md](prompts/stage-00.md) through [stage-12.md](prompts/stage-12.md) | Copy-paste prompts and manual steps |
| `.cursor/rules/` | Rules agents must follow |

## Stage 0 on 28 Sep 2026

The scaffold is in the repo. 28 Pest tests passed, and Pint was clean. Aiven is not connected yet. Register, login, logout, Home, and About have not been clicked through in a browser.

Before Stage 1, enable `pdo_mysql`, fill `.env` with the Aiven host, port, database, user, and password, save the CA file, set `MYSQL_ATTR_SSL_CA` to its absolute path, run `php artisan migrate`, and check register, login, logout, Home, and About in a browser. The steps are in [stage-00.md](prompts/stage-00.md).
