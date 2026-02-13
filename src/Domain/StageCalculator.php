<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Domain;

use DateTimeImmutable;

/**
 * Calculates company stage based on event history
 * This is the heart of the event-driven architecture
 */
class StageCalculator
{
    private const DEMO_VALIDITY_DAYS = 60;

    /**
     * Calculate current stage from events
     * 
     * @param Event[] $events All events for a company, ordered by created_at ASC
     */
    public function calculate(array $events): Stage
    {
        if (empty($events)) {
            return Stage::ICE;
        }

        // Build event type index for quick lookup
        $eventsByType = $this->indexEventsByType($events);

        // Check conditions for each stage in reverse order (highest to lowest)
        if ($this->canBeActivated($eventsByType)) {
            return Stage::ACTIVATED;
        }

        if ($this->canBeCustomer($eventsByType)) {
            return Stage::CUSTOMER;
        }

        if ($this->canBeCommitted($eventsByType)) {
            return Stage::COMMITTED;
        }

        if ($this->canBeDemoDone($eventsByType)) {
            return Stage::DEMO_DONE;
        }

        if ($this->canBeDemoPlanned($eventsByType)) {
            return Stage::DEMO_PLANNED;
        }

        if ($this->canBeInterested($eventsByType)) {
            return Stage::INTERESTED;
        }

        if ($this->canBeAware($eventsByType)) {
            return Stage::AWARE;
        }

        if ($this->canBeTouched($eventsByType)) {
            return Stage::TOUCHED;
        }

        return Stage::ICE;
    }

    /**
     * Index events by type for efficient lookup
     * 
     * @param Event[] $events
     * @return array<string, Event[]>
     */
    private function indexEventsByType(array $events): array
    {
        $index = [];
        foreach ($events as $event) {
            $index[$event->type->value][] = $event;
        }
        return $index;
    }

    private function hasEvent(array $eventsByType, EventType $type): bool
    {
        return !empty($eventsByType[$type->value]);
    }

    private function getLatestEvent(array $eventsByType, EventType $type): ?Event
    {
        $events = $eventsByType[$type->value] ?? [];
        return end($events) ?: null;
    }

    private function canBeTouched(array $eventsByType): bool
    {
        return $this->hasEvent($eventsByType, EventType::CONTACT_ATTEMPTED);
    }

    private function canBeAware(array $eventsByType): bool
    {
        return $this->hasEvent($eventsByType, EventType::DECISION_MAKER_CALL_LOGGED);
    }

    private function canBeInterested(array $eventsByType): bool
    {
        return $this->hasEvent($eventsByType, EventType::DISCOVERY_FILLED);
    }

    private function canBeDemoPlanned(array $eventsByType): bool
    {
        return $this->hasEvent($eventsByType, EventType::DEMO_SCHEDULED);
    }

    private function canBeDemoDone(array $eventsByType): bool
    {
        if (!$this->hasEvent($eventsByType, EventType::DEMO_DONE)) {
            return false;
        }

        // Demo must have happened within the last 60 days
        $latestDemo = $this->getLatestEvent($eventsByType, EventType::DEMO_DONE);
        if (!$latestDemo) {
            return false;
        }

        $now = new DateTimeImmutable();
        $daysSinceDemo = $now->diff($latestDemo->createdAt)->days;

        return $daysSinceDemo < self::DEMO_VALIDITY_DAYS;
    }

    private function canBeCommitted(array $eventsByType): bool
    {
        // Must have valid demo_done AND invoice issued
        return $this->canBeDemoDone($eventsByType) 
            && $this->hasEvent($eventsByType, EventType::INVOICE_ISSUED);
    }

    private function canBeCustomer(array $eventsByType): bool
    {
        return $this->hasEvent($eventsByType, EventType::PAYMENT_RECEIVED);
    }

    private function canBeActivated(array $eventsByType): bool
    {
        return $this->hasEvent($eventsByType, EventType::FIRST_CREDENTIAL_ISSUED);
    }

    /**
     * Get the demo validity window in days
     */
    public function getDemoValidityDays(): int
    {
        return self::DEMO_VALIDITY_DAYS;
    }
}
