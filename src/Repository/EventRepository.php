<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Repository;

use Joomla\Component\CrmStages\Domain\Event;
use Joomla\Component\CrmStages\Domain\EventType;
use PDO;

/**
 * Repository for event data access (append-only)
 */
class EventRepository
{
    public function __construct(
        private readonly PDO $db,
    ) {
    }

    /**
     * Get all events for a company
     * 
     * @return Event[]
     */
    public function findByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM events WHERE company_id = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$companyId]);

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = Event::fromArray($row);
        }

        return $events;
    }

    /**
     * Append a new event (never update or delete)
     */
    public function append(
        int $companyId,
        EventType $type,
        ?array $data = null,
        ?int $createdBy = null
    ): Event {
        $stmt = $this->db->prepare(
            'INSERT INTO events (company_id, event_type, event_data, created_by, created_at) 
             VALUES (?, ?, ?, ?, NOW())'
        );
        
        $stmt->execute([
            $companyId,
            $type->value,
            $data ? json_encode($data) : null,
            $createdBy,
        ]);

        $eventId = (int) $this->db->lastInsertId();

        // Fetch the created event
        $stmt = $this->db->prepare('SELECT * FROM events WHERE id = ?');
        $stmt->execute([$eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return Event::fromArray($row);
    }

    /**
     * Get recent events across all companies
     * 
     * @return Event[]
     */
    public function findRecent(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM events ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = Event::fromArray($row);
        }

        return $events;
    }
}
