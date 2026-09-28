# Stage 7 — Search

Run this only after you accept Stage 6. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 7 only. Do not start Stage 8. Do not build the report builder.

Add global search in the header.

After 2 characters, show up to 5 matches per object for leads, accounts, contacts, opportunities, and cases. Return only rows the current user may see under the Stage 2 policies. A full results page can filter by object type.

Search fields:
- Leads: name, company, email, phone
- Accounts: account name, phone, website
- Contacts: name, email, phone, account name
- Opportunities: opportunity name, account name, amount
- Cases: case number, subject, description

Store the last few searches for that user. Record recently viewed records and use the last 5 as the default list on each object.

Tests: a rep does not see another rep's private lead in search results.

Use token classes only. No hex and no inline color or font styles. Paginate the full results page.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

Search for a company that another role must not see, while logged in as that other role. Confirm the private record is absent.

## Suggested commit message

```text
Add global search and recent records.
```
