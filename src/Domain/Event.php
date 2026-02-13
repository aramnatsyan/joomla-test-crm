<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Domain;

use DateTimeImmutable;

/**
 * Immutable event entity
 */
readonly class Event
{
    public function __construct(
        public int $id,
        public int $companyId,
        public EventType $type,
        public ?array $data,
        public DateTimeImmutable $createdAt,
        public ?int $createdBy = null,
    ) {
    }

    /**
     * Create from database row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            companyId: (int) $row['company_id'],
            type: EventType::from($row['event_type']),
            data: $row['event_data'] ? json_decode($row['event_data'], true) : null,
            createdAt: new DateTimeImmutable($row['created_at']),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
        );
    }

    /**
     * Get event data value by key
     */
    public function getData(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
