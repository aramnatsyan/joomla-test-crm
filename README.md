# CRM Stages - Event-Driven Pipeline

A sophisticated CRM prototype built with PHP 8.2+ demonstrating event-driven architecture where company progression through sales stages is derived entirely from business events, not manual assignment.

## 🎯 Core Concept

**Stages are calculated, never set manually.** This ensures data integrity and prevents managers from skipping required steps in the sales process.

## 📋 Table of Contents

- [Architecture](#architecture)
- [Stage Flow](#stage-flow)
- [Business Rules](#business-rules)
- [Installation](#installation)
- [Testing](#testing)
- [Data Model](#data-model)
- [AI Workflow](#ai-workflow)
- [Future Improvements](#future-improvements)

## 🏗️ Architecture

### Event-Driven Design

The system follows **event sourcing principles**:

1. **Events are the source of truth** - All state changes are captured as immutable events
2. **Stages are projections** - Current stage is calculated from event history
3. **Append-only log** - Events are never updated or deleted
4. **Business rules enforced** - Guards prevent invalid events from being created

### Directory Structure

```
src/
├── Domain/              # Core business logic (framework-agnostic)
│   ├── Event.php        # Immutable event entity
│   ├── EventType.php    # Event type enum with validation
│   ├── Stage.php        # Stage enum with metadata
│   ├── Company.php      # Company entity
│   ├── StageCalculator.php    # Calculates stage from events
│   ├── EventGuard.php         # Validates event creation rules
│   ├── EventGuardException.php
│   ├── ActionResolver.php     # Determines available actions
│   └── AvailableAction.php
├── Repository/          # Data access layer
│   ├── CompanyRepository.php
│   └── EventRepository.php
└── Service/            # Application services
    ├── CrmService.php
    └── ServiceContainer.php

tests/
└── Unit/               # PHPUnit tests
    ├── StageCalculatorTest.php
    └── EventGuardTest.php

views/                  # Presentation layer
├── header.php
└── footer.php

index.php              # Company list
company.php            # Company detail with actions
config.php             # Database configuration
schema.sql             # Database schema
```

### Design Principles

1. **Immutability** - Events and entities use `readonly` properties
2. **Type Safety** - PHP 8.2 enums and strict types throughout
3. **Single Responsibility** - Each class has one clear purpose
4. **Dependency Injection** - No global state or hard dependencies
5. **Testability** - Pure functions make testing straightforward

## 🔄 Stage Flow

Companies progress through 9 stages:

```
Ice → Touched → Aware → Interested → Demo Planned → Demo Done → Committed → Customer → Activated
```

### Stage Definitions

| Stage | Trigger Event | Description |
|-------|---------------|-------------|
| **Ice** | (none) | Initial state, no contact yet |
| **Touched** | `contact_attempted` | First outreach made |
| **Aware** | `decision_maker_call_logged` | Decision maker contacted |
| **Interested** | `discovery_filled` | Discovery completed, needs identified |
| **Demo Planned** | `demo_scheduled` | Demo date set |
| **Demo Done** | `demo_done` | Demo completed (valid < 60 days) |
| **Committed** | `invoice_issued` | Invoice sent after valid demo |
| **Customer** | `payment_received` | Payment confirmed |
| **Activated** | `first_credential_issued` | Onboarding complete |

## ⚖️ Business Rules

### Event Prerequisites

Events can only be created when prerequisites are met:

```php
contact_attempted
  → decision_maker_call_logged (requires: comment)
    → discovery_filled
      → demo_scheduled (requires: date/time)
        → demo_done
          → invoice_issued (demo must be < 60 days old)
            → payment_received
              → first_credential_issued
```

### Critical Rules

1. **No Stage Skipping** - Each event requires the previous one
2. **Demo Validity Window** - Demo is only valid for 60 days
3. **Data Requirements** - Some events require structured data:
   - `decision_maker_call_logged` requires a comment
   - `demo_scheduled` requires a date/time
4. **Server-Side Enforcement** - All rules validated in `EventGuard`
5. **Idempotent Events** - Creating duplicate events is allowed (real-world scenario)

### Edge Case: Demo Expiry

```php
// Demo exactly 60 days ago = INVALID (must be < 60)
// Demo 59 days ago = VALID
// Demo 61 days ago = INVALID
```

When a demo expires, the company falls back to `demo_planned` stage, and invoice cannot be issued until a new demo is completed.

## 🚀 Installation

### Prerequisites

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer

### Setup Steps

1. **Clone the repository**

```bash
git clone git@github.com:aramnatsyan/joomla-test-crm.git
cd joomla-test-crm
```

2. **Install dependencies**

```bash
composer install
```

3. **Configure database**

Create a database and user:

```sql
CREATE DATABASE joomlacrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crmuser'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON joomlacrm.* TO 'crmuser'@'localhost';
FLUSH PRIVILEGES;
```

4. **Set environment variables** (or edit `config.php`)

```bash
export DB_HOST=localhost
export DB_PORT=3306
export DB_NAME=joomlacrm
export DB_USER=crmuser
export DB_PASS=your_password
```

5. **Import schema**

```bash
mysql -u crmuser -p joomlacrm < schema.sql
```

6. **Start development server**

```bash
php -S localhost:8000
```

7. **Open in browser**

Navigate to `http://localhost:8000`

## 🧪 Testing

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run with Detailed Output

```bash
vendor/bin/phpunit --testdox
```

### Test Coverage

The test suite includes:

- **Stage Calculation Tests** (15 tests)
  - All stage transitions
  - Demo validity edge cases (59, 60, 61 days)
  - Out-of-order events handling
  - Multiple demos (latest used for validity)

- **Event Guard Tests** (16 tests)
  - All prerequisite validations
  - Data requirement validations
  - Full happy path
  - Business rule violations

### TDD Demonstration

During development, two tests initially failed:

```
✘ Decision maker call requires comment
✘ Demo scheduled requires date
```

**Root Cause**: The `EventGuard` was checking generic data requirements before specific field validation, resulting in less helpful error messages.

**Fix**: Moved data validation into each specific validation method to provide precise error messages.

**Result**: All 31 tests passing ✅

This demonstrates:
1. Tests caught the bug immediately
2. Clear test names made debugging easy
3. Fix was surgical (minimal code change)
4. All tests green after fix

## 💾 Data Model

### Tables

#### `companies`

```sql
CREATE TABLE companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    current_stage VARCHAR(50) NULL,        -- Cached stage (recalculated on event)
    stage_updated_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stage (current_stage),
    INDEX idx_created (created_at)
);
```

**Design Notes**:
- `current_stage` is a **cache** for performance
- True stage is always calculated from events
- Cache is updated automatically when events are added

#### `events`

```sql
CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON NULL,                  -- Structured event metadata
    created_by INT UNSIGNED NULL,          -- User who created event
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company_created (company_id, created_at DESC),
    INDEX idx_company_type (company_id, event_type),
    INDEX idx_type_created (event_type, created_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

**Design Notes**:
- **Append-only** - Never UPDATE or DELETE
- `event_data` stores type-specific data (comments, dates, etc.)
- Indexes optimized for:
  - Loading company history (company_id + created_at)
  - Finding event types (company_id + event_type)
  - Analytics (event_type + created_at)

### Scalability

For high-volume production:

1. **Partitioning** - Partition `events` by `created_at` (monthly)
2. **Archival** - Move old events to cold storage
3. **Read Replicas** - Separate read/write databases
4. **Caching** - Redis for stage calculations
5. **Event Snapshots** - Store stage snapshots to avoid full event replay

## 🤖 AI Workflow

### How AI Was Used

This project was built with AI assistance at multiple levels:

#### 1. Architecture Design
- **Input**: Requirements document with business rules
- **AI Role**: Proposed event-driven architecture and domain model
- **Human Role**: Validated approach, adjusted for PHP/Joomla ecosystem
- **Outcome**: Clean separation of concerns, testable design

#### 2. Code Generation
- **Input**: Architecture decisions and domain entities
- **AI Role**: Generated boilerplate classes, enums, and methods
- **Human Role**: Reviewed for correctness, refactored for clarity
- **Outcome**: ~2000 lines of production code in < 1 hour

#### 3. Test Creation
- **Input**: Business rules and edge cases
- **AI Role**: Generated comprehensive test suite covering happy paths and edge cases
- **Human Role**: Added missing scenarios (e.g., 60-day boundary)
- **Outcome**: 31 tests with 100% coverage of business logic

#### 4. Bug Discovery & Fixing
- **Process**: 
  1. AI generated initial guard validation
  2. Tests revealed poor error messages
  3. AI proposed fix (move validation into specific methods)
  4. Human approved and applied
- **Outcome**: Demonstrated TDD workflow

#### 5. Documentation
- **Input**: Codebase structure and requirements
- **AI Role**: Generated README structure and content
- **Human Role**: Added context, refined explanations
- **Outcome**: Comprehensive documentation

### Controlling Hallucinations

Strategies used to ensure correctness:

1. **Strict Type System** - PHP 8.2 types catch many errors at runtime
2. **Test-First** - Tests validate AI-generated code
3. **Incremental Review** - Review each component before proceeding
4. **Domain Validation** - Business rules encoded in code, not docs
5. **Static Analysis** - PHPStan/Psalm could be added for additional safety

### Productivity Gains

**Without AI**: Estimated 12-16 hours
- Architecture: 2 hours
- Domain logic: 4 hours
- Tests: 3 hours
- Repository/Service: 2 hours
- UI: 3 hours
- Documentation: 2 hours

**With AI**: Actual ~3 hours
- Architecture discussion: 30 min
- Code generation + review: 90 min
- Test creation + fixes: 45 min
- UI + polish: 30 min
- Documentation: 15 min

**Productivity multiplier: ~4-5x**

### Where AI Excelled

✅ Boilerplate code (repositories, entities)
✅ Comprehensive test cases
✅ Documentation structure
✅ Consistent code style
✅ Edge case identification

### Where AI Needed Guidance

⚠️ Business rule interpretation (required clarification)
⚠️ Database index strategy (needed performance context)
⚠️ Error message quality (initially too generic)
⚠️ UI/UX decisions (needed product perspective)

## 🔮 Future Improvements

### Short-term

1. **Authentication & Authorization**
   - User login system
   - Role-based permissions (sales rep, manager, admin)
   - Audit trail for who created events

2. **Enhanced UI**
   - Real-time updates (WebSockets)
   - Stage progression visualization
   - Dashboard with metrics
   - Mobile-responsive design

3. **Validations**
   - Prevent duplicate events within time window
   - Warning for demos scheduled too far in future
   - Bulk actions (import companies, batch events)

4. **Search & Filtering**
   - Search companies by name
   - Filter by stage
   - Date range filters
   - Event type filters

### Medium-term

5. **Reporting & Analytics**
   - Stage conversion rates
   - Time in each stage
   - Sales velocity metrics
   - Cohort analysis

6. **Integrations**
   - Calendar sync for demos (Google Calendar, Outlook)
   - Email notifications for stage changes
   - Slack/Teams notifications
   - CRM export (Salesforce, HubSpot)

7. **Advanced Event Types**
   - Email sent/opened/clicked
   - Contract signed
   - Follow-up scheduled
   - Support tickets linked

8. **Stage Customization**
   - Configurable stage definitions
   - Custom event types
   - Industry-specific templates
   - A/B testing stage flows

### Long-term

9. **Machine Learning**
   - Predict stage progression probability
   - Recommend next best action
   - Identify at-risk deals
   - Anomaly detection

10. **Multi-tenancy**
    - SaaS deployment
    - Organization isolation
    - Custom domain support
    - Usage-based billing

11. **Event Replay & Time Travel**
    - View company state at any point in time
    - Replay events for debugging
    - Event sourcing projections
    - CQRS implementation

12. **Scale & Performance**
    - Event stream processing (Kafka)
    - Materialized views for common queries
    - GraphQL API
    - Microservices extraction

## 📝 License

This is a prototype for demonstration purposes. Not licensed for production use.

## 👤 Author

Built as a technical demonstration of event-driven architecture in PHP.

---

## 🏁 Quick Start

```bash
# 1. Install
composer install

# 2. Setup database
mysql -u root -p < schema.sql

# 3. Configure
export DB_NAME=joomlacrm DB_USER=root DB_PASS=yourpass

# 4. Test
vendor/bin/phpunit

# 5. Run
php -S localhost:8000

# 6. Open
open http://localhost:8000
```

Enjoy exploring the event-driven CRM! 🚀
