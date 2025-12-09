# 🏀 Basketball Spy

> A professional basketball scouting and player evaluation platform for scouts, teams, and organizations.

Basketball Spy is a modern, offline-first mobile application and API platform designed for basketball scouts to evaluate players, create detailed reports, and access comprehensive analytics—even without an internet connection.

---

## ✨ Features

### For Scouts
- 📱 **Offline-First Mobile App** - Create reports anywhere, sync when connected
- 📊 **Comprehensive Evaluation** - 20 detailed rating categories across 4 dimensions
- 🔍 **Player Search** - Find and track players across NBA, WNBA, and international leagues
- 📈 **Historical Tracking** - View past evaluations and track player development
- ⚡ **Fast & Intuitive** - Beautiful UI optimized for quick courtside evaluations

### For Organization Admins
- 👥 **Scout Management** - Monitor scout productivity and report quality
- 📊 **Team Analytics** - Aggregate player ratings across multiple scouts
- 📈 **Performance Metrics** - Track evaluation consistency and scout agreement
- 🎯 **Player Insights** - See consensus ratings and identify top prospects

### For Super Admins
- 🌐 **Multi-Organization** - Manage multiple scouting organizations
- 💼 **Subscription Tiers** - Control feature access and advanced analytics
- 📊 **System-Wide Analytics** - Monitor platform usage and performance
- 🔐 **SOC 2 Compliant** - Enterprise-grade security and data isolation

---

## 🏗️ Architecture

Basketball Spy is built on a modern, scalable architecture with security and reliability at its core.

### Technology Stack

**Backend (Laravel 12)**
- Framework: Laravel 12 (PHP 8.5)
- Database: PostgreSQL
- Authentication: Laravel Sanctum (token-based)
- Storage: Laravel Cloud
- Cache/Queue: Redis (future)

**Mobile (React Native + Expo)**
- Framework: React Native with Expo
- Local Database: SQLite
- State Management: React Context + AsyncStorage
- Sync: Custom local-first sync with conflict resolution
- Build: EAS Build (iOS + Android)

### Core Principles

1. **🔒 Security-First** - SOC 2 compliant multi-tenancy with organization-level data isolation
2. **📴 Local-First** - Everything works offline, syncs when connected
3. **🚀 API-First** - RESTful API with comprehensive documentation
4. **🤖 AI-Ready** - Architecture designed for future AI-powered insights

---

## 📦 What's Inside

```
basketball-spy-repo/
├── basketball-spy/        # Original prototype (HTML/CSS/JS)
├── laravel/              # Laravel 12 backend API
│   ├── app/              # Application code
│   ├── database/         # Migrations, seeders, models
│   ├── routes/           # API routes
│   └── storage/          # File storage
├── mobile/               # React Native mobile app (Expo)
│   ├── src/              # App source code
│   ├── assets/           # Images, fonts
│   └── app.json          # Expo configuration
└── docs/                 # Comprehensive documentation
    ├── user-guide/       # End-user documentation
    ├── developer-guide/  # Development guides
    └── api-reference/    # API documentation
```

---

## 🚀 Quick Start

### Prerequisites
- **Backend:** PHP 8.5+, Composer, PostgreSQL
- **Mobile:** Node.js 20+, npm/yarn, Expo CLI
- **Development:** Git, VS Code (recommended)

### Backend Setup (Laravel)

```bash
# Navigate to Laravel directory
cd laravel

# Install dependencies
composer install

# Set up environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_CONNECTION=pgsql
DB_DATABASE=basketball_spy
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations and seed data
php artisan migrate:fresh --seed

# Start development server
php artisan serve
```

The API will be available at `http://localhost:8000/api`

### Mobile Setup (React Native)

```bash
# Navigate to mobile directory
cd mobile

# Install dependencies
npm install

# Start Expo development server
npx expo start

# Press 'i' for iOS simulator
# Press 'a' for Android emulator
# Scan QR code with Expo Go app for physical device
```

---

## 📊 Database

### Seeded Data Included

- ✅ **60 Teams** - 30 NBA, 12 WNBA, 18 international (EuroLeague, CBA, NBL)
- ✅ **150 Players** - 5 key players from each NBA team with official headshots
- ✅ **Team Logos** - Official team logos and colors from NBA CDN

### Download Player Images

```bash
cd laravel

# Download all player headshots to local storage
php artisan players:fetch-images

# Force re-download
php artisan players:fetch-images --force
```

This downloads ~150 player headshots from the NBA CDN to your Laravel storage for better performance and reliability.

---

## 🔐 Authentication

Basketball Spy uses token-based authentication via Laravel Sanctum.

### Login Example

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com",
    "role": "scout"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz",
  "token_type": "Bearer"
}
```

Use the token in subsequent requests:
```bash
curl http://localhost:8000/api/teams \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📚 API Endpoints

### Authentication
- `POST /api/login` - Login and get token
- `POST /api/register` - Register new user
- `POST /api/logout` - Logout and revoke token
- `GET /api/user` - Get current user details

### Teams
- `GET /api/teams` - List all teams (supports filtering and search)
- `GET /api/teams/{id}` - Get team details with players

### Players
- `GET /api/players?team_id=1` - List players for a team
- `GET /api/players/{id}` - Get player details with reports

### Reports
- `GET /api/reports` - List reports (organization-scoped)
- `POST /api/reports` - Create new report
- `GET /api/reports/{id}` - Get report details
- `PUT /api/reports/{id}` - Update report
- `DELETE /api/reports/{id}` - Delete report
- `POST /api/reports/sync` - Batch sync (local-first)

### Analytics
- `GET /api/analytics/organization` - Organization dashboard (admin only)
- `GET /api/analytics/system` - System-wide analytics (super admin only)
- `GET /api/analytics/players/{id}` - Player-specific analytics

**Total:** 17 RESTful API endpoints

See [API Documentation](./docs/api-reference/) for complete details.

---

## 📖 Documentation

Comprehensive documentation is available in the `/docs` directory:

### For Users
- [Getting Started](./docs/user-guide/01-getting-started.md)
- [Using the Mobile App](./docs/user-guide/02-mobile-app-guide.md)
- [Creating Reports](./docs/user-guide/03-creating-reports.md)
- [Understanding Analytics](./docs/user-guide/04-analytics-guide.md)

### For Developers
- [Architecture Overview](./docs/developer-guide/01-architecture.md)
- [Laravel Backend Setup](./docs/developer-guide/02-laravel-setup.md)
- [React Native Setup](./docs/developer-guide/03-react-native-setup.md)
- [Database Schema](./docs/developer-guide/04-database-schema.md)
- [Local-First Sync](./docs/developer-guide/05-local-first-sync.md)
- [Multi-Tenancy](./docs/developer-guide/06-multi-tenancy.md)

### API Reference
- [Authentication](./docs/api-reference/01-authentication.md)
- [Teams API](./docs/api-reference/02-teams-api.md)
- [Players API](./docs/api-reference/03-players-api.md)
- [Reports API](./docs/api-reference/04-reports-api.md)
- [Analytics API](./docs/api-reference/05-analytics-api.md)

---

## 🎯 Player Evaluation Categories

Basketball Spy uses a comprehensive 20-field evaluation system:

### Offense (6 fields)
- Shooting
- Finishing
- Driving
- Dribbling
- Creating
- Passing

### Defense (4 fields)
- 1-on-1 Guarding
- Blocking
- Team Defense
- Rebounding

### Intangibles (4 fields)
- Effort
- Role Acceptance
- Basketball IQ
- Awareness

### Athleticism (6 fields)
- Hands
- Length
- Quickness
- Jumping
- Strength
- Coordination

Each category is rated on a 1-5 scale with partial reports supported.

---

## 🔄 Offline-First Sync

Basketball Spy is designed to work perfectly offline. Here's how it works:

1. **Scout creates report** → Saved to local SQLite database
2. **App syncs when network available** → Background sync to Laravel API
3. **Conflict detection** → Server checks timestamps
4. **Resolution** → Automatic sync or user-prompted resolution

```
Mobile (SQLite) ─sync→ Laravel API ─→ PostgreSQL
                  ↑
                  └─── Conflict resolution
```

No data is ever lost, even with poor or no connectivity.

---

## 🏢 Multi-Tenancy & Roles

Basketball Spy supports multiple organizations with strict data isolation:

### Three-Tier Access Model

**Scout**
- Create and edit own reports
- View teams and players
- View own analytics

**Organization Admin**
- All scout permissions
- View all org reports
- Manage org scouts
- View org analytics

**Super Admin**
- All org admin permissions
- Manage all organizations
- System-wide analytics
- Subscription management

### Data Isolation

Each organization's data is completely isolated:
- Scouts in Org A cannot see Org B's data
- Database queries automatically scoped by organization_id
- SOC 2 compliance ready

---

## 🧪 Testing

### Backend Tests

```bash
cd laravel

# Run all tests
php artisan test

# Run specific test
php artisan test --filter=ReportControllerTest
```

### Mobile Tests

```bash
cd mobile

# Run Jest tests
npm test

# Run with coverage
npm test -- --coverage
```

---

## 🚢 Deployment

### Laravel Backend (Laravel Cloud)

```bash
# Deploy to Laravel Cloud
laravel deploy production

# Or use traditional hosting
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### React Native Mobile (EAS Build)

```bash
# Build for iOS
eas build --platform ios

# Build for Android
eas build --platform android

# Submit to App Store
eas submit --platform ios

# Submit to Play Store
eas submit --platform android
```

---

## 📝 Development Roadmap

### Phase 1: MVP (Current)
- ✅ Laravel API with all endpoints
- ✅ Database schema and seeders
- ✅ Multi-tenancy and RBAC
- ✅ Local-first sync architecture
- 🚧 React Native mobile app
- 🚧 Offline data storage

### Phase 2: Enhanced Features
- 📹 Video analysis integration
- 📊 Advanced analytics dashboards
- 🤖 AI-powered player comparisons
- 📈 Historical trend analysis
- 🔔 Real-time notifications

### Phase 3: Enterprise
- 🌐 Web dashboard for org admins
- 📧 Email reports and exports
- 📱 Apple Watch complications
- 🎯 Custom evaluation templates
- 🔗 Third-party integrations

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](./CONTRIBUTING.md) for guidelines.

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

[License information to be added]

---

## 🙏 Acknowledgments

- **NBA.com** - Official team logos and player headshots
- **Laravel** - Excellent PHP framework
- **React Native & Expo** - Mobile development platform
- **Basketball scouts everywhere** - The inspiration for this project

---

## 📞 Support

For support, please:
- 📖 Check the [documentation](./docs/)
- 🐛 Report issues on [GitHub Issues](https://github.com/your-org/basketball-spy/issues)
- 💬 Join our community [Discord/Slack]
- 📧 Email support@basketballspy.com

---

## 📊 Project Status

**Backend:** ✅ **Complete and Production Ready**
- All API endpoints implemented
- Database fully seeded with real data
- Multi-tenancy and RBAC working
- Local-first sync architecture in place

**Mobile App:** 🚧 **In Development**
- Architecture designed
- Ready to begin implementation

---

Built with ❤️ for basketball scouts everywhere.

**Repository:** https://github.com/alexmansfield/basketball-spy
**Documentation:** [./docs/README.md](./docs/README.md)
**License:** TBD
