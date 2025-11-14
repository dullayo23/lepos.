<?php
require_once '../includes/auth.php';
requireLogin();

$user = getCurrentUser();

// Handle delete lesson plan from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_lesson'])) {
    $lesson_id = $_POST['lesson_id'] ?? null;
    if ($lesson_id) {
        [$stmt, $result, $conn] = q(
            "DELETE FROM lesson_plans WHERE id = ? AND user_id = ?",
            'ii',
            [$lesson_id, $user['id']]
        );

        if ($stmt && $stmt->affected_rows > 0) {
            $_SESSION['success_message'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Lesson plan deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } else {
            $_SESSION['error_message'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Failed to delete lesson plan. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
        if ($stmt) $stmt->close();
        if ($conn) $conn->close();
        
        // Refresh the page to show updated list
        header("Location: dashboard.php");
        exit();
    }
}

// Check for success/error messages from session
if (isset($_SESSION['success_message'])) {
    $delete_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $delete_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Check if we're in list view mode
$action = $_GET['action'] ?? 'dashboard';
$is_list_view = $action === 'list';

// Initialize stats with default values
$stats = [
    'total_lessons' => 0,
    'pending_lessons' => 0,
    'in_progress_lessons' => 0,
    'completed_lessons' => 0,
    'review_lessons' => 0,
    'overdue_lessons' => 0
];

// Get enhanced statistics for dashboard with progress tracking
try {
    [$stmt, $result, $conn] = q(
        "SELECT 
            COUNT(*) as total_lessons,
            SUM(CASE WHEN progress_status = 'pending' THEN 1 ELSE 0 END) as pending_lessons,
            SUM(CASE WHEN progress_status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_lessons,
            SUM(CASE WHEN progress_status = 'completed' THEN 1 ELSE 0 END) as completed_lessons,
            SUM(CASE WHEN progress_status = 'review' THEN 1 ELSE 0 END) as review_lessons,
            SUM(CASE WHEN due_date < CURDATE() AND progress_status != 'completed' THEN 1 ELSE 0 END) as overdue_lessons
         FROM lesson_plans WHERE user_id = ?",
        'i',
        [$user['id']]
    );
    
    if ($result) {
        $stats = $result->fetch_assoc();
        if (!$stats) {
            $stats = [
                'total_lessons' => 0,
                'pending_lessons' => 0,
                'in_progress_lessons' => 0,
                'completed_lessons' => 0,
                'review_lessons' => 0,
                'overdue_lessons' => 0
            ];
        }
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    // If there's an error (like missing columns), use default stats
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Get all lesson plans for list view
$all_lessons = [];
try {
    [$stmt, $result, $conn] = q(
        "SELECT * FROM lesson_plans WHERE user_id = ? ORDER BY created_at DESC",
        'i',
        [$user['id']]
    );
    $all_lessons = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("All lessons error: " . $e->getMessage());
}

// Get recent lesson plans with progress status (for dashboard view)
$recent_lessons = [];
if (!$is_list_view) {
    try {
        [$stmt, $result, $conn] = q(
            "SELECT * FROM lesson_plans WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
            'i',
            [$user['id']]
        );
        $recent_lessons = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Recent lessons error: " . $e->getMessage());
    }
}

// Get upcoming lessons (next 7 days)
$upcoming_lessons = [];
try {
    [$stmt, $result, $conn] = q(
        "SELECT * FROM lesson_plans WHERE user_id = ? AND start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY start_date ASC LIMIT 3",
        'i',
        [$user['id']]
    );
    $upcoming_lessons = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Upcoming lessons error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $is_list_view ? 'All Lesson Plans' : 'Dashboard'; ?> - LEPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .card-total { border-left-color: var(--primary-color); }
        .card-pending { border-left-color: var(--warning-color); }
        .card-progress { border-left-color: var(--info-color); }
        .card-completed { border-left-color: var(--success-color); }
        .card-overdue { border-left-color: var(--danger-color); }
        .card-review { border-left-color: var(--secondary-color); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #4361ee 100%, #3a0ca3 0%);
            border-radius: 15px;
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .recent-lesson-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .recent-lesson-card:hover {
            transform: translateX(5px);
            border-left-color: var(--success-color);
        }
        
        .badge-pending { background-color: var(--warning-color); color: #000; }
        .badge-in_progress { background-color: var(--info-color); }
        .badge-completed { background-color: var(--success-color); }
        .badge-review { background-color: var(--secondary-color); color: white; }
        
        .quick-action-btn {
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8f9fa;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .quick-action-btn:hover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.05);
            color: inherit;
            text-decoration: none;
        }
        
        .nav-link.active {
            font-weight: 600;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        
        .progress-tracker {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background-color: #e9ecef;
            margin: 10px 0;
        }
        
        .progress-bar-custom {
            height: 100%;
            transition: width 0.3s ease;
            background: linear-gradient(90deg, var(--success-color), #20c997);
        }
        
        .upcoming-lesson {
            border-left: 3px solid var(--info-color);
            padding-left: 15px;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .btn-new-lesson {
            background: linear-gradient(135deg, var(--success-color) 0%, #1e7e34 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .btn-new-lesson:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .empty-state {
            padding: 2rem 1rem;
            text-align: center;
            color: #6c757d;
        }
        
        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .template-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .template-card:hover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.05);
            transform: translateY(-2px);
        }
        
        .lesson-status {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }

        .lesson-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .lesson-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .clickable-item {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clickable-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        /* Footer Styles */
        .footer {
            background: #4361ee;
            color: white;
            padding: 1.5rem 0;
            margin-top: 3rem;
            border-top: 3px solid #4361ee;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .footer-left {
            font-size: 0.9rem;
        }

        .footer-right {
            font-size: 0.85rem;
            text-align: right;
        }

        .footer-divider {
            margin: 0 10px;
            color: #95a5a6;
        }

        .system-name {
            font-weight: bold;
            color: #ecf0f1;
        }

        .version {
            color: #bdc3c7;
            font-style: italic;
        }

        .developer {
            color: #ecf0f1;
        }

        .university {
            color: #ecf0f1;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .footer-right {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chalkboard-teacher me-2"></i>LEPOS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo !$is_list_view ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_list_view ? 'active' : ''; ?>" href="dashboard.php?action=list">
                            <i class="fas fa-list me-1"></i>All Lessons
                        </a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                    </span>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($delete_message)) echo $delete_message; ?>

        <?php if ($is_list_view): ?>
            <!-- ALL LESSONS LIST VIEW -->
            <div class="welcome-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-list me-2"></i>All Lesson Plans</h2>
                        <p class="mb-0">View and manage all your created lesson plans</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="dashboard.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <?php if (empty($all_lessons)): ?>
                <div class="empty-state py-5 text-center">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No Lesson Plans Yet</h3>
                    <p class="text-muted">Start by creating your first lesson plan</p>
                    <a href="lesson_plans.php?action=create" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Create Your First Lesson
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($all_lessons as $lesson): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card lesson-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title text-primary"><?php echo htmlspecialchars($lesson['title']); ?></h5>
                                        <span class="badge lesson-status badge-<?php echo $lesson['progress_status'] ?? 'completed'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $lesson['progress_status'] ?? 'completed')); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="lesson-meta text-muted small mb-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($lesson['subject']); ?>
                                            </div>
                                            <div class="col-6">
                                                <i class="fas fa-layer-group me-1"></i><?php echo htmlspecialchars($lesson['grade_level']); ?>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <i class="far fa-clock me-1"></i><?php echo $lesson['duration']; ?> mins
                                            </div>
                                            <div class="col-6 mt-2">
                                                <i class="far fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($lesson['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($lesson['objectives'])): ?>
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($lesson['objectives'], 0, 200) . (strlen($lesson['objectives']) > 200 ? '...' : ''))); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Created: <?php echo date('M j, Y g:i A', strtotime($lesson['created_at'])); ?>
                                        </small>
                                        <div class="btn-group btn-group-sm">
                                            <a href="lesson_view.php?action=view&id=<?php echo $lesson['id']; ?>" class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="lesson_edit.php?action=edit&id=<?php echo $lesson['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    title="Delete Lesson Plan"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?php echo $lesson['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Confirmation Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $lesson['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title text-danger">
                                                <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="text-center">
                                                <i class="fas fa-trash-alt fa-3x text-warning mb-3"></i>
                                                <h4>Delete Lesson Plan?</h4>
                                                <p>You are about to delete the lesson plan:</p>
                                                <p class="fw-bold text-primary">"<?php echo htmlspecialchars($lesson['title']); ?>"</p>
                                                <p class="text-danger">
                                                    <i class="fas fa-exclamation-circle me-1"></i>
                                                    This action cannot be undone and all associated data will be permanently lost.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </button>
                                            <form method="POST" action="dashboard.php" style="display: inline;">
                                                <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>">
                                                <button type="submit" name="delete_lesson" class="btn btn-danger">
                                                    <i class="fas fa-trash me-1"></i>Delete Lesson Plan
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- DASHBOARD VIEW -->
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2>Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h2>
                        <p class="mb-0">Ready to create amazing lesson plans for your students?</p>
                        <div class="mt-3">
                            <div class="progress-tracker" style="max-width: 400px;">
                                <?php 
                                $total = $stats['total_lessons'] ?? 0;
                                $completed = $stats['completed_lessons'] ?? 0;
                                $progress = $total > 0 ? ($completed / $total) * 100 : 0;
                                ?>
                                <div class="progress-bar-custom" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <small class="text-white-50">
                                <?php echo $completed; ?> of <?php echo $total; ?> lessons completed (<?php echo round($progress); ?>%)
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card card-total h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="stat-label">Total</p>
                                    <h2 class="stat-number text-primary"><?php echo $stats['total_lessons']; ?></h2>
                                </div>
                                <div class="icon-circle bg-primary">
                                    <i class="fas fa-book text-white"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">All lesson plans</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card card-pending h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="stat-label">Pending</p>
                                    <h2 class="stat-number text-warning"><?php echo $stats['pending_lessons']; ?></h2>
                                </div>
                                <div class="icon-circle bg-warning">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Need attention</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card card-progress h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="stat-label">In Progress</p>
                                    <h2 class="stat-number text-info"><?php echo $stats['in_progress_lessons']; ?></h2>
                                </div>
                                <div class="icon-circle bg-info">
                                    <i class="fas fa-sync-alt text-white"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Being worked on</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card card-completed h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="stat-label">Completed</p>
                                    <h2 class="stat-number text-success"><?php echo $stats['completed_lessons']; ?></h2>
                                </div>
                                <div class="icon-circle bg-success">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Ready to use</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card card-review h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="stat-label">In Review</p>
                                    <h2 class="stat-number" style="color: var(--secondary-color);"><?php echo $stats['review_lessons']; ?></h2>
                                </div>
                                <div class="icon-circle" style="background-color: var(--secondary-color);">
                                    <i class="fas fa-eye text-white"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Under review</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card card-overdue h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="stat-label">Overdue</p>
                                    <h2 class="stat-number text-danger"><?php echo $stats['overdue_lessons']; ?></h2>
                                </div>
                                <div class="icon-circle bg-danger">
                                    <i class="fas fa-exclamation-triangle text-white"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Past due date</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Quick Actions & Upcoming -->
                <div class="col-lg-4 mb-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="lesson_created.php?action=create" class="quick-action-btn">
                                        <i class="fas fa-plus-circle fa-2x text-primary mb-2"></i>
                                        <p class="mb-0 small fw-bold">New Lesson</p>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="dashboard.php?action=list" class="quick-action-btn">
                                        <i class="fas fa-list fa-2x text-success mb-2"></i>
                                        <p class="mb-0 small fw-bold">View All</p>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Lesson Plans -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Lesson Plans</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_lessons)): ?>
                                <div class="empty-state py-5 text-center">
                                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No lesson plans yet</h5>
                                    <p class="text-muted mb-4">Start creating your first lesson plan to organize your teaching materials</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recent_lessons as $lesson): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-start py-3 clickable-item" onclick="window.location.href='lesson_plans.php?action=view&id=<?php echo $lesson['id']; ?>'">
                                            <div class="flex-grow-1 me-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-0 fw-bold text-primary"><?php echo htmlspecialchars($lesson['title']); ?></h6>
                                                    <span class="badge lesson-status badge-<?php echo $lesson['progress_status'] ?? 'completed'; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $lesson['progress_status'] ?? 'completed')); ?>
                                                    </span>
                                                </div>
                                                <div class="lesson-meta text-muted small mb-2">
                                                    <span class="me-3">
                                                        <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($lesson['subject']); ?>
                                                    </span>
                                                    <span class="me-3">
                                                        <i class="fas fa-layer-group me-1"></i><?php echo htmlspecialchars($lesson['grade_level']); ?>
                                                    </span>
                                                    <span class="me-3">
                                                        <i class="far fa-clock me-1"></i><?php echo $lesson['duration']; ?> mins
                                                    </span>
                                                </div>
                                                <?php if (!empty($lesson['objectives'])): ?>
                                                    <p class="small text-muted mb-2 lesson-objective">
                                                        <?php echo nl2br(htmlspecialchars(substr($lesson['objectives'], 0, 150) . (strlen($lesson['objectives']) > 150 ? '...' : ''))); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="text-muted small">
                                                    <i class="far fa-calendar-alt me-1"></i>
                                                    Created: <?php echo date('M j, Y g:i A', strtotime($lesson['created_at'])); ?>
                                                </div>
                                            </div>
                                            <div class="btn-group-vertical btn-group-sm" onclick="event.stopPropagation()">
                                                <div class="btn-group btn-group-sm ms-3">
                                                    <a href="lesson_view.php?action=view&id=<?php echo $lesson['id']; ?>" class="btn btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="lesson_edit.php?action=edit&id=<?php echo $lesson['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            title="Delete Lesson Plan"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal<?php echo $lesson['id']; ?>"
                                                            onclick="event.stopPropagation()">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>  
                                            </div>
                                        </div>

                                        <!-- Delete Confirmation Modal for Recent Lessons -->
                                        <div class="modal fade" id="deleteModal<?php echo $lesson['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title text-danger">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="text-center">
                                                            <i class="fas fa-trash-alt fa-3x text-warning mb-3"></i>
                                                            <h4>Delete Lesson Plan?</h4>
                                                            <p>You are about to delete the lesson plan:</p>
                                                            <p class="fw-bold text-primary">"<?php echo htmlspecialchars($lesson['title']); ?>"</p>
                                                            <p class="text-danger">
                                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                                This action cannot be undone and all associated data will be permanently lost.
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i>Cancel
                                                        </button>
                                                        <form method="POST" action="dashboard.php" style="display: inline;">
                                                            <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>">
                                                            <button type="submit" name="delete_lesson" class="btn btn-danger">
                                                                <i class="fas fa-trash me-1"></i>Delete Lesson Plan
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <span class="system-name">Lesson Plan Organizer System</span>
                    <span class="footer-divider">|</span>
                    <span class="version">Version No 1.0</span>
                </div>
                <div class="footer-right">
                    <span>Developed and Maintained By</span>
                    <span class="footer-divider">|</span>
                    <span class="university">LEPOS</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animate progress bars on page load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar-custom');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                    bar.style.transition = 'width 1s ease-in-out';
                }, 300);
            });
        });

        // Make recent lesson cards clickable
        document.querySelectorAll('.recent-lesson-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on buttons
                if (!e.target.closest('.btn')) {
                    const viewLink = this.querySelector('a[href*="action=view"]');
                    if (viewLink) {
                        window.location.href = viewLink.href;
                    }
                }
            });
        });

        // Prevent clickable items from triggering when clicking buttons
        document.querySelectorAll('.clickable-item .btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>