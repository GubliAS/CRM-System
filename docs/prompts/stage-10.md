# Stage 10 — Reports

Run this only after you accept Stage 9. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 10 only. Do not start Stage 11. Do not add report subscriptions.

Build the report list, the pre-built reports below, a run view, and a report builder.

Folders: Recent, Created by Me, Private Reports, Public Reports, and All Reports. The list shows report name, description, folder, created by, and created on. Users can search by name or description.

Pre-built reports:
- Leads by Source This FY
- Leads Converted This FY
- Leads Created by Month
- New Leads This FY By Owner
- Conversion of New Leads This FY
- All Pipeline — Current Year
- Potential Revenue Source — Current Year
- Avg Deal Size — Current FY
- Avg. Deal Length
- Closed (Won) Opportunities This FY
- Closed Won Opportunities by Owner
- Average Case Age
- Monthly Case Volume by Origin

Builder steps: choose the object, choose columns, add filters (field, operator, value), group by one or more fields, add a chart, preview, then save with a name and description into a folder.

The run view shows a table, sortable columns, the record count, and the chart when one is configured. Clicking a row opens the record. The user can export CSV, Excel, and PDF.

A private report is hidden from another user. Tests: a private report is hidden from another user, and a filter changes the row count.

Use Policies so report visibility matches the folder and the underlying records. Use token classes only. Paginate large results, maximum 200 rows per page.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

Run "All Pipeline — Current Year" and compare its total with the home funnel for the same year. The totals should match.

## Suggested commit message

```text
Add pre-built reports and the report builder.
```
