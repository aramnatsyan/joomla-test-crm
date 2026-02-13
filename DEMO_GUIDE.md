# Demo Guide - CRM Stages

This guide walks you through the CRM prototype to demonstrate all features.

## 🎬 Demo Scenario: "Acme Corporation"

Let's take one of the sample companies through the entire sales pipeline.

### Step 1: View Companies

1. Open `http://localhost:8000`
2. You'll see three companies:
   - Acme Corporation
   - TechStart Inc
   - Global Solutions Ltd
3. Notice they're all at **Ice** stage (no events yet)

### Step 2: Start with Acme

1. Click **View Details** on "Acme Corporation"
2. Observe:
   - Current stage: **Ice**
   - Instructions: "Initial contact needed. Attempt to reach out to the company."
   - Available Actions: Only "Log Contact Attempt" is enabled
   - Event History: Empty

### Step 3: Progress Through Stages

Follow this sequence to demonstrate the full pipeline:

#### 3.1 Ice → Touched
- Click "Log Contact Attempt"
- Confirm the action
- **Stage changes to: Touched**
- Notice new instruction appears
- Available action: "Log Decision Maker Call"

#### 3.2 Touched → Aware
- Click "Log Decision Maker Call"
- Enter call notes (e.g., "Spoke with CEO John. Very interested in our solution.")
- Submit
- **Stage changes to: Aware**
- Available action: "Mark Discovery Complete"

#### 3.3 Aware → Interested
- Click "Mark Discovery Complete"
- Confirm
- **Stage changes to: Interested**
- Available action: "Schedule Demo"

#### 3.4 Interested → Demo Planned
- Click "Schedule Demo"
- Select a date/time (e.g., tomorrow at 2 PM)
- Submit
- **Stage changes to: Demo Planned**
- Available action: "Mark Demo Complete"

#### 3.5 Demo Planned → Demo Done
- Click "Mark Demo Complete"
- Confirm
- **Stage changes to: Demo Done**
- Available action: "Issue Invoice"

#### 3.6 Demo Done → Committed
- Click "Issue Invoice"
- Confirm
- **Stage changes to: Committed**
- Available action: "Record Payment"

#### 3.7 Committed → Customer
- Click "Record Payment"
- Confirm
- **Stage changes to: Customer**
- Available action: "Issue Credentials"

#### 3.8 Customer → Activated
- Click "Issue Credentials"
- Confirm
- **Stage changes to: Activated** 🎉
- No more actions available (pipeline complete!)

### Step 4: Review Event History

Scroll down to see the complete event timeline:
- All 8 events listed in reverse chronological order
- Timestamps for each event
- Event-specific data (call notes, scheduled dates)

## 🚫 Testing Business Rules

Now let's test that the guards work!

### Test 1: Cannot Skip Stages

1. Go to "TechStart Inc" (still at Ice)
2. Notice only "Log Contact Attempt" is available
3. There's no way to skip to later stages!

### Test 2: Demo Expiry (60 Days)

This is hard to test without time travel, but you can:

1. Look at the tests: `tests/Unit/StageCalculatorTest.php`
2. See lines 118-142 for demo expiry tests
3. Run tests to verify: `vendor/bin/phpunit --filter demo`

Output shows:
```
✔ Demo older than 60 days is invalid
✔ Demo exactly 60 days ago is invalid
✔ Demo 59 days ago is valid
```

### Test 3: Required Data

1. Go to "Global Solutions Ltd"
2. Click "Log Contact Attempt" → Works
3. Click "Log Decision Maker Call"
4. Try to submit WITHOUT entering notes
5. **Error**: "Comment is required"
6. Enter notes and submit → Works!

### Test 4: Cannot Issue Invoice Without Demo

To simulate this, you'd need to:
1. Manually modify the database to remove the `demo_done` event
2. Try to issue invoice
3. Guard would block it

The tests verify this works:
```bash
vendor/bin/phpunit --filter "invoice requires demo"
```

## 🧪 Running Tests

See all business rules enforced:

```bash
# All tests
vendor/bin/phpunit --testdox

# Just stage calculation
vendor/bin/phpunit --filter StageCalculator

# Just business rules
vendor/bin/phpunit --filter EventGuard
```

## 📊 What to Notice

### 1. Event-Driven Architecture
- Every action creates an event
- Stage is recalculated after each event
- Event history shows complete audit trail

### 2. Server-Side Validation
- UI only shows valid actions
- But guards enforce rules server-side too
- Try to bypass UI (e.g., via curl) → blocked!

### 3. Clean Code
- Look at `src/Domain/StageCalculator.php` - simple, readable
- Look at `src/Domain/EventGuard.php` - clear business rules
- Tests are self-documenting

### 4. Type Safety
- PHP 8.2 enums prevent typos
- Readonly properties prevent mutation
- Strict types catch errors early

## 🎯 Key Takeaways

1. **Stages are calculated, not set** - This prevents data integrity issues
2. **Business rules enforced** - Cannot skip steps or violate conditions
3. **Full audit trail** - Every state change is logged
4. **Testable** - Business logic is pure functions
5. **Scalable** - Event sourcing enables time travel, replay, analytics

## 🔍 Advanced Exploration

### Check Database Structure

```sql
-- Connect to database
mysql -u root joomlacrm

-- View companies
SELECT * FROM companies;

-- View events
SELECT * FROM events;

-- See stage calculation in action
SELECT 
    c.name,
    c.current_stage as cached_stage,
    COUNT(e.id) as event_count
FROM companies c
LEFT JOIN events e ON e.company_id = c.id
GROUP BY c.id;
```

### Modify Events

Try adding events directly via SQL to see stage recalculate:

```sql
-- Add contact event for company ID 2
INSERT INTO events (company_id, event_type, created_at)
VALUES (2, 'contact_attempted', NOW());

-- Refresh company page to see stage update
```

### Time Travel

Since events are immutable, you could build a feature to:
- View company state at any point in history
- Replay events from a specific date
- Analyze conversion rates by cohort

This is left as an exercise! 🚀

## 🐛 Troubleshooting

**Problem**: "Database connection failed"
**Solution**: 
1. Ensure MySQL is running
2. Check credentials in `.env`
3. Run `php setup.php` to auto-create database

**Problem**: "No actions available" but company isn't at Activated
**Solution**: 
- Check event history
- Possibly a demo expired (>60 days old)
- Re-do the demo to continue

**Problem**: Tests failing
**Solution**:
- Run `composer install` to ensure dependencies
- Check PHP version: `php -v` (need 8.2+)
- Clear PHPUnit cache: `rm -rf .phpunit.cache`

## 📚 Further Reading

- `README.md` - Full documentation
- `PROJECT_SUMMARY.md` - Quick reference
- `src/Domain/` - Business logic code
- `tests/Unit/` - Comprehensive test examples

Enjoy exploring the event-driven CRM! 🎉
