<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Tests\Unit;

use DateTimeImmutable;
use Joomla\Component\CrmStages\Domain\Event;
use Joomla\Component\CrmStages\Domain\EventGuard;
use Joomla\Component\CrmStages\Domain\EventGuardException;
use Joomla\Component\CrmStages\Domain\EventType;
use Joomla\Component\CrmStages\Domain\StageCalculator;
use PHPUnit\Framework\TestCase;

class EventGuardTest extends TestCase
{
    private EventGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new EventGuard(new StageCalculator());
    }

    public function test_contact_attempted_always_allowed(): void
    {
        $this->guard->validate(EventType::CONTACT_ATTEMPTED, null, []);
        
        $this->assertTrue(true); // No exception thrown
    }

    public function test_decision_maker_call_requires_contact_first(): void
    {
        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('contact_attempted');
        
        $this->guard->validate(
            EventType::DECISION_MAKER_CALL_LOGGED,
            ['comment' => 'Test call'],
            []
        );
    }

    public function test_decision_maker_call_requires_comment(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
        ];

        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('Comment is required');
        
        $this->guard->validate(
            EventType::DECISION_MAKER_CALL_LOGGED,
            ['comment' => ''],
            $events
        );
    }

    public function test_decision_maker_call_allowed_after_contact(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
        ];

        $this->guard->validate(
            EventType::DECISION_MAKER_CALL_LOGGED,
            ['comment' => 'Great conversation'],
            $events
        );
        
        $this->assertTrue(true);
    }

    public function test_discovery_filled_requires_decision_maker_call(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
        ];

        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('decision_maker_call_logged');
        
        $this->guard->validate(EventType::DISCOVERY_FILLED, null, $events);
    }

    public function test_discovery_filled_allowed_after_call(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
        ];

        $this->guard->validate(EventType::DISCOVERY_FILLED, null, $events);
        
        $this->assertTrue(true);
    }

    public function test_demo_scheduled_requires_discovery(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
        ];

        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('discovery_filled');
        
        $this->guard->validate(
            EventType::DEMO_SCHEDULED,
            ['scheduled_at' => '2026-03-01 14:00:00'],
            $events
        );
    }

    public function test_demo_scheduled_requires_date(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
        ];

        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('Scheduled date/time is required');
        
        $this->guard->validate(EventType::DEMO_SCHEDULED, ['scheduled_at' => ''], $events);
    }

    public function test_demo_done_requires_demo_scheduled(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
        ];

        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('demo_scheduled');
        
        $this->guard->validate(EventType::DEMO_DONE, null, $events);
    }

    public function test_demo_done_allowed_after_scheduled(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
        ];

        $this->guard->validate(EventType::DEMO_DONE, null, $events);
        
        $this->assertTrue(true);
    }

    public function test_invoice_requires_demo_done(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']),
        ];

        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('demo_done');
        
        $this->guard->validate(EventType::INVOICE_ISSUED, null, $events);
    }

    public function test_invoice_requires_demo_within_60_days(): void
    {
        $now = new DateTimeImmutable();
        $oldDemo = $now->modify('-61 days');

        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2025-12-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE, createdAt: $oldDemo),
        ];

        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('Demo was 61 days ago');
        
        $this->guard->validate(EventType::INVOICE_ISSUED, null, $events);
    }

    public function test_invoice_allowed_with_recent_demo(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-02-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE),
        ];

        $this->guard->validate(EventType::INVOICE_ISSUED, null, $events);
        
        $this->assertTrue(true);
    }

    public function test_payment_requires_invoice(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-02-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE),
        ];

        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('invoice_issued');
        
        $this->guard->validate(EventType::PAYMENT_RECEIVED, null, $events);
    }

    public function test_credential_requires_payment(): void
    {
        $events = [
            $this->createEvent(EventType::CONTACT_ATTEMPTED),
            $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']),
            $this->createEvent(EventType::DISCOVERY_FILLED),
            $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-02-01 14:00:00']),
            $this->createEvent(EventType::DEMO_DONE),
            $this->createEvent(EventType::INVOICE_ISSUED),
        ];

        $this->expectException(EventGuardException::class);
        $this->expectExceptionMessage('payment_received');
        
        $this->guard->validate(EventType::FIRST_CREDENTIAL_ISSUED, null, $events);
    }

    public function test_full_happy_path(): void
    {
        $events = [];

        // Each step should be valid
        $this->guard->validate(EventType::CONTACT_ATTEMPTED, null, $events);
        $events[] = $this->createEvent(EventType::CONTACT_ATTEMPTED);

        $this->guard->validate(
            EventType::DECISION_MAKER_CALL_LOGGED,
            ['comment' => 'Great call'],
            $events
        );
        $events[] = $this->createEvent(EventType::DECISION_MAKER_CALL_LOGGED, ['comment' => 'Great call']);

        $this->guard->validate(EventType::DISCOVERY_FILLED, null, $events);
        $events[] = $this->createEvent(EventType::DISCOVERY_FILLED);

        $this->guard->validate(
            EventType::DEMO_SCHEDULED,
            ['scheduled_at' => '2026-03-01 14:00:00'],
            $events
        );
        $events[] = $this->createEvent(EventType::DEMO_SCHEDULED, ['scheduled_at' => '2026-03-01 14:00:00']);

        $this->guard->validate(EventType::DEMO_DONE, null, $events);
        $events[] = $this->createEvent(EventType::DEMO_DONE);

        $this->guard->validate(EventType::INVOICE_ISSUED, null, $events);
        $events[] = $this->createEvent(EventType::INVOICE_ISSUED);

        $this->guard->validate(EventType::PAYMENT_RECEIVED, null, $events);
        $events[] = $this->createEvent(EventType::PAYMENT_RECEIVED);

        $this->guard->validate(EventType::FIRST_CREDENTIAL_ISSUED, null, $events);
        
        $this->assertTrue(true);
    }

    // Helper method
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
