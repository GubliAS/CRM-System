# Stage 9 — Home

Run this only after you accept Stage 8. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 9 only. Do not start Stage 10. Do not add a report builder.

Build the home dashboard for the signed-in user. Use token classes only. No hex and no inline color or font styles.

Widgets:
- Pipeline funnel for the current year: count and value by stage (Qualification, Meeting Scheduled, Proposal/Price Quote, Negotiation/Review, Closed Won, Closed Lost). Clicking a stage filters the opportunity list. Show the total pipeline value.
- Revenue by lead source for the same period.
- Tasks due today, with a complete action, and a clear empty message when none are due.
- Today's events, with a clear empty message when none are scheduled.
- Key open opportunities: name, account, amount, close date, and stage, each linking to the record.
- Two assistant rules only: accounts with no activity for 30 days, and opportunities near their close date with no recent update. The user can dismiss a recommendation, and it stays dismissed for that user. Each recommendation links to the record.

Tests cover an empty task list and that a dismissed recommendation stays dismissed.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

Open Home as a sales rep who has seed data. Follow each widget through to a real record. Confirm an empty task day shows the empty message.

## Suggested commit message

```text
Add the home dashboard.
```
