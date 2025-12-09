# Basketball Spy Documentation

Welcome to the Basketball Spy documentation. This guide will help you understand, deploy, and develop with the Basketball Spy platform.

## 📚 Documentation Structure

### User Guides
For scouts, organization admins, and end users:
- [Getting Started](./user-guide/01-getting-started.md) - Installation and first steps
- [Using the Mobile App](./user-guide/02-mobile-app-guide.md) - Complete mobile app guide
- [Creating Reports](./user-guide/03-creating-reports.md) - How to evaluate players
- [Understanding Analytics](./user-guide/04-analytics-guide.md) - Reading and using analytics

### Developer Guides
For developers building or extending Basketball Spy:
- [Architecture Overview](./developer-guide/01-architecture.md) - System design and principles
- [Laravel Backend Setup](./developer-guide/02-laravel-setup.md) - Backend installation
- [React Native Setup](./developer-guide/03-react-native-setup.md) - Mobile app setup
- [Database Schema](./developer-guide/04-database-schema.md) - Complete schema documentation
- [Local-First Sync](./developer-guide/05-local-first-sync.md) - Offline-first architecture
- [Multi-Tenancy](./developer-guide/06-multi-tenancy.md) - Organization isolation (SOC 2)
- [Testing Guide](./developer-guide/07-testing.md) - Running and writing tests
- [Deployment](./developer-guide/08-deployment.md) - Production deployment guide

### API Reference
For mobile app developers and integrators:
- [Authentication](./api-reference/01-authentication.md) - Login, tokens, and security
- [Teams API](./api-reference/02-teams-api.md) - Team endpoints
- [Players API](./api-reference/03-players-api.md) - Player endpoints
- [Reports API](./api-reference/04-reports-api.md) - Report CRUD and sync
- [Analytics API](./api-reference/05-analytics-api.md) - Analytics endpoints
- [Error Handling](./api-reference/06-error-handling.md) - Error codes and responses

## 🎯 Quick Start

### For End Users
1. Download the Basketball Spy mobile app (iOS/Android)
2. Create an account or login with your organization credentials
3. Start evaluating players and creating reports
4. View analytics and insights

### For Developers

**Backend (Laravel):**
```bash
cd laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

**Mobile App (React Native):**
```bash
cd mobile
npm install
npx expo start
```

## 🏗️ System Architecture

Basketball Spy is built on a modern, scalable architecture:

- **Backend**: Laravel 12 (PHP 8.5) with PostgreSQL
- **Mobile**: React Native with Expo
- **Authentication**: Laravel Sanctum (token-based)
- **Storage**: PostgreSQL (server), SQLite (mobile)
- **Sync**: Local-first with conflict resolution
- **Hosting**: Laravel Cloud (backend), Expo EAS (mobile)

## 🔑 Key Features

### For Scouts
- Offline-first report creation
- Comprehensive 20-field player evaluation
- Historical player tracking
- Team and player search

### For Organization Admins
- Scout performance metrics
- Team statistics
- Report quality analysis
- Player evaluation aggregates

### For Super Admins
- System-wide analytics
- Multi-organization management
- Subscription tier management
- Usage statistics

## 📦 What's Included

### Laravel Backend
- ✅ RESTful API (17 endpoints)
- ✅ Multi-tenancy with organization isolation
- ✅ Role-based access control
- ✅ 60 teams (NBA, WNBA, Foreign)
- ✅ 150 players with official headshots
- ✅ Comprehensive analytics
- ✅ Local-first sync with conflict resolution

### React Native Mobile App
- ✅ iOS and Android support
- ✅ Offline-first architecture
- ✅ Player evaluation interface
- ✅ Team and player browsing
- ✅ Analytics dashboards
- ✅ Background sync

## 🔐 Security & Compliance

Basketball Spy is built with SOC 2 compliance in mind:

- **Multi-tenancy**: Organization-scoped data isolation
- **Authentication**: Token-based API authentication
- **Authorization**: Role-based access control (Scout, Org Admin, Super Admin)
- **Data Retention**: Soft deletes for historical records
- **Audit Logs**: All data access tracked

## 🤝 Contributing

For contribution guidelines, see [CONTRIBUTING.md](../CONTRIBUTING.md)

## 📄 License

[License information will be added]

## 📞 Support

For support, please contact [support information will be added]

---

Built with ❤️ for basketball scouts everywhere.
