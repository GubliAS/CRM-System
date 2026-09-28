# Stage 2 — Access control

Run this only after you accept Stage 1. Paste the prompt below in Agent mode.

## Prompt

```text
Stage 2 only. Do not start Stage 3. Do not build CRM CRUD pages.

Harden auth to the SRS and enforce the README sharing model on the server.

Password rule: minimum 8 characters, with an uppercase letter, a lowercase letter, and a number. Lock further login attempts after 5 failures. End the session after 120 idle minutes. Remember-me lasts 30 days. The password-reset token expires in 60 minutes. Store the last 5 password hashes and reject reuse.

Seed the five roles: Administrator, Sales Manager, Sales Representative, Service Representative, and Read-only.

Policies implement this matrix:
- Administrator: everything.
- Sales Manager: all sales records.
- Sales Representative: records they own.
- Service Representative: all cases, read accounts and contacts, own tasks.
- Read-only: read, never write.

A sales rep cannot open another rep's lead by guessing the URL. Feature tests cover login lockout, password reuse, and one denial per role.

Wire the reset flow to the mail driver already in .env. Keep development mail on the log driver or Mailpit. Do not add a production mail provider.

Passwords use bcrypt. Validation stays in Form Requests. Authorization stays in Policies. Do not enforce this only in Vue.

Read README.md and .cursor/rules before writing code. Do not change Current stage in README.md. Do not commit. When you finish, remind us to commit and give a one-line commit message.
```

## You do this by hand

- Leave `MAIL_MAILER=log` and read reset messages in `storage/logs/laravel.log`, or point SMTP at Mailpit on `127.0.0.1:1025`.
- Do not create a Resend, Postmark, SES, or Gmail account yet.
- Use the seeded administrator the agent documents. Do not put that password in git.

## Suggested commit message

```text
Enforce roles, password policy, and record sharing.
```
