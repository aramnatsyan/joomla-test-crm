<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Domain;

/**
 * CRM stages that companies progress through
 */
enum Stage: string
{
    case ICE = 'ice';
    case TOUCHED = 'touched';
    case AWARE = 'aware';
    case INTERESTED = 'interested';
    case DEMO_PLANNED = 'demo_planned';
    case DEMO_DONE = 'demo_done';
    case COMMITTED = 'committed';
    case CUSTOMER = 'customer';
    case ACTIVATED = 'activated';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::ICE => 'Ice',
            self::TOUCHED => 'Touched',
            self::AWARE => 'Aware',
            self::INTERESTED => 'Interested',
            self::DEMO_PLANNED => 'Demo Planned',
            self::DEMO_DONE => 'Demo Done',
            self::COMMITTED => 'Committed',
            self::CUSTOMER => 'Customer',
            self::ACTIVATED => 'Activated',
        };
    }

    /**
     * Get instructions for this stage
     */
    public function instructions(): string
    {
        return match($this) {
            self::ICE => 'Initial contact needed. Attempt to reach out to the company.',
            self::TOUCHED => 'Contact established. Schedule a call with decision maker.',
            self::AWARE => 'Decision maker aware. Complete discovery to understand needs.',
            self::INTERESTED => 'Interest confirmed. Schedule a product demo.',
            self::DEMO_PLANNED => 'Demo scheduled. Prepare materials and conduct the demo.',
            self::DEMO_DONE => 'Demo completed. Follow up and move towards commitment.',
            self::COMMITTED => 'Company committed. Issue invoice for payment.',
            self::CUSTOMER => 'Payment received. Issue first credentials to activate.',
            self::ACTIVATED => 'Customer activated. Monitor usage and provide support.',
        };
    }

    /**
     * Get the next stage in progression
     */
    public function next(): ?self
    {
        return match($this) {
            self::ICE => self::TOUCHED,
            self::TOUCHED => self::AWARE,
            self::AWARE => self::INTERESTED,
            self::INTERESTED => self::DEMO_PLANNED,
            self::DEMO_PLANNED => self::DEMO_DONE,
            self::DEMO_DONE => self::COMMITTED,
            self::COMMITTED => self::CUSTOMER,
            self::CUSTOMER => self::ACTIVATED,
            self::ACTIVATED => null,
        };
    }
}
