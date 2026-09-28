# Stage 3 — Accounts and contacts

Run this only after you accept Stage 2. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 3 only. Do not start Stage 4. No leads, opportunities, or cases.

Build account and contact list, create, edit, and detail pages.

Account list columns: name, phone, type, industry, revenue, owner. Contact list columns: name, account, title, phone, email, owner. Detail pages show the SRS sections and related lists that already have data (contacts on an account).

Enforce Policies. Required fields match the SRS: account name; contact last name and account. Forms use Save, Save & New, and Cancel. Confirm before delete. Desktop forms are two columns. Required fields show a red asterisk. Use token classes from resources/css/app.css only. No hex and no inline color or font styles.

Validation lives in Form Requests. Writes go through one Action class each. Eager-load relationships the page displays. Paginate lists at 25, maximum 200.

Feature tests: a rep creates an account and a contact, another rep cannot see them, a manager can, and validation errors return inline beside the fields.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

Log in as the seeded sales rep and as the sales manager. Create an account and a contact as the rep. Confirm the other rep cannot open them and the manager can. No new accounts or services are required.

## Suggested commit message

```text
Add account and contact management.
```
