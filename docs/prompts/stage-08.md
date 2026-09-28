# Stage 8 — Tasks, events, and calendar

Run this only after you accept Stage 7. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 8 only. Do not start Stage 9. Do not send reminder email. That belongs to Stage 11. No recurring events.

Build tasks, events, and a calendar with day, week, month, and list views.

Task statuses: Not Started, In Progress, Completed, Deferred. Task priorities: High, Normal, Low. Subject is required. Assigned To defaults to the current user. Related To is a polymorphic lookup (account, contact, lead, opportunity, or case). Completing a task from the list is one click. Store reminder set, reminder date, and reminder time. Do not send the reminder.

Events require subject, start, and end. End must be after start. Support all-day events and a private flag. A private event is visible only to its owner. Drag an event to a new date and time. Clicking a day starts an event on that date. Related To may be an account, contact, lead, or opportunity, not a case.

Use Policies, Form Requests, and one Action class per write. Use token classes only. Eager-load the related record shown on the screen.

Tests: marking a task complete, a private event hidden from another user, and an end time before the start time is rejected.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

Nothing new to sign up for. Open the calendar and create one event so you can see the views. Reminder email stays off until Stage 11.

## Suggested commit message

```text
Add tasks, events, and the calendar.
```
