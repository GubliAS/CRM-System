# Local setup

Stage 0 shell. Agents do not commit. When a stage is ready, a person on the team commits.

## Tools

- PHP 8.3 or newer. Laravel 11 itself allows 8.2, but Breeze 2.4 installs Pest 4 and PHPUnit 12, and those require 8.3. CI uses PHP 8.3 for that reason.
- Composer
- Node.js 20 (Node 22 is fine locally)
- npm

Enable these PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `fileinfo`. `pdo_sqlite` is required for the test suite. On this machine `pdo_mysql` may be commented out in `php.ini` (`extension=pdo_mysql`). Uncomment it and restart the terminal before `php artisan migrate`.

Composer 2.10 blocks published Laravel 11 security advisories. `composer.json` sets `config.policy.advisories.block` to `false` so `composer install` can finish on Laravel 11. Do not move the app to Laravel 12 unless a later stage says so.

## Install

From the repository root:

```powershell
composer install
copy .env.example .env
php artisan key:generate
npm install
npm run dev
```

In a second terminal:

```powershell
php artisan migrate
php artisan serve
```

`npm run dev` serves Vite. `php artisan serve` serves Laravel (default `http://127.0.0.1:8000`).

## Aiven MySQL

The application database is MySQL on Aiven with SSL. Do not point the app at PostgreSQL, Neon, or SQLite.

1. In Aiven, copy the host, the port (it is not 3306), the database name, the user, and the password.
2. Download the CA certificate and save it as `storage/certs/aiven-ca.pem`. `storage/certs/` is gitignored. Do not commit the certificate.
3. Edit `.env` (never `.env.example`) and set:

```dotenv
DB_CONNECTION=mysql
DB_HOST=your-aiven-host
DB_PORT=your-aiven-port
DB_DATABASE=your-database
DB_USERNAME=your-user
DB_PASSWORD=your-password
MYSQL_ATTR_SSL_CA=C:\absolute\path\to\storage\certs\aiven-ca.pem
```

`MYSQL_ATTR_SSL_CA` must be an absolute path. PDO on Windows often ignores a relative path. `config/database.php` passes that value to `PDO::MYSQL_ATTR_SSL_CA`.

4. Confirm `pdo_mysql` is loaded (`php -m`).
5. Run `php artisan migrate`.
6. If the connection times out, add your current public IP to the Aiven allowlist and retry.

Do not invent a password. If `.env` has no real Aiven password, leave the database unset and use sqlite only for tests.

## Tests and formatting

```powershell
php artisan test
vendor/bin/pint
```

CI (`.github/workflows/ci.yml`) runs `npm ci`, `npm run build`, `vendor/bin/pint --test`, and `php artisan test`.

`phpunit.xml` forces `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`. CI does not need Aiven secrets, the CA file, or a MySQL server. The application itself still uses MySQL when you run it locally.
