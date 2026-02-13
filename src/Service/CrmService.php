<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Service;

use Joomla\Component\CrmStages\Domain\ActionResolver;
use Joomla\Component\CrmStages\Domain\Company;
use Joomla\Component\CrmStages\Domain\Event;
use Joomla\Component\CrmStages\Domain\EventGuard;
use Joomla\Component\CrmStages\Domain\EventType;
use Joomla\Component\CrmStages\Domain\Stage;
use Joomla\Component\CrmStages\Domain\StageCalculator;
use Joomla\Component\CrmStages\Repository\CompanyRepository;
use Joomla\Component\CrmStages\Repository\EventRepository;

/**
 * Application service for CRM operations
 */
class CrmService
{
    public function __construct(
        private readonly CompanyRepository $companyRepo,
        private readonly EventRepository $eventRepo,
        private readonly StageCalculator $stageCalculator,
        private readonly EventGuard $eventGuard,
        private readonly ActionResolver $actionResolver,
    ) {
    }

    /**
     * Get company with calculated stage
     */
    public function getCompanyWithStage(int $companyId): ?array
    {
        $company = $this->companyRepo->find($companyId);
        if (!$company) {
            return null;
        }

        $events = $this->eventRepo->findByCompany($companyId);
        $stage = $this->stageCalculator->calculate($events);

        // Update cached stage if changed
        if ($company->currentStage !== $stage) {
            $this->companyRepo->updateStage($companyId, $stage);
        }

        return [
            'company' => $company,
            'stage' => $stage,
            'events' => $events,
            'availableActions' => $this->actionResolver->getAvailableActions($events),
            'nextAction' => $this->actionResolver->getNextAction($events),
        ];
    }

    /**
     * Create an event for a company
     * 
     * @throws \Joomla\Component\CrmStages\Domain\EventGuardException
     */
    public function createEvent(
        int $companyId,
        EventType $eventType,
        ?array $data = null,
        ?int $userId = null
    ): Event {
        // Load existing events
        $existingEvents = $this->eventRepo->findByCompany($companyId);

        // Validate the event can be created
        $this->eventGuard->validate($eventType, $data, $existingEvents);

        // Append event
        $event = $this->eventRepo->append($companyId, $eventType, $data, $userId);

        // Recalculate and update stage
        $allEvents = $this->eventRepo->findByCompany($companyId);
        $newStage = $this->stageCalculator->calculate($allEvents);
        $this->companyRepo->updateStage($companyId, $newStage);

        return $event;
    }

    /**
     * Get all companies with their stages
     */
    public function getAllCompanies(): array
    {
        $companies = $this->companyRepo->findAll();
        $result = [];

        foreach ($companies as $company) {
            $events = $this->eventRepo->findByCompany($company->id);
            $stage = $this->stageCalculator->calculate($events);

            $result[] = [
                'company' => $company,
                'stage' => $stage,
                'eventCount' => count($events),
            ];
        }

        return $result;
    }

    /**
     * Create a new company
     */
    public function createCompany(string $name): Company
    {
        return $this->companyRepo->create($name);
    }
}
