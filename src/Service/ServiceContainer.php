<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Service;

use Joomla\Component\CrmStages\Domain\ActionResolver;
use Joomla\Component\CrmStages\Domain\EventGuard;
use Joomla\Component\CrmStages\Domain\StageCalculator;
use Joomla\Component\CrmStages\Repository\CompanyRepository;
use Joomla\Component\CrmStages\Repository\EventRepository;
use PDO;

/**
 * Simple service container for dependency injection
 */
class ServiceContainer
{
    private static ?self $instance = null;
    private ?PDO $db = null;
    private ?CrmService $crmService = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setDatabase(PDO $db): void
    {
        $this->db = $db;
    }

    public function getDatabase(): PDO
    {
        if ($this->db === null) {
            throw new \RuntimeException('Database not configured');
        }
        return $this->db;
    }

    public function getCrmService(): CrmService
    {
        if ($this->crmService === null) {
            $db = $this->getDatabase();
            
            $companyRepo = new CompanyRepository($db);
            $eventRepo = new EventRepository($db);
            $stageCalculator = new StageCalculator();
            $eventGuard = new EventGuard($stageCalculator);
            $actionResolver = new ActionResolver($stageCalculator, $eventGuard);

            $this->crmService = new CrmService(
                $companyRepo,
                $eventRepo,
                $stageCalculator,
                $eventGuard,
                $actionResolver
            );
        }

        return $this->crmService;
    }
}
