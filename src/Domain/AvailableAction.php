<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Domain;

/**
 * Represents an action that can be performed on a company
 */
readonly class AvailableAction
{
    public function __construct(
        public EventType $eventType,
        public string $label,
        public string $description,
        public bool $requiresInput = false,
    ) {
    }
}
