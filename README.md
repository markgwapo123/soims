# SOIMS

Supply Office Inventory Management System for West Visayas State University - Himamaylan City Campus.

## Foundation

- Laravel 12
- PHP 8.2+
- MySQL/MariaDB
- Laravel Blade, HTML, CSS, and JavaScript
- Apache/XAMPP-compatible local development

## Local Setup

1. Confirm PHP and Composer are available.
2. Copy `.env.example` to `.env` if a local environment file does not exist.
3. Configure local database credentials in `.env`.
4. Generate the application key with `php artisan key:generate`.
5. Run the development server with `php artisan serve`.
6. Open `http://127.0.0.1:8000`.

The current local database target is `soims`. The database may exist without tables until the approved ERD is implemented through migrations.

## Verification

```powershell
php artisan about
php artisan route:list
php artisan test
composer validate --no-check-publish
```

## Development Phases

The project is developed phase by phase. Database tables, authentication, authorization, inventory workflows, and other SOIMS modules must not be added until their approved phase.

Current foundation documentation:

- `docs/phase-1-requirements-architecture.md`
- `docs/phase-2-erd.md`

Do not commit `.env` or local credentials. Do not run migrations against a shared or production database without explicit approval.
