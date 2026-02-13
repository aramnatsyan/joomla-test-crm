<?php

declare(strict_types=1);

namespace Joomla\Component\CrmStages\Domain;

/**
 * Event types that drive stage progression
 */
enum EventType: string
{
    case CONTACT_ATTEMPTED = 'contact_attempted';
    case DECISION_MAKER_CALL_LOGGED = 'decision_maker_call_logged';
    case DISCOVERY_FILLED = 'discovery_filled';
    case DEMO_SCHEDULED = 'demo_scheduled';
    case DEMO_DONE = 'demo_done';
    case INVOICE_ISSUED = 'invoice_issued';
    case PAYMENT_RECEIVED = 'payment_received';
    case FIRST_CREDENTIAL_ISSUED = 'first_credential_issued';

    /**
     * Get human-readable label for the event
     */
    public function label(): string
    {
        return match($this) {
            self::CONTACT_ATTEMPTED => 'Contact Attempted',
            self::DECISION_MAKER_CALL_LOGGED => 'Decision Maker Call Logged',
            self::DISCOVERY_FILLED => 'Discovery Form Filled',
            self::DEMO_SCHEDULED => 'Demo Scheduled',
            self::DEMO_DONE => 'Demo Completed',
            self::INVOICE_ISSUED => 'Invoice Issued',
            self::PAYMENT_RECEIVED => 'Payment Received',
            self::FIRST_CREDENTIAL_ISSUED => 'First Credential Issued',
        };
    }

    /**
     * Check if this event requires additional data
     */
    public function requiresData(): bool
    {
        return match($this) {
            self::DECISION_MAKER_CALL_LOGGED => true, // requires comment
            self::DEMO_SCHEDULED => true, // requires date/time
            default => false,
        };
    }

    /**
     * Validate event data structure
     */
    public function validateData(?array $data): bool
    {
        return match($this) {
            self::DECISION_MAKER_CALL_LOGGED => isset($data['comment']) && !empty(trim($data['comment'])),
            self::DEMO_SCHEDULED => isset($data['scheduled_at']) && !empty($data['scheduled_at']),
            default => true,
        };
    }
}
