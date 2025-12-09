# Basketball Spy - Project Memory

## Project Overview
Basketball Spy is a scouting application for evaluating basketball players (NBA/WNBA/Foreign leagues). Scouts will use this app to create detailed player evaluation reports during games or practices.

**Tech Stack:**
- Frontend: React Native (iOS, Android, potentially desktop via Expo)
- Backend: Laravel 12
- Database: PostgreSQL (backend), SQLite/WatermelonDB (local-first mobile)
- Hosting: Laravel Cloud

**Existing Prototype:** `file:///Users/default/Documents/basketball-spy/index.html`

## First Principles

### 1. AI-First
- AI integrations should be additive and meaningful, not performative
- Should enhance scout workflows naturally

### 2. Security-First (SOC 2 Compliance)
- Multi-tenant architecture with strict data isolation
- Organization-level security (multiple scouts, teams, orgs)
- Zero possibility of data crossover or breaches
- Row-level security in database
- API authentication via Laravel Sanctum

### 3. Local-First
- **CRITICAL**: Everything saves locally FIRST before syncing
- Scouts must be able to work with zero network connectivity
- Partial/draft reports saved offline automatically
- Graceful degradation when network fails
- Proactive sync when network available
- Conflict resolution strategy for multi-device scenarios
- No data loss under any circumstances

### 4. API-First
- RESTful API design
- Mobile app consumes same API as potential future web dashboard
- Versioned endpoints

## Laravel Packages to Use
- **Pennant**: Feature flags
- **Nightwatch**: Application monitoring
- **Sanctum**: API authentication
- **Horizon**: Queue monitoring
- **Telescope**: Debugging (dev only)
- **Multi-tenancy Package** (e.g., spatie/laravel-multitenancy or tenancy/tenancy): For organization isolation

## Multi-Tenancy & Analytics Architecture

### Three-Tier Access Model

**1. Scout Level (Mobile App)**
- Scouts belong to Organizations
- Can only access their own reports and organization's data
- Local-first storage with background sync

**2. Organization Level (Laravel Back Office - Web Dashboard)**
- Organization admins can log in to web dashboard
- **Analytics Suite includes:**
  - Scout performance metrics
  - Player evaluation reports aggregated across scouts
  - Team statistics and trends
  - Report quality and consistency analysis
  - Individual scout productivity
- **Initially available**: Basic analytics immediately accessible to all organizations
- **Future add-on**: Advanced analytics as premium feature

**3. Service Provider Level (Super Admin)**
- We (service provider) have super-admin access
- Can view all organizations
- System-wide analytics across all organizations
- Performance monitoring, usage metrics, billing, etc.

### Implementation Requirements
- **Tenant isolation**: All database queries must be scoped to organization_id
- **Row-level security**: PostgreSQL policies to prevent data leakage
- **Feature flags**: Use Pennant to enable/disable advanced analytics per organization
- **Billing integration**: Track organization subscription level for add-on features
- **Audit logs**: Track all data access for SOC 2 compliance

## Data Model

### Teams
- id, name, abbreviation, location, nickname, url, logoUrl, color, league (NBA/WNBA/Foreign)
- CRUD operations supported
- Searchable dropdown with name + logo in UI

### Players
- id, team_id, name, jersey, position, height, weight, headshotUrl
- Players can belong to teams and switch teams
- CRUD operations supported

### Reports (Scout Evaluations)
- id, player_id, scout_id, team_id_at_time
- **Local-first**: Saved to SQLite immediately, synced later
- Multiple scouts can evaluate same player independently (no collaboration)
- Support for viewing historical reports on a player
- Partial reports supported (can be saved incomplete)

**Rating Categories (1-5 scale, shadcn-inspired number buttons):**

**OFFENSE**
- Shooting
- Finishing
- Driving
- Dribbling
- Creating
- Passing

**DEFENSE**
- 1 on 1 guarding
- Blocking
- Rotating/positioning (team defense)
- Rebounding

**INTANGIBLES**
- Effort
- Role Acceptance
- IQ
- Awareness

**ATHLETICISM**
- Hands
- Length
- Quickness
- Jumping
- Strength
- Coordination

**Additional Fields:**
- notes (free-form text)
- synced_at (timestamp for local-first sync tracking)

### Users (Scouts)
- id, organization_id, name, email, password, role
- Roles: scout, admin, org_admin

### Organizations
- id, name
- For multi-tenancy and data isolation

## UI/UX Design Patterns (from Prototype)

### Responsive Behavior
- **Desktop (1024px+)**: Side-by-side team panels, editing expands one team
- **Tablet (800-1023px)**: Side-by-side but condensed, horizontal scroll when editing
- **Phone (<800px)**: Single team view, swipe/button to switch teams

### Key Interactions
- Team switching via logo buttons
- Player selection opens report editor
- Player movement between "in-game" (5 active) and "bench"
- Players always sorted by jersey number
- Clean white cards on blurred basketball background

### Visual Design
- System font stack
- Team-colored cards for active players
- Clean, minimal aesthetic
- Player cards: headshot, name, jersey, position, height, weight

## Implementation Notes
- Do NOT create a "Games" model - scouts evaluate players independently
- Save everything locally FIRST (SQLite/WatermelonDB)
- Sync queue for background upload when network available
- Use React Native's AsyncStorage for simple persistence
- Consider WatermelonDB for complex relational offline data
