# Laravel Backend Setup Guide

Complete guide to setting up the Basketball Spy Laravel backend for development and production.

## Prerequisites

Before you begin, ensure you have:
- **PHP 8.5 or higher** (PHP 8.5 recommended)
- **Composer** 2.9+
- **PostgreSQL** 15+ (or SQLite for local dev)
- **Git**
- **Node.js 20+** (for asset compilation, if needed)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/basketball-spy.git
cd basketball-spy/laravel
```

### 2. Install PHP Dependencies

```bash
composer install
```

This will install:
- Laravel 12 framework
- Laravel Sanctum (authentication)
- Required dependencies

### 3. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 4. Configure Database

Edit `.env` and set your database credentials:

**For PostgreSQL (Production):**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=basketball_spy
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**For SQLite (Local Development):**
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

Create the database:

**PostgreSQL:**
```bash
createdb basketball_spy
```

**SQLite:**
```bash
touch database/database.sqlite
```

### 5. Run Migrations

Run all database migrations:

```bash
php artisan migrate
```

This creates tables for:
- users
- organizations
- teams
- players
- reports
- personal_access_tokens (Sanctum)

### 6. Seed Database

Seed the database with teams and players:

```bash
php artisan db:seed
```

Or run migrations and seeds together:

```bash
php artisan migrate:fresh --seed
```

This populates:
- ✅ 30 NBA teams
- ✅ 12 WNBA teams
- ✅ 18 international teams
- ✅ 150 NBA players (5 per team)
- ✅ 1 test user

### 7. Download Player Images (Optional but Recommended)

Download player headshots to local storage:

```bash
php artisan players:fetch-images
```

This downloads ~150 images from NBA CDN to `storage/app/public/player-headshots/`

To make images publicly accessible:

```bash
php artisan storage:link
```

### 8. Start Development Server

```bash
php artisan serve
```

The API will be available at: `http://localhost:8000/api`

---

## Verify Installation

### Test the API

**1. Health Check:**
```bash
curl http://localhost:8000/up
```

Should return `200 OK`

**2. Login with Test User:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'
```

**3. List Teams:**
```bash
curl http://localhost:8000/api/teams \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Check Routes

List all API routes:

```bash
php artisan route:list --path=api
```

Should show 17 routes.

---

## Configuration

### Key Environment Variables

**Application:**
```env
APP_NAME=BasketballSpy
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

**Database:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=basketball_spy
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Mail (for notifications - future):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

**Cache & Queue (future):**
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## Development Tools

### Laravel Telescope (Debugging)

Install Telescope for development debugging:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access at: `http://localhost:8000/telescope`

**Features:**
- Request logging
- Database queries
- Exceptions
- Cache operations
- Jobs and queues

### Laravel Tinker (REPL)

Laravel Tinker is included. Access the REPL:

```bash
php artisan tinker
```

**Example usage:**
```php
// Get all teams
$teams = App\Models\Team::count();

// Create a report
$report = App\Models\Report::create([
    'user_id' => 1,
    'player_id' => 1,
    'team_id_at_time' => 1,
    'offense_shooting' => 5,
    'notes' => 'Great shooter!'
]);

// Check user role
$user = App\Models\User::find(1);
$user->isSuperAdmin();
```

---

## Database Management

### Migrations

**Create new migration:**
```bash
php artisan make:migration create_example_table
```

**Run migrations:**
```bash
php artisan migrate
```

**Rollback last migration:**
```bash
php artisan migrate:rollback
```

**Reset and re-run all migrations:**
```bash
php artisan migrate:fresh
```

**With seeding:**
```bash
php artisan migrate:fresh --seed
```

### Seeders

**Create new seeder:**
```bash
php artisan make:seeder ExampleSeeder
```

**Run specific seeder:**
```bash
php artisan db:seed --class=NBATeamsSeeder
```

**Run all seeders:**
```bash
php artisan db:seed
```

---

## Testing

### Run Tests

```bash
php artisan test
```

**Run specific test:**
```bash
php artisan test --filter=ReportControllerTest
```

**With coverage:**
```bash
php artisan test --coverage
```

### Create Tests

**Feature test:**
```bash
php artisan make:test ReportControllerTest
```

**Unit test:**
```bash
php artisan make:test ReportTest --unit
```

---

## Performance Optimization

### For Development

**Cache configuration:**
```bash
php artisan config:cache
```

**Clear all caches:**
```bash
php artisan optimize:clear
```

### For Production

**Optimize everything:**
```bash
php artisan optimize
```

This runs:
- `config:cache`
- `route:cache`
- `view:cache`
- `event:cache`

**Clear specific caches:**
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

## Common Commands

### Artisan Commands

```bash
# List all commands
php artisan list

# Get help for a command
php artisan help migrate

# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear

# Clear compiled views
php artisan view:clear

# Run queue workers (future)
php artisan queue:work

# Restart queue workers
php artisan queue:restart
```

### Custom Commands

Basketball Spy includes custom artisan commands:

```bash
# Download player images
php artisan players:fetch-images

# Force re-download
php artisan players:fetch-images --force
```

---

## Troubleshooting

### Issue: "Class not found"

**Solution:** Rebuild autoloader
```bash
composer dump-autoload
```

### Issue: "Permission denied" on storage

**Solution:** Fix permissions
```bash
chmod -R 775 storage bootstrap/cache
```

### Issue: Database connection failed

**Solution:** Check credentials in `.env` and ensure PostgreSQL is running
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Restart PostgreSQL
sudo systemctl restart postgresql
```

### Issue: "No application encryption key"

**Solution:** Generate key
```bash
php artisan key:generate
```

### Issue: Routes not found

**Solution:** Clear route cache
```bash
php artisan route:clear
php artisan optimize:clear
```

---

## Production Deployment

### Preparation

1. **Set environment to production:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

2. **Optimize for production:**
```bash
composer install --optimize-autoloader --no-dev
php artisan optimize
```

3. **Set up proper database backups**

4. **Configure queue workers:**
```bash
# Using Supervisor (recommended)
sudo apt-get install supervisor
sudo nano /etc/supervisor/conf.d/basketball-spy-worker.conf
```

5. **Set up HTTPS with SSL certificate**

### Laravel Cloud Deployment

```bash
# Install Laravel Cloud CLI
composer global require laravel/cloud-cli

# Login
laravel cloud login

# Deploy
laravel cloud deploy production
```

### Traditional Server Deployment

**1. Copy files to server:**
```bash
rsync -avz --exclude=node_modules --exclude=.git ./ user@server:/var/www/basketball-spy/
```

**2. Install dependencies:**
```bash
cd /var/www/basketball-spy/laravel
composer install --optimize-autoloader --no-dev
```

**3. Set permissions:**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**4. Run migrations:**
```bash
php artisan migrate --force
```

**5. Optimize:**
```bash
php artisan optimize
```

**6. Restart services:**
```bash
sudo systemctl restart php8.5-fpm
sudo systemctl restart nginx
```

---

## Environment-Specific Configuration

### Local Development
```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
```

### Staging
```env
APP_ENV=staging
APP_DEBUG=false
LOG_LEVEL=info
```

### Production
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_SECURE_COOKIE=true
```

---

## Next Steps

- [Database Schema Documentation](./04-database-schema.md)
- [API Development Guide](../../api-reference/01-authentication.md)
- [Testing Guide](./07-testing.md)
- [Deployment Guide](./08-deployment.md)
