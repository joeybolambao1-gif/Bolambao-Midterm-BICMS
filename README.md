# BICMS - Barangay Information and Community Management System

A PHP and MySQL application for resident records, barangay document previews, blotter records, account sessions, and database transactions.

## Requirements

- PHP 7.4 or newer with PDO MySQL enabled
- MySQL or MariaDB
- Apache or another PHP-capable web server

## Local setup with XAMPP

1. Copy this project folder into `htdocs`.
2. Create a MySQL database named `bicms_db` in phpMyAdmin.
3. Open `http://localhost/Bolambao.Midterm/`.
4. Register an account, then sign in.

The default local database connection is `localhost`, database `bicms_db`, user `root`, with an empty password. Override these values with `BICMS_DB_HOST`, `BICMS_DB_NAME`, `BICMS_DB_USER`, and `BICMS_DB_PASSWORD`, or create `classes/database.local.php` using `classes/database.example.php` as a template. The local configuration file is ignored by Git; do not upload credentials to a public repository.

## Sessions

The application uses PHP sessions for sign-in state and CSRF tokens. Session IDs are regenerated after login/account creation, protected backend actions require an authenticated session, and signing out destroys the session.

## Database transactions

Writes use `Database::runTransaction()` to begin a PDO transaction, commit successful queries, and roll back on failures. The application uses this for:

1. Adding a resident
2. Updating a resident
3. Deleting a resident
4. Adding a blotter record

## Deployment

Upload the application files to the hosting provider's PHP document root, create a MySQL database there, then create `classes/database.local.php` on the server with that provider's database host, database name, username, and password. Keep this file private and never commit it.
