<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Tests\Unit;

use DateTimeImmutable;
use Joomla\Component\CrmStages\Domain\Event;
use Joomla\Component\CrmStages\Domain\EventType;
use Joomla\Component\CrmStages\Domain\Stage;
use Joomla\Component\CrmStages\Domain\StageCalculator;
use PHPUnit\Framework\TestCase;

class StageCalculatorTest extends TestCase
{
    private StageCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StageCalculator();
    }

    public function test_new_company_starts_at_ice(): void
    {
        $stage = $this->calculator->calculate([]);
        
        $this->assertSame(Stage::ICE, $stage);
    }

    public function test_contact_attempted_moves_to_touched(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
        ];

        $stage = $this->calculator->calculate($events);
        
        $this->assertSame(Stage::TOUCHED, $stage);
    }

    public function test_decision_maker_call_moves_to_aware(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
        ];

        $stage = $this->calculator->calculate($events);
        
        $this->assertSame(Stage::AWARE, $stage);
    }

    public function test_discovery_filled_moves_to_interested(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
        ];

        $stage = $this->calculator->calculate($events);
        
        $this->assertSame(Stage::INTERESTED, $stage);
    }

    public function test_demo_scheduled_moves_to_demo_planned(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
        ];

        $stage = $this->calculator->calculate($events);
        
        $this->assertSame(Stage::DEMO_PLANNED, $stage);
    }

    public function test_demo_done_moves_to_demo_done_stage(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE),
        ];

        $stage = $this->calculator->calculate($events);
        
        $this->assertSame(Stage::DEMO_DONE, $stage);
    }

    public function test_invoice_issued_after_demo_moves_to_committed(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE),
            $this->createEvent(EventType::INVOICE_ISSUED),
        ];

        $stage = $this->calculator->calculate($events);
        
        $this->assertSame(Stage::COMMITTED, $stage);
    }

    public function test_payment_received_moves_to_customer(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE),
            $this->createEvent(EventType::INVOICE_ISSUED),
            $this->createEvent(EventType::PAYMENT_RECEIVED),
        ];

        $stage = $this->calculator->calculate($events);
        
        $this->assertSame(Stage::CUSTOMER, $stage);
    }

    public function test_first_credential_issued_moves_to_activated(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE),
            $this->createEvent(EventType::INVOICE_ISSUED),
            $this->createEvent(EventType::PAYMENT_RECEIVED),
            $this->createEvent(EventType::FIRST_CREDENTIAL_ISSUED),
        ];

        $stage = $this->calculator->calculate($events);
        
        $this->assertSame(Stage::ACTIVATED, $stage);
    }

    public function test_demo_older_than_60_days_is_invalid(): void
    {
        $now = new DateTimeImmutable();
        $oldDemo = $now->modify('-61 days');

        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE, createdAt: $oldDemo),
        ];

        $stage = $this->calculator->calculate($events);
        
        // Should fall back to demo_planned, not demo_done
        $this->assertSame(Stage::DEMO_PLANNED, $stage);
    }

    public function test_demo_exactly_60_days_ago_is_invalid(): void
    {
        $now = new DateTimeImmutable();
        $demo60DaysAgo = $now->modify('-60 days');

        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE, createdAt: $demo60DaysAgo),
        ];

        $stage = $this->calculator->calculate($events);
        
        // Exactly 60 days should be invalid (< 60 is valid)
        $this->assertSame(Stage::DEMO_PLANNED, $stage);
    }

    public function test_demo_59_days_ago_is_valid(): void
    {
        $now = new DateTimeImmutable();
        $demo59DaysAgo = $now->modify('-59 days');

        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE, createdAt: $demo59DaysAgo),
        ];

        $stage = $this->calculator->calculate($events);
        
        $this->assertSame(Stage::DEMO_DONE, $stage);
    }

    public function test_invoice_without_valid_demo_stays_at_demo_planned(): void
    {
        $now = new DateTimeImmutable();
        $oldDemo = $now->modify('-61 days');

        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE, createdAt: $oldDemo),
            $this->createEvent(EventType::INVOICE_ISSUED),
        ];

        $stage = $this->calculator->calculate($events);
        
        // Cannot be committed without valid demo
        $this->assertSame(Stage::DEMO_PLANNED, $stage);
    }

    public function test_latest_demo_is_used_for_validity_check(): void
    {
        $now = new DateTimeImmutable();
        $oldDemo = $now->modify('-70 days');
        $recentDemo = $now->modify('-10 days');

        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2025-12-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE, createdAt: $oldDemo),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-02-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE, createdAt: $recentDemo),
        ];

        $stage = $this->calculator->calculate($events);
        
        // Recent demo should make demo_done stage valid
        $this->assertSame(Stage::DEMO_DONE, $stage);
    }

    public function test_out_of_order_events_handled_correctly(): void
    {
        // Events might be retrieved in any order from DB
        $events = [
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
        ];

        $stage = $this->calculator->calculate($events);
        
        // Should still calculate correct stage
        $this->assertSame(Stage::INTERESTED, $stage);
    }

    // Helper method to create test events
    private function createEvent(
        EventType $type,
        ?array $data = null,
        ?DateTimeImmutable $createdAt = null
    ): Event {
        static $id = 1;
        
        return new Event(
            id: $id++,
            companyId: 1,
            type: $type,
            data: $data,
            createdAt: $createdAt ?? new DateTimeImmutable(),
        );
    }
}
