<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Repository;

use Joomla\Component\CrmStages\Domain\Company;
use Joomla\Component\CrmStages\Domain\Event;
use Joomla\Component\CrmStages\Domain\EventType;
use Joomla\Component\CrmStages\Domain\Stage;
use PDO;

/**
 * Repository for company data access
 */
class CompanyRepository
{
    public function __construct(
        private readonly PDO $db,
    ) {
    }

    /**
     * Find company by ID
     */
    public function find(int $id): ?Company
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM companies WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Company::fromArray($row) : null;
    }

    /**
     * Get all companies
     * 
     * @return Company[]
     */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM companies ORDER BY name');
        $companies = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $companies[] = Company::fromArray($row);
        }

        return $companies;
    }

    /**
     * Update cached stage for a company
     */
    public function updateStage(int $companyId, Stage $stage): void
    {
        $stmt = $this->db->prepare(
            'UPDATE companies SET current_stage = ?, stage_updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$stage->value, $companyId]);
    }

    /**
     * Create a new company
     */
    public function create(string $name): Company
    {
        $stmt = $this->db->prepare(
            'INSERT INTO companies (name, created_at, updated_at) VALUES (?, NOW(), NOW())'
        );
        $stmt->execute([$name]);

        return $this->find((int) $this->db->lastInsertId());
    }
}
