<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Domain;

/**
 * Exception thrown when an event cannot be created due to business rules
 */
class EventGuardException extends \DomainException
{
    public static function missingRequiredData(EventType $type): self
    {
        return new self("Event {$type->value} requires additional data");
    }

    public static function invalidData(EventType $type, string $reason): self
    {
        return new self("Event {$type->value} has invalid data: {$reason}");
    }

    public static function stageMismatch(EventType $type, Stage $currentStage, Stage $requiredStage): self
    {
        return new self(
            "Cannot create event {$type->value}. Company is at stage {$currentStage->value}, " .
            "but must be at {$requiredStage->value} or later"
        );
    }

    public static function missingPreviousEvent(EventType $type, EventType $requiredEvent): self
    {
        return new self(
            "Cannot create event {$type->value} without first having {$requiredEvent->value}"
        );
    }

    public static function demoExpired(int $daysAgo, int $maxDays): self
    {
        return new self(
            "Demo was {$daysAgo} days ago, but must be within {$maxDays} days to proceed"
        );
    }
}
