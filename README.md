# Monochrome News Flash - Laravel Backend API

This repository contains the Laravel 12 backend API for the Monochrome News Flash application. It provides authentication, news management, categories, tags, subscriptions, payments, AI-assisted article generation, and role-based access control.

## Features

- RESTful API for news management
- Category and tag management
- JWT-based authentication
- User, role, and permission management
- Subscription and Stripe checkout support
- AI article generation endpoints
- CORS configuration for React / Next.js clients
- Seeded demo data for local testing

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL
- Laragon, XAMPP, or MAMP for local PHP/MySQL setup (recommended)

## Automated Setup

The project includes setup scripts for Windows and Linux/macOS. These scripts copy `.env`, install Composer dependencies, and generate the Laravel application key.

### Windows (PowerShell)

1. Open PowerShell or the VS Code terminal.
2. Change into the backend project directory.
3. Run:

```powershell
.\setup.ps1
```

If you hit an execution policy error, run this first:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
```

### Linux / macOS (Bash)

1. Open a terminal.
2. Change into the backend project directory.
3. Make the script executable and run it:

```bash
chmod +x setup.sh
./setup.sh
```

## Database Configuration

After the setup script finishes, configure MySQL and run migrations.

### 1. Update `.env`

Open `.env` in the backend root and fill in your MySQL settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monochrome_news
DB_USERNAME=root
DB_PASSWORD=
```

Leave `DB_PASSWORD` empty if you are using a default XAMPP or Laragon setup.

### 2. Create the Database

Create an empty database in MySQL:

```sql
CREATE DATABASE monochrome_news CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Run Migrations and Seeders

Run the following command to create the schema and seed demo data:

```bash
php artisan migrate:fresh --seed
```

## Run the Server

Start the Laravel development server:

```bash
php artisan serve
```

The API will be available at:

```text
http://localhost:8000
```

## Verify the API

Open your browser or Postman and request:

```text
http://localhost:8000/api/health
```

Expected response:

```json
{
  "status": "ok",
  "message": "API is running",
  "timestamp": "2025-11-06T10:30:00.000000Z"
}
```

## Useful Commands

```bash
# List all registered routes
php artisan route:list

# Clear caches after changing .env or config
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Rebuild the database with fresh demo data
php artisan migrate:fresh --seed
```

## Seeded Demo Data

The project includes demo data for development and testing:

- Primary admin account: `admin@example.com`
- Editor account: `marie.laurent@example.com`
- Shared password for seeded accounts: `password`
- Seeded categories include Technology, Economy, Environment, Sports, Culture, and Politics
- Seeded news articles are distributed across the available categories

## Project Notes

- Stripe webhook endpoint: `/api/webhook/stripe`
- Authenticated API endpoints are grouped under `/api/v1`
- A public demo users endpoint is still exposed separately for the teacher demo flow

## Troubleshooting

If you run into HTTP 500 errors or failed queries:

1. Make sure your MySQL service is running.
2. Check the Laravel log at `storage/logs/laravel.log`.
3. Clear config cache after changing `.env`:

```bash
php artisan config:clear
```
