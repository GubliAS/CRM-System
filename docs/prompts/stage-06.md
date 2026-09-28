# Stage 6 — Cases

Run this only after you accept Stage 5. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 6 only. Do not start Stage 7.

Build the case list, create, detail, status changes, close, and reopen.

Generate a unique case number. Statuses: New, Working, Escalated, Closed. Origin is required (Phone, Email, Web, Chat). Priority colors use tokens: high uses danger, medium uses warning, low uses success. Do not put hex values in Vue.

List filters: My Open Cases, All Open Cases, and Recently Closed Cases. Closed cases are read-only until Reopen. Choosing a contact may fill the account from that contact. Subject, description, type, reason, and priority follow the SRS and may be optional.

Use Policies, Form Requests, and one Action class per write. A sales rep who is not allowed cannot update a case, including by guessing the URL. Use token classes only. Paginate the list.

Tests: closing a case locks edits, reopen allows edits, and a disallowed sales rep cannot update a case.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

Open a case, close it, and confirm the form is locked. Reopen it and edit the subject.

## Suggested commit message

```text
Add case management and the close workflow.
```
