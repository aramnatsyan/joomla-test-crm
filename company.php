<?php
require_once __DIR__ . '/config.php';

use Joomla\Component\CrmStages\Service\ServiceContainer;
use Joomla\Component\CrmStages\Domain\EventType;
use Joomla\Component\CrmStages\Domain\EventGuardException;

$container = ServiceContainer::getInstance();
$crmService = $container->getCrmService();

$companyId = (int) ($_GET['id'] ?? 0);
if ($companyId === 0) {
    header('Location: index.php');
    exit;
}

$message = null;
$error = null;

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_type'])) {
    try {
        $eventType = EventType::from($_POST['event_type']);
        $data = null;

        // Prepare event data based on type
        if ($eventType === EventType::DECISION_MAKER_CALL_LOGGED) {
            $comment = trim($_POST['comment'] ?? '');
            if (empty($comment)) {
                throw new Exception('Comment is required');
            }
            $data = ['comment' => $comment];
        } elseif ($eventType === EventType::DEMO_SCHEDULED) {
            $scheduledAt = trim($_POST['scheduled_at'] ?? '');
            if (empty($scheduledAt)) {
                throw new Exception('Scheduled date/time is required');
            }
            $data = ['scheduled_at' => $scheduledAt];
        }

        $crmService->createEvent($companyId, $eventType, $data);
        $message = "Event '{$eventType->label()}' created successfully!";
        
        // Redirect to avoid form resubmission
        header('Location: company.php?id=' . $companyId . '&success=1');
        exit;
    } catch (EventGuardException $e) {
        $error = "Business Rule Violation: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get company data
$companyData = $crmService->getCompanyWithStage($companyId);
if (!$companyData) {
    header('Location: index.php');
    exit;
}

$company = $companyData['company'];
$stage = $companyData['stage'];
$events = $companyData['events'];
$availableActions = $companyData['availableActions'];
$nextAction = $companyData['nextAction'];

if (isset($_GET['success'])) {
    $message = "Action completed successfully!";
}

$title = htmlspecialchars($company->name) . ' - CRM Stages';
include __DIR__ . '/views/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <strong>Error:</strong> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card">
    <h2><?= htmlspecialchars($company->name) ?></h2>
    <p>
        <strong>Current Stage:</strong>
        <span class="stage-badge stage-<?= $stage->value ?>">
            <?= htmlspecialchars($stage->label()) ?>
        </span>
    </p>
    <p><a href="index.php" class="btn btn-secondary">← Back to Companies</a></p>
</div>

<div class="instructions">
    <h3>📋 What to do at this stage</h3>
    <p><?= htmlspecialchars($stage->instructions()) ?></p>
</div>

<div class="card">
    <h2>Available Actions</h2>
    
    <?php if (empty($availableActions)): ?>
        <div class="alert alert-info">
            No actions available. All stages completed! 🎉
        </div>
    <?php else: ?>
        <div class="actions-grid">
            <?php foreach ($availableActions as $action): ?>
                <div class="action-card" onclick="showEventForm('<?= $action->eventType->value ?>', <?= $action->requiresInput ? 'true' : 'false' ?>)">
                    <h4><?= htmlspecialchars($action->label) ?></h4>
                    <p><?= htmlspecialchars($action->description) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Event form modal -->
<div id="eventModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; padding: 20px;">
    <div style="max-width: 600px; margin: 50px auto; background: white; padding: 2rem; border-radius: 8px;">
        <h3 id="modalTitle">Create Event</h3>
        <form method="POST" id="eventForm">
            <input type="hidden" name="event_type" id="eventTypeInput">
            
            <div id="commentField" style="display: none;" class="form-group">
                <label>Call Notes / Comment <span style="color: red;">*</span></label>
                <textarea name="comment" class="form-control" rows="4" placeholder="Describe the conversation..."></textarea>
            </div>
            
            <div id="scheduledField" style="display: none;" class="form-group">
                <label>Scheduled Date/Time <span style="color: red;">*</span></label>
                <input type="datetime-local" name="scheduled_at" class="form-control">
            </div>
            
            <div id="confirmField" style="display: none;" class="form-group">
                <p id="confirmMessage">Are you sure you want to create this event?</p>
            </div>
            
            <button type="submit" class="btn btn-success">Confirm & Create Event</button>
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<div class="card">
    <h2>Event History (<?= count($events) ?>)</h2>
    
    <?php if (empty($events)): ?>
        <p style="color: #7f8c8d;">No events yet. Create your first event using the actions above.</p>
    <?php else: ?>
        <div class="event-timeline">
            <?php foreach (array_reverse($events) as $event): ?>
                <div class="event-item">
                    <div class="event-type">
                        <?= htmlspecialchars($event->type->label()) ?>
                    </div>
                    <?php if ($event->data): ?>
                        <div style="font-size: 0.9rem; color: #555; margin: 0.25rem 0;">
                            <?php if (isset($event->data['comment'])): ?>
                                <strong>Notes:</strong> <?= htmlspecialchars($event->data['comment']) ?>
                            <?php endif; ?>
                            <?php if (isset($event->data['scheduled_at'])): ?>
                                <strong>Scheduled:</strong> <?= htmlspecialchars($event->data['scheduled_at']) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="event-time">
                        <?= $event->createdAt->format('F j, Y g:i A') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function showEventForm(eventType, requiresInput) {
    document.getElementById('eventTypeInput').value = eventType;
    document.getElementById('modalTitle').textContent = 'Create Event: ' + eventType.replace(/_/g, ' ').toUpperCase();
    
    // Hide all fields
    document.getElementById('commentField').style.display = 'none';
    document.getElementById('scheduledField').style.display = 'none';
    document.getElementById('confirmField').style.display = 'none';
    
    // Show relevant field
    if (eventType === 'decision_maker_call_logged') {
        document.getElementById('commentField').style.display = 'block';
    } else if (eventType === 'demo_scheduled') {
        document.getElementById('scheduledField').style.display = 'block';
    } else {
        document.getElementById('confirmField').style.display = 'block';
    }
    
    document.getElementById('eventModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('eventModal').style.display = 'none';
    document.getElementById('eventForm').reset();
}

// Close modal on outside click
document.getElementById('eventModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include __DIR__ . '/views/footer.php'; ?>
