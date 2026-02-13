<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Domain;

use DateTimeImmutable;

/**
 * Guards business rules for event creation
 * Ensures events can only be created when business conditions are met
 */
class EventGuard
{
    private const DEMO_VALIDITY_DAYS = 60;

    public function __construct(
        private readonly StageCalculator $stageCalculator,
    ) {
    }

    /**
     * Validate that an event can be created given current company state
     * 
     * @param Event[] $existingEvents All events for the company
     * @throws EventGuardException if the event cannot be created
     */
    public function validate(EventType $type, ?array $data, array $existingEvents): void
    {
        // Validate business rules based on event type (includes data validation)
        match($type) {
            EventType::CONTACT_ATTEMPTED => $this->validateContactAttempted($existingEvents),
            EventType::DECISION_MAKER_CALL_LOGGED => $this->validateDecisionMakerCall($data, $existingEvents),
            EventType::DISCOVERY_FILLED => $this->validateDiscoveryFilled($existingEvents),
            EventType::DEMO_SCHEDULED => $this->validateDemoScheduled($data, $existingEvents),
            EventType::DEMO_DONE => $this->validateDemoDone($existingEvents),
            EventType::INVOICE_ISSUED => $this->validateInvoiceIssued($existingEvents),
            EventType::PAYMENT_RECEIVED => $this->validatePaymentReceived($existingEvents),
            EventType::FIRST_CREDENTIAL_ISSUED => $this->validateFirstCredentialIssued($existingEvents),
        };
    }

    private function validateContactAttempted(array $events): void
    {
        // Can always attempt contact
    }

    private function validateDecisionMakerCall(?array $data, array $events): void
    {
        // Require contact to have been attempted
        if (!$this->hasEventType($events, EventType::CONTACT_ATTEMPTED)) {
            throw EventGuardException::missingPreviousEvent(
                EventType::DECISION_MAKER_CALL_LOGGED,
                EventType::CONTACT_ATTEMPTED
            );
        }

        // Validate comment is present
        if (empty($data['comment'] ?? '')) {
            throw EventGuardException::invalidData(
                EventType::DECISION_MAKER_CALL_LOGGED,
                'Comment is required'
            );
        }
    }

    private function validateDiscoveryFilled(array $events): void
    {
        // Require decision maker call to have been logged
        if (!$this->hasEventType($events, EventType::DECISION_MAKER_CALL_LOGGED)) {
            throw EventGuardException::missingPreviousEvent(
                EventType::DISCOVERY_FILLED,
                EventType::DECISION_MAKER_CALL_LOGGED
            );
        }
    }

    private function validateDemoScheduled(?array $data, array $events): void
    {
        // Require discovery to be filled
        if (!$this->hasEventType($events, EventType::DISCOVERY_FILLED)) {
            throw EventGuardException::missingPreviousEvent(
                EventType::DEMO_SCHEDULED,
                EventType::DISCOVERY_FILLED
            );
        }

        // Validate scheduled date/time
        if (empty($data['scheduled_at'] ?? '')) {
            throw EventGuardException::invalidData(
                EventType::DEMO_SCHEDULED,
                'Scheduled date/time is required'
            );
        }
    }

    private function validateDemoDone(array $events): void
    {
        // Require demo to be scheduled
        if (!$this->hasEventType($events, EventType::DEMO_SCHEDULED)) {
            throw EventGuardException::missingPreviousEvent(
                EventType::DEMO_DONE,
                EventType::DEMO_SCHEDULED
            );
        }
    }

    private function validateInvoiceIssued(array $events): void
    {
        // Require demo to be done AND valid (within 60 days)
        if (!$this->hasEventType($events, EventType::DEMO_DONE)) {
            throw EventGuardException::missingPreviousEvent(
                EventType::INVOICE_ISSUED,
                EventType::DEMO_DONE
            );
        }

        // Check demo validity
        $latestDemo = $this->getLatestEventOfType($events, EventType::DEMO_DONE);
        if ($latestDemo) {
            $daysSinceDemo = (new DateTimeImmutable())->diff($latestDemo->createdAt)->days;
            if ($daysSinceDemo >= self::DEMO_VALIDITY_DAYS) {
                throw EventGuardException::demoExpired($daysSinceDemo, self::DEMO_VALIDITY_DAYS);
            }
        }
    }

    private function validatePaymentReceived(array $events): void
    {
        // Require invoice to be issued
        if (!$this->hasEventType($events, EventType::INVOICE_ISSUED)) {
            throw EventGuardException::missingPreviousEvent(
                EventType::PAYMENT_RECEIVED,
                EventType::INVOICE_ISSUED
            );
        }
    }

    private function validateFirstCredentialIssued(array $events): void
    {
        // Require payment to be received
        if (!$this->hasEventType($events, EventType::PAYMENT_RECEIVED)) {
            throw EventGuardException::missingPreviousEvent(
                EventType::FIRST_CREDENTIAL_ISSUED,
                EventType::PAYMENT_RECEIVED
            );
        }
    }

    private function hasEventType(array $events, EventType $type): bool
    {
        foreach ($events as $event) {
            if ($event->type === $type) {
                return true;
            }
        }
        return false;
    }

    private function getLatestEventOfType(array $events, EventType $type): ?Event
    {
        $found = null;
        foreach ($events as $event) {
            if ($event->type === $type) {
                $found = $event;
            }
        }
        return $found;
    }
}
