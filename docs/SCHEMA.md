# CRM schema

The running app uses MySQL on Aiven. Pest uses sqlite `:memory:` only. Do not point the app at sqlite, PostgreSQL, or another driver.

Picklists are `VARCHAR`, not MySQL `ENUM`, so the same migrations run on sqlite. Allowed values are enforced in application code where this stage owns the rule (opportunity stage, case closure, event related types). Later stages validate the rest in form requests.

`Case` is a PHP reserved word, so the Eloquent model is `App\Models\SupportCase` and the table is `cases`. `related_type` and `notable_type` store the model class name, for example `App\Models\Lead`.

## Shared columns

Every business table except `roles` and `activity_log` has:

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | no | Primary key, auto-increment |
| owner_id | BIGINT UNSIGNED | yes | FK `users.id`, `ON DELETE SET NULL`, indexed |
| created_by | BIGINT UNSIGNED | yes | FK `users.id`, `ON DELETE SET NULL`, indexed |
| updated_by | BIGINT UNSIGNED | yes | FK `users.id`, `ON DELETE SET NULL`, indexed |
| created_at | TIMESTAMP | yes | Laravel timestamps |
| updated_at | TIMESTAMP | yes | Laravel timestamps |

`roles` is reference data and has no owner columns. `activity_log` records the actor in `user_id` instead of `owner_id`.

String columns below are `VARCHAR(n)`. Unspecified string length is `VARCHAR(255)`. `TEXT` is MySQL `TEXT`. Booleans are `TINYINT(1)`.

## users

Breeze columns are unchanged. This stage adds:

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| role_id | BIGINT UNSIGNED | yes | FK `roles.id`, `ON DELETE SET NULL`, indexed |

## roles

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | no | Primary key |
| name | VARCHAR(255) | no | Unique |
| slug | VARCHAR(255) | no | Unique |
| created_at, updated_at | TIMESTAMP | yes | |

Seeded rows (reference data, not authorization): Administrator `admin`, Sales Manager `sales-manager`, Sales Representative `sales-rep`, Service Representative `service-rep`, Read-only `read-only`. Stage 2 enforces them. There are no policies in this stage.

## accounts

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| name | VARCHAR(255) | no | |
| parent_account_id | BIGINT UNSIGNED | yes | Self FK `accounts.id`, `ON DELETE SET NULL`, indexed |
| phone, fax | VARCHAR(40) | yes | |
| website | VARCHAR(255) | yes | |
| type | VARCHAR(40) | yes | Customer, Prospect, Partner, Other |
| industry | VARCHAR(80) | yes | |
| employees | INT UNSIGNED | yes | |
| annual_revenue | DECIMAL(15,2) | yes | |
| billing_street, shipping_street | VARCHAR(255) | yes | |
| billing_city, billing_state, billing_postal_code, billing_country | VARCHAR(80) | yes | |
| shipping_city, shipping_state, shipping_postal_code, shipping_country | VARCHAR(80) | yes | |
| description | TEXT | yes | |

## contacts

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| account_id | BIGINT UNSIGNED | no | FK `accounts.id`, `ON DELETE RESTRICT`, indexed |
| salutation | VARCHAR(20) | yes | |
| first_name | VARCHAR(40) | yes | |
| last_name | VARCHAR(80) | no | |
| title | VARCHAR(128) | yes | |
| department | VARCHAR(80) | yes | |
| phone, mobile, home_phone, other_phone, fax, assistant_phone | VARCHAR(40) | yes | |
| email | VARCHAR(80) | yes | |
| reports_to_id | BIGINT UNSIGNED | yes | Self FK `contacts.id`, `ON DELETE SET NULL`, indexed |
| assistant | VARCHAR(80) | yes | |
| mailing_street, other_street | VARCHAR(255) | yes | |
| mailing_city, mailing_state, mailing_postal_code, mailing_country | VARCHAR(80) | yes | |
| other_city, other_state, other_postal_code, other_country | VARCHAR(80) | yes | |
| lead_source | VARCHAR(40) | yes | |
| birthdate | DATE | yes | |
| description | TEXT | yes | |

## leads

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| salutation | VARCHAR(20) | yes | |
| first_name | VARCHAR(40) | yes | |
| last_name | VARCHAR(80) | no | |
| company | VARCHAR(255) | no | |
| title | VARCHAR(128) | yes | |
| email | VARCHAR(80) | yes | |
| phone, mobile | VARCHAR(40) | yes | |
| lead_status | VARCHAR(40) | no | Default `New`. Indexed. New, Working, Nurturing, Qualified, Unqualified, Converted |
| lead_source | VARCHAR(40) | yes | |
| rating | VARCHAR(20) | yes | |
| industry | VARCHAR(80) | yes | |
| annual_revenue | DECIMAL(15,2) | yes | |
| number_of_employees | INT UNSIGNED | yes | |
| website | VARCHAR(255) | yes | |
| street | VARCHAR(255) | yes | |
| city, state, postal_code, country | VARCHAR(80) | yes | |
| description | TEXT | yes | |
| converted | TINYINT(1) | no | Default 0 |
| converted_account_id | BIGINT UNSIGNED | yes | FK `accounts.id`, `ON DELETE SET NULL`, indexed |
| converted_contact_id | BIGINT UNSIGNED | yes | FK `contacts.id`, `ON DELETE SET NULL`, indexed |
| converted_opportunity_id | BIGINT UNSIGNED | yes | FK `opportunities.id`, `ON DELETE SET NULL`, indexed |

## opportunities

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| name | VARCHAR(120) | no | |
| account_id | BIGINT UNSIGNED | no | FK `accounts.id`, `ON DELETE RESTRICT`, indexed |
| amount | DECIMAL(15,2) | yes | |
| close_date | DATE | no | |
| stage | VARCHAR(40) | no | Indexed |
| probability | TINYINT UNSIGNED | no | Default 10. Overwritten from stage on save |
| type | VARCHAR(40) | yes | New Business, Existing Business, Renewal |
| lead_source | VARCHAR(40) | yes | |
| next_step | VARCHAR(255) | yes | |
| description | TEXT | yes | |
| is_closed | TINYINT(1) | no | Default 0. Stored, synced from stage |
| is_won | TINYINT(1) | no | Default 0. Stored, synced from stage |
| archived_at | TIMESTAMP | yes | Set when an opportunity is archived. No soft deletes and no row delete in this stage |

Stage map lives in `app/Support/OpportunityStage.php`. The `Opportunity` saving hook writes probability and flags from that map:

| Stage | Probability | is_closed | is_won |
| --- | --- | --- | --- |
| Qualification | 10 | false | false |
| Meeting Scheduled | 20 | false | false |
| Proposal/Price Quote | 65 | false | false |
| Negotiation/Review | 80 | false | false |
| Closed Won | 100 | true | true |
| Closed Lost | 0 | true | false |

`expected_revenue` is not a column. The accessor returns `amount * probability / 100` as a two-decimal string, or null when `amount` is null. It is appended on the model.

There is no opportunity stage-history table. That belongs to Stage 5.

## cases

Origin is stored in `origin` (not `case_origin`).

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| case_number | VARCHAR(20) | yes | Unique. See below |
| contact_id | BIGINT UNSIGNED | yes | FK `contacts.id`, `ON DELETE SET NULL`, indexed |
| account_id | BIGINT UNSIGNED | yes | FK `accounts.id`, `ON DELETE SET NULL`, indexed |
| subject | VARCHAR(255) | yes | |
| description, internal_comments | TEXT | yes | |
| status | VARCHAR(40) | no | Default `New`. Indexed. New, Working, Escalated, Closed |
| priority | VARCHAR(20) | yes | High, Medium, Low |
| type | VARCHAR(40) | yes | Question, Problem, Feature Request |
| origin | VARCHAR(40) | no | Phone, Email, Web, Chat |
| reason | VARCHAR(80) | yes | |
| web_email, web_name | VARCHAR(80) | yes | |
| web_company | VARCHAR(255) | yes | |
| web_phone | VARCHAR(40) | yes | |
| is_closed | TINYINT(1) | no | Default 0. True only when status is Closed |
| closed_at | DATETIME | yes | Set when status becomes Closed, cleared otherwise |

The saving hook sets `is_closed` and `closed_at`. A later save that stays Closed keeps the existing `closed_at`.

### Case numbers

`case_number` is nullable only so the insert can happen before the id exists. MySQL and sqlite both allow multiple nulls in a unique index, so two inserts cannot collide on null.

`SupportCase` sets the number in the `created` event, after the database has assigned `id`, including inside an open transaction: `C-{100000 + id}` (id 1 is `C-100001`). It then `saveQuietly()`. The number is unique because the id is unique. Tests assert it is present and unique after `create()`.

## tasks

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| subject | VARCHAR(255) | no | |
| assigned_to_id | BIGINT UNSIGNED | yes | FK `users.id`, `ON DELETE SET NULL`, indexed |
| related_type | VARCHAR(255) | yes | Morph type. Composite index with `related_id` |
| related_id | BIGINT UNSIGNED | yes | Morph id |
| contact_id | BIGINT UNSIGNED | yes | FK `contacts.id`, `ON DELETE SET NULL`, indexed |
| due_on | DATE | yes | |
| status | VARCHAR(40) | no | Default `Not Started`. Indexed. Not Started, In Progress, Completed, Deferred |
| priority | VARCHAR(20) | yes | High, Normal, Low |
| comments | TEXT | yes | |
| reminder_set | TINYINT(1) | no | Default 0 |
| reminder_at | DATETIME | yes | |

Allowed `related_type` values: Account, Contact, Lead, Opportunity, Case (`SupportCase`). The saving hook rejects anything else.

## events

Same ownership, `assigned_to_id`, `related_type`, `related_id`, and `contact_id` rules as tasks, with one difference: a case is rejected. Allowed related models are Account, Contact, Lead, and Opportunity. The saving hook throws `InvalidArgumentException`.

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| subject | VARCHAR(255) | no | |
| starts_at, ends_at | DATETIME | no | |
| all_day | TINYINT(1) | no | Default 0 |
| location | VARCHAR(255) | yes | |
| show_time_as | VARCHAR(20) | no | Default `Busy`. Busy, Free, Out of Office |
| is_private | TINYINT(1) | no | Default 0 |
| description | TEXT | yes | |

No recurrence.

## notes

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| notable_type | VARCHAR(255) | no | Morph type. Composite index with `notable_id` |
| notable_id | BIGINT UNSIGNED | no | Morph id |
| body | TEXT | no | |

## activity_log

Write model only. No UI. Table name is `activity_log`.

| Column | MySQL type | Null | Notes |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | no | Primary key |
| subject_type | VARCHAR(255) | yes | Morph type. Composite index with `subject_id` |
| subject_id | BIGINT UNSIGNED | yes | Morph id |
| user_id | BIGINT UNSIGNED | yes | FK `users.id`, `ON DELETE SET NULL`, indexed |
| action | VARCHAR(80) | no | For example `created`, `owner_changed` |
| description | TEXT | yes | |
| properties | JSON | yes | sqlite tests store this as text |
| created_at, updated_at | TIMESTAMP | yes | |

## Indexes

- Primary key on every `id`.
- Unique: `roles.name`, `roles.slug`, `cases.case_number`, `users.email` (Breeze).
- Every foreign key column is indexed. `ON DELETE SET NULL` for nullable user, parent, reports-to, converted, case account/contact, and activity `user_id` keys. `ON DELETE RESTRICT` for `contacts.account_id` and `opportunities.account_id`.
- Status lookups: `leads.lead_status`, `cases.status`, `tasks.status`.
- `opportunities.stage`.
- Morph pairs: `tasks.related`, `events.related`, `notes.notable`, `activity_log.subject`.
