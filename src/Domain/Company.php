<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Domain;

use DateTimeImmutable;

/**
 * Company entity
 */
readonly class Company
{
    public function __construct(
        public int $id,
        public string $name,
        public ?Stage $currentStage = null,
        public ?DateTimeImmutable $stageUpdatedAt = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    /**
     * Create from database row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: $row['name'],
            currentStage: isset($row['current_stage']) ? Stage::from($row['current_stage']) : null,
            stageUpdatedAt: isset($row['stage_updated_at']) ? new DateTimeImmutable($row['stage_updated_at']) : null,
            createdAt: isset($row['created_at']) ? new DateTimeImmutable($row['created_at']) : null,
            updatedAt: isset($row['updated_at']) ? new DateTimeImmutable($row['updated_at']) : null,
        );
    }
}
