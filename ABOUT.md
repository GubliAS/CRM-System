# About this CRM

A CRM (Customer Relationship Management system) is the shared record of people and companies a business sells to and supports. It keeps the sales pipeline and the support queue in one place so a conversation, a deal, and a case can all point at the same customer.

## Sales vs service

Sales and service use the same accounts and contacts, then split by the work they do. Sales tracks who might buy and what the deal is worth. Service tracks problems after the customer is known. Tasks and calendar events hang off either kind of work.

## Records and relationships

**Lead.** A person who is not yet a customer. Last name, company, and status are required. Status is one of: New, Working, Nurturing, Qualified, Unqualified, Converted.

**Account.** A company or organization. Name is required. An account may optionally belong to a parent account.

**Contact.** A person at an account. Last name and account are required. A contact may optionally report to another contact.

**Opportunity.** A deal. Name, account, close date, and stage are required. Stage is one of the pipeline stages below.

**Case.** A support issue. Status and origin are required. The system generates a case number. Status is one of: New, Working, Escalated, Closed.

**Task.** A to-do. Subject is required. A task can relate to any record type, including a case.

**Event.** A calendar item. Subject, start, and end are required. An event can relate to any record type except a case. Events may be all-day. This version has no recurrence.

## How they connect

A lead converts into an account, a contact, and an optional opportunity. An account has contacts, opportunities, and cases. Tasks and events hang off those records. A converted lead is read-only. A closed case is read-only. Deleting an opportunity archives it instead of removing the row.

## Pipeline and expected revenue

| Stage | Probability |
| --- | --- |
| Qualification | 10% |
| Meeting Scheduled | 20% |
| Proposal/Price Quote | 65% |
| Negotiation/Review | 80% |
| Closed Won | 100% |
| Closed Lost | 0% |

Expected revenue = amount × probability.

## Roles and sharing

| Role | Access |
| --- | --- |
| Administrator | Everything |
| Sales Manager | All sales records |
| Sales Representative | Own records |
| Service Representative | All cases, read accounts and contacts, own tasks |
| Read-only | Read everything, write nothing |

## What will not be built

P2 ships only if a later stage is explicitly added: report subscriptions, dashboard auto-refresh, account hierarchy visualization, advanced search, attachment preview, and MFA.

P3 will not be built: native apps, a workflow builder, AI, custom objects, an integration marketplace, and Gmail/Outlook sync.

Also out of scope: campaigns, products, quotes, recurring events, and an AI assistant.

Home suggestions, when that stage arrives, are two rules only: accounts inactive for 30 days, and opportunities near their close date with no recent update.

## How the interface is organized

The header shows the logo, search, notifications, and the profile menu.

Tabs: Home, Leads, Accounts, Contacts, Opportunities, Cases, Tasks, Calendar, Reports, Dashboards.

Lists support sort, search, and pagination up to 200 rows, plus bulk actions where a stage requires them.

A detail page offers Edit, Delete or Archive, and Change Owner, then related lists.

Forms use two columns on desktop and one column on mobile. Required fields show an asterisk. Actions are Save, Save & New, and Cancel. Destructive actions ask for confirmation.

## Visual direction

Color, type, and spacing come from design tokens in the application stylesheet. The product does not use Salesforce branding. It does not use the Salesforce name, logo, or a proprietary Salesforce font.
