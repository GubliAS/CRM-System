# Stage 12 — API and release shape

Run this only after you accept Stage 11. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 12 only. Do not add P2 or P3 features. Do not change Current stage to complete until the user accepts the project.

Add a JSON API at /api/v1 with CRUD for leads, accounts, contacts, opportunities, cases, tasks, and events. Use the same Policies and Form Requests as the web app. Authenticate with Laravel Sanctum personal access tokens. Rate-limit the API. Publish an OpenAPI file and document it in docs/API.md.

Add a Dockerfile and a compose file for the app and a local MySQL server. Do not use PostgreSQL. Expand docs/SETUP.md with Aiven MySQL, mail, the queue worker, migrate, and rollback. Rollback means redeploy the previous release and reverse a migration only when that migration is backward compatible.

Add end-to-end coverage for three journeys: convert a lead, move an opportunity to Closed Won, and close a case then reopen it.

Keep the app database as MySQL. CI may keep sqlite :memory: for the existing Pest suite. Do not put secrets in the image or in git.

Read README.md and .cursor/rules before writing code. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

1. Create a second Aiven MySQL database, or a second Aiven service, for production. Do not point production at the database you develop against.
2. Put the production mail key on the host, not in git. Resend is the recommended provider. Postmark and Amazon SES are the alternatives.
3. Choose a PHP 8.3 host with HTTPS and a queue worker. Practical options are Laravel Forge on a small VPS, Ploi, or Railway.
4. Set `MYSQL_ATTR_SSL_CA` on that host to the Aiven CA, using an absolute path.
5. Run the queue worker as a long-lived process: `php artisan queue:work --tries=3`.
6. Rely on Aiven's own backups for MySQL. Do not promise 99.5% uptime, geo-redundant backups, or a penetration test unless the school provides that infrastructure.

## Suggested commit message

```text
Add the JSON API, Docker setup, and release docs.
```
