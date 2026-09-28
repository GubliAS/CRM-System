# Stage 0 — Shell

This prompt was already run on 28 Sep 2026. The scaffold is in the repo. Stage 0 is still in progress. Do not run the prompt again unless you are repairing the shell. The manual steps below are what you still need to do.

## Prompt

```text
Stage 0 only. This stage creates the repo foundation and the app shell. Do not start Stage 1.

Write these files first, then scaffold the app around them:

1. README.md
   Product summary, tech stack, scope (P0–P3), the 0–12 build stages, coding standards, Aiven MySQL setup, and local commands.
   Current stage: 0 — in progress.
   The About section is a short summary plus a link to ABOUT.md.
   State that agents never commit; after a stage they remind the team and give a one-line message.

2. ABOUT.md
   The full About document: what the CRM is, sales vs service, every record (Lead, Account, Contact, Opportunity, Case, Task, Event) and how they relate, pipeline stages and probabilities, the five roles and the sharing rules, what will not be built, and how list, detail, and form screens are organized.
   The in-app About page must show this same content.

3. .cursor/rules
   - crm-core.mdc (always apply): only the stage named in the prompt; no campaigns, products, quotes, recurring events, AI, native apps, workflow builder, custom objects, or Gmail/Outlook sync; MySQL on Aiven only; Policies, Form Requests, and one Action per write; tokens from the global CSS file; never commit; remind the user with a one-line commit message when the stage is ready.
   - crm-stage.mdc (always apply): do not start the next stage; do not add dependencies the stage does not need.
   - crm-laravel.mdc (PHP files): Laravel 11, artisan generators, migrations only, eager load, paginate, config() not env() outside config files.
   - crm-vue.mdc (Vue files): script setup, Inertia Link and useForm, @/ alias, no hex and no inline color or font styles.

Then create the Laravel 11 app in this repository with Breeze (Inertia + Vue 3), Tailwind, Pest, and Pint.

Configure MySQL for the existing Aiven service. Read MYSQL_ATTR_SSL_CA in config/database.php. Put safe placeholders in .env.example. Gitignore .env and storage/certs/. Do not commit secrets or the CA file.

In resources/css/app.css define :root tokens and map them into the Tailwind theme:
primary #032d60, secondary #0176d3, background #f3f3f3, surface #ffffff, text #181818, plus muted text, border, success, danger, warning, and info.
Font stack: Arial, Helvetica, sans-serif.
Type scale: h1 1.5rem, h2 1.25rem, h3 1rem, body 0.875rem, small 0.75rem, line-height 1.5.
Vue files use only those token classes.

Restyle the Breeze auth screens with the tokens. Add the app shell: header, primary tabs (Home, Leads, Accounts, Contacts, Opportunities, Cases, Tasks, Calendar, Reports, Dashboards), and an About page whose copy matches ABOUT.md. Tabs other than Home and About can be disabled placeholders.

Add docs/SETUP.md with the Aiven .env steps, the CA download, and the local commands. Add a GitHub Actions workflow that runs Pint and Pest.

Do not add CRM tables, roles, or business CRUD. Do not change Current stage to complete. Do not commit.

Done when: README.md, ABOUT.md, and the four rule files exist; the app boots on MySQL; register, login, and logout work; About matches ABOUT.md; the shell uses token classes; CI exists; php artisan test passes.

Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

1. Uncomment `extension=pdo_mysql` in `C:\php\php.ini`. Open a new terminal and confirm `php -m` lists `pdo_mysql`.
2. In the Aiven MySQL service, copy the host, the port (not 3306), the database name, the user, and the password into `.env` only.
3. Download the CA certificate and save it as `storage/certs/aiven-ca.pem`. Set `MYSQL_ATTR_SSL_CA` to that file's absolute path.
4. Run `php artisan migrate`.
5. In the browser, register a user, log in, log out, and open Home and About. Confirm About matches `ABOUT.md`.

If the connection times out, allow your current public IP on the Aiven service.

## Suggested commit message

```text
Scaffold the CRM app, docs, rules, and design tokens.
```
