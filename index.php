<?php
require_once __DIR__ . '/config.php';

use Joomla\Component\CrmStages\Service\ServiceContainer;
use Joomla\Component\CrmStages\Domain\EventType;

$container = ServiceContainer::getInstance();
$crmService = $container->getCrmService();

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = null;
$error = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_company'])) {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new Exception('Company name is required');
            }
            $company = $crmService->createCompany($name);
            header('Location: company.php?id=' . $company->id);
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get data
if ($action === 'list') {
    $companies = $crmService->getAllCompanies();
}

$title = 'Companies - CRM Stages';
include __DIR__ . '/views/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($action === 'create'): ?>
    <div class="card">
        <h2>Add New Company</h2>
        <form method="POST">
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="name" class="form-control" required autofocus>
            </div>
            <button type="submit" name="create_company" class="btn btn-primary">Create Company</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Companies</h2>
        
        <?php if (empty($companies)): ?>
            <div class="alert alert-info">
                No companies yet. <a href="index.php?action=create">Create your first company</a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Current Stage</th>
                        <th>Events</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['company']->name) ?></strong>
                            </td>
                            <td>
                                <span class="stage-badge stage-<?= $item['stage']->value ?>">
                                    <?= htmlspecialchars($item['stage']->label()) ?>
                                </span>
                            </td>
                            <td><?= $item['eventCount'] ?> events</td>
                            <td>
                                <a href="company.php?id=<?= $item['company']->id ?>" class="btn btn-primary">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/views/footer.php'; ?>
