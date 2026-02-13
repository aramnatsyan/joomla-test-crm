<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Domain;

/**
 * Determines which actions are available based on current stage
 * Only shows actions that would be valid according to business rules
 */
class ActionResolver
{
    public function __construct(
        private readonly StageCalculator $stageCalculator,
        private readonly EventGuard $guard,
    ) {
    }

    /**
     * Get available actions for a company given its event history
     * 
     * @param Event[] $events
     * @return AvailableAction[]
     */
    public function getAvailableActions(array $events): array
    {
        $currentStage = $this->stageCalculator->calculate($events);
        $actions = [];

        // Check each possible event type to see if it's allowed
        foreach (EventType::cases() as $eventType) {
            if ($this->isActionAvailable($eventType, $events)) {
                $actions[] = $this->createAction($eventType, $currentStage);
            }
        }

        return $actions;
    }

    /**
     * Check if an action is currently available
     */
    private function isActionAvailable(EventType $type, array $events): bool
    {
        try {
            // Create minimal valid data for validation
            $data = match($type) {
                EventType::DECISION_MAKER_CALL_LOGGED => ['comment' => 'test'],
                EventType::DEMO_SCHEDULED => ['scheduled_at' => date('Y-m-d H:i:s')],
                default => null,
            };

            $this->guard->validate($type, $data, $events);
            return true;
        } catch (EventGuardException) {
            return false;
        }
    }

    /**
     * Create action descriptor
     */
    private function createAction(EventType $type, Stage $currentStage): AvailableAction
    {
        return match($type) {
            EventType::CONTACT_ATTEMPTED => new AvailableAction(
                $type,
                'Log Contact Attempt',
                'Record that you attempted to reach the company',
            ),
            EventType::DECISION_MAKER_CALL_LOGGED => new AvailableAction(
                $type,
                'Log Decision Maker Call',
                'Record a call with the decision maker',
                requiresInput: true,
            ),
            EventType::DISCOVERY_FILLED => new AvailableAction(
                $type,
                'Mark Discovery Complete',
                'Confirm that discovery form has been filled',
            ),
            EventType::DEMO_SCHEDULED => new AvailableAction(
                $type,
                'Schedule Demo',
                'Set a date and time for the product demo',
                requiresInput: true,
            ),
            EventType::DEMO_DONE => new AvailableAction(
                $type,
                'Mark Demo Complete',
                'Confirm that the demo has been completed',
            ),
            EventType::INVOICE_ISSUED => new AvailableAction(
                $type,
                'Issue Invoice',
                'Generate and send invoice to the customer',
            ),
            EventType::PAYMENT_RECEIVED => new AvailableAction(
                $type,
                'Record Payment',
                'Confirm that payment has been received',
            ),
            EventType::FIRST_CREDENTIAL_ISSUED => new AvailableAction(
                $type,
                'Issue Credentials',
                'Create and send first set of access credentials',
            ),
        };
    }

    /**
     * Get the primary/recommended next action
     * 
     * @param Event[] $events
     */
    public function getNextAction(array $events): ?AvailableAction
    {
        $available = $this->getAvailableActions($events);
        return $available[0] ?? null;
    }
}
