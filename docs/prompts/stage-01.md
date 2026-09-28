# Stage 1 — Schema

Run this only after you accept Stage 0. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 1 only. Do not start Stage 2. Do not build CRUD pages.

Add the CRM schema on the existing Aiven MySQL database. Document MySQL types in docs/SCHEMA.md. Do not add another database driver.

Tables: users (extend), roles, leads, accounts, contacts, opportunities, cases, tasks, events, notes, and an activity log.

Include owner, created_by, and updated_by, plus the foreign keys from the SRS data section. An account may have a parent account. A contact may report to another contact. Tasks and events use a polymorphic related record. Events cannot relate to a case. Opportunities store stage, probability, is_closed, and is_won. Deleting an opportunity sets archived_at and does not remove the row.

Index foreign keys, owner_id, status, and stage.

Add models, factories, and a seeder with obvious fake companies only. No real customer data.

Pest tests assert the relationships and the calculated opportunity flags (is_closed, is_won, expected revenue = amount × probability).

Read README.md and .cursor/rules before writing code. Authorize later stages in Policies; this stage has no pages. Use migrations as the only schema change. Use config() outside config files. Tokens stay in resources/css/app.css.

Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

1. Confirm `.env` still points at the Aiven MySQL service: host, non-3306 port, database, user, password, and an absolute `MYSQL_ATTR_SSL_CA`.
2. Review the new migration files before they run.
3. Run `php artisan migrate --seed`.

No new Aiven service is required. Tests keep using sqlite `:memory:` and do not need your password.

## Suggested commit message

```text
Add the CRM schema, models, factories, and seed data.
```
