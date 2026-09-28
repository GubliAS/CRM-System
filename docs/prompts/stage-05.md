# Stage 5 — Opportunities

Run this only after you accept Stage 4. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 5 only. Do not start Stage 6. No dashboard charts.

Build the opportunity list, create, edit, detail, clone, change owner, and archive.

Stages and probabilities: Qualification 10%, Meeting Scheduled 20%, Proposal/Price Quote 65%, Negotiation/Review 80%, Closed Won 100%, Closed Lost 0%. Changing the stage updates probability. The detail page shows a stage path and expected revenue (amount × probability). Store is_closed and is_won from the stage. Store stage history.

Required fields: opportunity name, account, close date, and stage. Close date cannot be in the past. Amount, if entered, must be positive. Probability stays between 0 and 100.

Archive sets archived_at after a confirmation dialog. It does not delete the row. The list hides archived rows unless a filter asks for them. Clone copies the opportunity and can include related records when the user asks.

Use Policies, Form Requests, and one Action class per write. Use token classes only. Eager-load the account. Paginate the list.

Tests cover the probability update, a past close date, and archive.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

Move one deal from Qualification to Closed Won. Confirm the probability is 100 and expected revenue equals the amount.

## Suggested commit message

```text
Add opportunities and sales stages.
```
