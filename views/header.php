<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'CRM Stages') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: #2c3e50;
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        header h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        header nav {
            margin-top: 0.5rem;
        }

        header nav a {
            color: #ecf0f1;
            text-decoration: none;
            margin-right: 1.5rem;
            font-size: 0.95rem;
        }

        header nav a:hover {
            color: #3498db;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .stage-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stage-ice { background: #ecf0f1; color: #7f8c8d; }
        .stage-touched { background: #d5e8f7; color: #2980b9; }
        .stage-aware { background: #d4edda; color: #28a745; }
        .stage-interested { background: #fff3cd; color: #856404; }
        .stage-demo_planned { background: #cce5ff; color: #004085; }
        .stage-demo_done { background: #b8daff; color: #004085; }
        .stage-committed { background: #d1ecf1; color: #0c5460; }
        .stage-customer { background: #d4edda; color: #155724; }
        .stage-activated { background: #c3e6cb; color: #155724; }

        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 4px;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .action-card {
            padding: 1rem;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-card:hover {
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }

        .action-card h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .action-card p {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .event-timeline {
            margin-top: 1rem;
        }

        .event-item {
            padding: 1rem;
            border-left: 3px solid #3498db;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 4px 4px 0;
        }

        .event-type {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .event-time {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .instructions {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 4px 4px 0;
        }

        .instructions h3 {
            margin-bottom: 0.5rem;
            color: #856404;
        }

        .instructions p {
            color: #856404;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>CRM Stages - Event-Driven Pipeline</h1>
            <nav>
                <a href="index.php">Companies</a>
                <a href="index.php?action=create">Add Company</a>
            </nav>
        </div>
    </header>
    <div class="container">
