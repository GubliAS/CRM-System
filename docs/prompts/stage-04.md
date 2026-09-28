# Stage 4 — Leads and conversion

Run this only after you accept Stage 3. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 4 only. Do not start Stage 5. No pipeline charts.

Build the lead list, create, edit, detail, status changes, and change owner.

Statuses: New, Working, Nurturing, Qualified, Unqualified, Converted. Required fields: last name, company, and status (default New).

Convert opens a dialog. The user can match an existing account by company name or create one. Conversion always creates the contact. The user may create an opportunity, and the opportunity name is required when they do. A checkbox moves open tasks and events onto the new records. The whole conversion runs in one database transaction. After conversion the lead is read-only and stores the new account, contact, and opportunity ids. Record the owner change in the activity log. Changing owner can optionally transfer related open tasks and events.

Tests: conversion creates the right rows, a failed opportunity validation rolls back every row, and a converted lead cannot be edited.

Use Policies, Form Requests, and one Action class for the conversion write. Use token classes only. Eager-load related records. Paginate the list.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

Convert one seeded lead in the browser. Confirm the account, contact, and optional opportunity exist, and confirm the lead no longer saves edits.

## Suggested commit message

```text
Add leads and the conversion workflow.
```
