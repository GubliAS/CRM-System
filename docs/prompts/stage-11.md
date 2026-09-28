# Stage 11 — Dashboards, import, and mail

Run this only after you accept Stage 10. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 11 only. Do not start Stage 12. Do not add dashboard auto-refresh. Do not add file uploads. Do not track email opens or clicks.

Dashboards: create, view, clone, and delete. A dashboard holds up to 20 widgets sourced from saved reports. Widget types: chart, table, metric, and gauge. Global filters for date range and owner apply to every widget on the dashboard. Folders: Recent, Created by Me, Private Dashboards, and All Dashboards.

CSV import for leads, accounts, contacts, and opportunities. The user maps columns, each row is validated, and failed rows are listed in a downloadable error report and are not inserted. Update-or-insert is an explicit choice, not the default. Export the current list view to CSV.

Send email when a task is assigned, when a record owner changes, and for password reset if that path still only writes to the log. Log the email on the related record. Send through a queued job on the database queue.

Tests: a bad CSV row is reported and not inserted, and assigning a task queues a mailable.

Use Policies, Form Requests, and one Action class per write. Use token classes only. Do not store uploaded files.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

- Pick Resend (recommended), Postmark, or Amazon SES. Put the API key only in `.env` or the host environment. Do not use Gmail as the production mailer.
- Keep Mailpit, or the `log` driver, on your own machine so tests do not email real people.
- Import a small CSV you wrote yourselves. Do not import real customer data.
- Do not set up file storage. This stage does not upload attachments.

## Suggested commit message

```text
Add dashboards, CSV import, and notification email.
```
