<?php
require_once '../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$message = '';

// Check action
$action = $_GET['action'] ?? 'list';
$lesson_id = $_GET['id'] ?? null;
$preview_data = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_lesson'])) {
        // Get form data
        $title = trim($_POST['title'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $grade_level = trim($_POST['grade_level'] ?? '');
        $duration = intval($_POST['duration'] ?? 0);
        $objectives = trim($_POST['objectives'] ?? '');
        $materials = trim($_POST['materials'] ?? '');
        $activities = trim($_POST['activities'] ?? '');
        $assessment = trim($_POST['assessment'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (!empty($title) && !empty($subject) && !empty($grade_level) && $duration > 0) {
            // Use current timestamp for completion
            $completion_date = date('Y-m-d H:i:s');
            
            // Insert into database with completed status
            [$stmt, $result, $conn] = q(
                "INSERT INTO lesson_plans (
                    user_id, title, subject, grade_level, duration, 
                    objectives, materials, activities, assessment, notes, 
                    status, progress_status, completion_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', 'completed', ?)",
                'isssissssss',
                [
                    $user['id'], $title, $subject, $grade_level, $duration,
                    $objectives, $materials, $activities, $assessment, $notes,
                    $completion_date
                ]
            );
 if ($stmt && $stmt->affected_rows > 0) {
        // ✅ Redirect to dashboard after saving
        header("Location: dashboard.php?success=lesson_saved");
        exit();
    } else {
        echo "<div class='alert alert-danger text-center mt-4'>
                Failed to save the lesson plan. Please try again.
              </div>";
    }

    if ($stmt) $stmt->close();
    if ($conn) $conn->close();
}
    }
    
    // Handle preview request
    if (isset($_POST['preview_lesson'])) {
        $preview_data = [
            'title' => trim($_POST['title'] ?? ''),
            'subject' => trim($_POST['subject'] ?? ''),
            'grade_level' => trim($_POST['grade_level'] ?? ''),
            'duration' => intval($_POST['duration'] ?? 0),
            'objectives' => trim($_POST['objectives'] ?? ''),
            'materials' => trim($_POST['materials'] ?? ''),
            'activities' => trim($_POST['activities'] ?? ''),
            'assessment' => trim($_POST['assessment'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];
        $action = 'preview';
    }
}

// Fetch specific lesson plan for view
if ($lesson_id && $action === 'view') {
    [$stmt, $result, $conn] = q(
        "SELECT * FROM lesson_plans WHERE id = ? AND user_id = ?",
        'ii',
        [$lesson_id, $user['id']]
    );
    $lesson = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if (!$lesson) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>Lesson plan not found.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
        $action = 'list';
    }
}

// Fetch lesson plans for list view
if ($action === 'list') {
    [$stmt, $result, $conn] = q(
        "SELECT * FROM lesson_plans WHERE user_id = ? ORDER BY created_at DESC",
        'i',
        [$user['id']]
    );
    $lesson_plans = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>
        <?php 
        if ($action === 'create') echo 'Create Lesson Plan';
        elseif ($action === 'preview') echo 'Preview Lesson Plan';
        elseif ($action === 'view') echo 'View Lesson Plan';
        else echo 'Lesson Plans';
        ?> - LEPOS
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add jsPDF library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .lesson-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #4361ee;
        }
        
        .section-header {
            color: #4361ee;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        
        .creation-header {
            background: linear-gradient(135deg, #4361ee 100%, #3a0ca3 0%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .btn-create {
            background: linear-gradient(135deg, #4361ee 100%, #3a0ca3 0%);
            border: none;
            color: white; 
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
        }
        
        /* Standard Lesson Plan Format Styles */
        .standard-lesson-plan {
            background: white;
            border: 2px solid #333;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.6;
        }
        
        .lesson-plan-header {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .lesson-plan-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .lesson-plan-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #4361ee;
        }
        
        .meta-item {
            margin-bottom: 0.5rem;
        }
        
        .meta-label {
            font-weight: bold;
            color: #2c3e50;
            display: inline-block;
            width: 120px;
        }
        
        .lesson-section-standard {
            margin-bottom: 2rem;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #4361ee;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .section-content {
            padding: 1rem;
            background: #fafafa;
            border-radius: 5px;
            border-left: 3px solid #e9ecef;
        }
        
        .time-allocation {
            background: #e8f4fd;
            border: 1px solid #b6d7e8;
            border-radius: 5px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .time-slot {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #ccc;
        }
        
        .time-slot:last-child {
            border-bottom: none;
        }
        
        /* Print Styles for A4 */
        @media print {
            body * {
                visibility: hidden;
            }
            .standard-lesson-plan, .standard-lesson-plan * {
                visibility: visible;
            }
            .standard-lesson-plan {
                position: absolute;
                left: 0;
                top: 0;
                width: 210mm;
                height: 297mm;
                padding: 20mm;
                margin: 0;
                border: none;
                box-shadow: none;
                background: white;
                font-size: 12pt;
                line-height: 1.4;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
            .lesson-section-standard {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
        
        /* View Lesson Plan Header */
        .view-header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chalkboard-teacher me-2"></i>LEPOS
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                </span>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php echo $message; ?>

        <?php if ($action === 'create'): ?>
            <!-- CREATE LESSON PLAN FORM -->
            <div class="creation-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h2><i class="fas fa-plus-circle me-2"></i>Create New Lesson Plan</h2>
                        <p class="mb-0">Fill in the details and preview before saving</p>
                    </div>
                    <div class="col-auto">
                        <a href="dashboard.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <form method="POST" id="lessonForm">
                <!-- Basic Information -->
                <div class="lesson-section">
                    <h5 class="section-header">Basic Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Lesson Title</label>
                                <input type="text" class="form-control" name="title" required 
                                       placeholder="Enter a descriptive lesson title">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Subject</label>
                                <select class="form-select" name="subject" required>
                                    <option value="">Select Subject</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="English">English</option>
                                    <option value="Science">Physics</option>
                                    <option value="Social Studies">Social Studies</option>
                                    <option value="Kiswahili">Kiswahili</option>
                                    <option value="CRE">Biology</option>
                                    <option value="History">History</option>
                                    <option value="Geography">Geography</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Class Level</label>
                                <select class="form-select" name="grade_level" required>
                                    <option value="">Select class</option>
                                    <option value="Form 1">Form 1</option>
                                    <option value="Form 2">Form 2</option>
                                    <option value="Form 3">Form 3</option>
                                    <option value="Form 4">Form 4</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Duration (minutes)</label>
                                <input type="number" class="form-control" name="duration" required 
                                       min="1" max="480" value="40">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lesson Content -->
                <div class="lesson-section">
                    <h5 class="section-header">Lesson Content</h5>
                    <div class="mb-3">
                        <label class="form-label">Learning Objectives</label>
                        <textarea class="form-control" name="objectives" rows="3" 
                                  placeholder="What will students learn? List specific, measurable objectives..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Materials & Resources</label>
                        <textarea class="form-control" name="materials" rows="2" 
                                  placeholder="List all required materials, textbooks, equipment..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Activities & Procedure</label>
                        <textarea class="form-control" name="activities" rows="4" 
                                  placeholder="Describe step-by-step activities, timing, and instructions..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assessment Methods</label>
                        <textarea class="form-control" name="assessment" rows="2" 
                                  placeholder="How will you assess student learning?"></textarea>
                    </div>
                </div>

                <!-- Additional Notes -->
                <div class="lesson-section">
                    <h5 class="section-header">Additional Notes</h5>
                    <div class="mb-3">
                        <label class="form-label">Teacher's Notes</label>
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Any additional notes, differentiation strategies, or reflections..."></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <a href="dashboard.php" class="btn btn-secondary btn-lg w-100">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="preview_lesson" class="btn btn-info btn-lg w-100">
                            <i class="fas fa-eye me-2"></i>Preview Lesson
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="create_lesson" class="btn btn-create btn-lg w-100">
                            <i class="fas fa-save me-2"></i>Save
                        </button>
                    </div>
                </div>
            </form>

        <?php elseif ($action === 'preview'): ?>
            <!-- PREVIEW LESSON PLAN -->
            <div class="preview-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h2><i class="fas fa-eye me-2"></i>Lesson Plan Preview</h2>
                        <p class="mb-0">Review your lesson plan before saving</p>
                    </div>
                    <div class="col-auto">
                        <button onclick="window.print()" class="btn btn-light me-2">
                            <i class="fas fa-print me-2"></i>Download PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Standard Lesson Plan Format -->
            <div class="standard-lesson-plan" id="lessonPlanContent">
                <!-- Header -->
                <div class="lesson-plan-header">
                     <div style="font-size: 1.1rem; color: #666;">lesson title</title></div>
                    <div class="lesson-plan-title"><?php echo htmlspecialchars($preview_data['title']); ?></div>
                </div>

                <!-- Metadata -->
                <div class="lesson-plan-meta">
                    <div class="meta-item">
                        <span class="meta-label">Subject:</span>
                        <?php echo htmlspecialchars($preview_data['subject']); ?>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Class Level:</span>
                        <?php echo htmlspecialchars($preview_data['grade_level']); ?>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Duration:</span>
                        <?php echo $preview_data['duration']; ?> minutes
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Date Prepared:</span>
                        <?php echo date('F j, Y'); ?>
                    </div>
                </div>

                <!-- Learning Objectives -->
                <div class="lesson-section-standard">
                    <div class="section-title">1. Learning Objectives</div>
                    <div class="section-content">
                        <?php if (!empty($preview_data['objectives'])): ?>
                            <?php echo nl2br(htmlspecialchars($preview_data['objectives'])); ?>
                        <?php else: ?>
                            <em>No learning objectives specified.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Materials & Resources -->
                <div class="lesson-section-standard">
                    <div class="section-title">2. Materials & Resources</div>
                    <div class="section-content">
                        <?php if (!empty($preview_data['materials'])): ?>
                            <?php echo nl2br(htmlspecialchars($preview_data['materials'])); ?>
                        <?php else: ?>
                            <em>No materials specified.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lesson Procedure -->
                <div class="lesson-section-standard">
                    <div class="section-title">3. Lesson Procedure</div>
                    
                    <!-- Time Allocation -->
                    <div class="time-allocation">
                        <strong>Time Allocation:</strong>
                        <div class="time-slot">
                            <span>Introduction:</span>
                            <span>5-10 minutes</span>
                        </div>
                        <div class="time-slot">
                            <span>Main Activity:</span>
                            <span>20-30 minutes</span>
                        </div>
                        <div class="time-slot">
                            <span>Conclusion:</span>
                            <span>5-10 minutes</span>
                        </div>
                    </div>

                    <div class="section-content">
                        <?php if (!empty($preview_data['activities'])): ?>
                            <?php echo nl2br(htmlspecialchars($preview_data['activities'])); ?>
                        <?php else: ?>
                            <em>No activities specified.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Assessment -->
                <div class="lesson-section-standard">
                    <div class="section-title">4. Assessment Methods</div>
                    <div class="section-content">
                        <?php if (!empty($preview_data['assessment'])): ?>
                            <?php echo nl2br(htmlspecialchars($preview_data['assessment'])); ?>
                        <?php else: ?>
                            <em>No assessment methods specified.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Teacher's Notes -->
                <div class="lesson-section-standard">
                    <div class="section-title">5. Teacher's Notes</div>
                    <div class="section-content">
                        <?php if (!empty($preview_data['notes'])): ?>
                            <?php echo nl2br(htmlspecialchars($preview_data['notes'])); ?>
                        <?php else: ?>
                            <em>No additional notes.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div style="margin-top: 3rem; padding-top: 1rem; border-top: 2px solid #333; text-align: center; color: #666;">
                    <div>Prepared by: <?php echo htmlspecialchars($user['name']); ?></div>
                    <div>LEPOS Lesson Plan Organizer System</div>
                </div>
            </div>

            <!-- Preview Actions -->
            <div class="row mt-4 no-print">
               <!-- <div class="col-md-6">
                    <a href="lesson_created.php?action=create" class="btn btn-secondary btn-lg w-100">
                        <i class="fas fa-edit me-2"></i>Edit Lesson Plan
                    </a>
                </div> -->

                <div class="col-md-6">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($preview_data['title']); ?>">
                        <input type="hidden" name="subject" value="<?php echo htmlspecialchars($preview_data['subject']); ?>">
                        <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($preview_data['grade_level']); ?>">
                        <input type="hidden" name="duration" value="<?php echo $preview_data['duration']; ?>">
                        <input type="hidden" name="objectives" value="<?php echo htmlspecialchars($preview_data['objectives']); ?>">
                        <input type="hidden" name="materials" value="<?php echo htmlspecialchars($preview_data['materials']); ?>">
                        <input type="hidden" name="activities" value="<?php echo htmlspecialchars($preview_data['activities']); ?>">
                        <input type="hidden" name="assessment" value="<?php echo htmlspecialchars($preview_data['assessment']); ?>">
                        <input type="hidden" name="notes" value="<?php echo htmlspecialchars($preview_data['notes']); ?>">
                        <button type="submit" name="create_lesson" class="btn btn-create btn-lg w-100">
                            <i class="fas fa-check-circle me-2"></i>Save
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($action === 'view' && isset($lesson)): ?>
            <!-- VIEW LESSON PLAN 
            <div class="view-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h2><i class="fas fa-eye me-2"></i>View Lesson Plan</h2>
                        <p class="mb-0"><?php echo htmlspecialchars($lesson['title']); ?></p>
                    </div>
                    <div class="col-auto action-buttons">
                        <button onclick="window.print()" class="btn btn-light me-2">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                        <a href="lesson_plans.php?action=edit&id=<?php echo $lesson['id']; ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-2"></i>Edit
                        </a>
                        <a href="lesson_plans.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                    </div>
                </div>
            </div>-->

            <!-- Standard Lesson Plan Format -->
            <div class="standard-lesson-plan" id="lessonPlanContent">
                <!-- Header -->
                <div class="lesson-plan-header">
                     <div style="font-size: 1.1rem; color: #666;">lesson title</title></div>
                    <div class="lesson-plan-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                </div>

                <!-- Metadata -->
                <div class="lesson-plan-meta">
                    <div class="meta-item">
                        <span class="meta-label">Subject:</span>
                        <?php echo htmlspecialchars($lesson['subject']); ?>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Class Level:</span>
                        <?php echo htmlspecialchars($lesson['grade_level']); ?>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Duration:</span>
                        <?php echo $lesson['duration']; ?> minutes
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Date Prepared:</span>
                        <?php echo date('F j, Y', strtotime($lesson['created_at'])); ?>
                    </div>
                    <?php if ($lesson['updated_at'] && $lesson['updated_at'] !== $lesson['created_at']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Last Updated:</span>
                        <?php echo date('F j, Y', strtotime($lesson['updated_at'])); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Learning Objectives -->
                <div class="lesson-section-standard">
                    <div class="section-title">1. Learning Objectives</div>
                    <div class="section-content">
                        <?php if (!empty($lesson['objectives'])): ?>
                            <?php echo nl2br(htmlspecialchars($lesson['objectives'])); ?>
                        <?php else: ?>
                            <em>No learning objectives specified.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Materials & Resources -->
                <div class="lesson-section-standard">
                    <div class="section-title">2. Materials & Resources</div>
                    <div class="section-content">
                        <?php if (!empty($lesson['materials'])): ?>
                            <?php echo nl2br(htmlspecialchars($lesson['materials'])); ?>
                        <?php else: ?>
                            <em>No materials specified.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lesson Procedure -->
                <div class="lesson-section-standard">
                    <div class="section-title">3. Lesson Procedure</div>
                    
                    <!-- Time Allocation -->
                    <div class="time-allocation">
                        <strong>Time Allocation:</strong>
                        <div class="time-slot">
                            <span>Introduction:</span>
                            <span>5-10 minutes</span>
                        </div>
                        <div class="time-slot">
                            <span>Main Activity:</span>
                            <span>20-30 minutes</span>
                        </div>
                        <div class="time-slot">
                            <span>Conclusion:</span>
                            <span>5-10 minutes</span>
                        </div>
                    </div>

                    <div class="section-content">
                        <?php if (!empty($lesson['activities'])): ?>
                            <?php echo nl2br(htmlspecialchars($lesson['activities'])); ?>
                        <?php else: ?>
                            <em>No activities specified.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Assessment -->
                <div class="lesson-section-standard">
                    <div class="section-title">4. Assessment Methods</div>
                    <div class="section-content">
                        <?php if (!empty($lesson['assessment'])): ?>
                            <?php echo nl2br(htmlspecialchars($lesson['assessment'])); ?>
                        <?php else: ?>
                            <em>No assessment methods specified.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Teacher's Notes -->
                <div class="lesson-section-standard">
                    <div class="section-title">5. Teacher's Notes</div>
                    <div class="section-content">
                        <?php if (!empty($lesson['notes'])): ?>
                            <?php echo nl2br(htmlspecialchars($lesson['notes'])); ?>
                        <?php else: ?>
                            <em>No additional notes.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div style="margin-top: 3rem; padding-top: 1rem; border-top: 2px solid #333; text-align: center; color: #666;">
                    <div>Prepared by: <?php echo htmlspecialchars($user['name']); ?></div>
                    <div>LEPOS Lesson Plan Organizer System</div>
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
        // PDF Generation Function
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            const lessonPlanElement = document.getElementById('lessonPlanContent');
            
            // Show loading
            const originalContent = lessonPlanElement.innerHTML;
            lessonPlanElement.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Generating PDF...</div>';
            
            html2canvas(lessonPlanElement, {
                scale: 2,
                useCORS: true,
                logging: false,
                width: lessonPlanElement.scrollWidth,
                height: lessonPlanElement.scrollHeight
            }).then(canvas => {
                // Restore original content
                lessonPlanElement.innerHTML = originalContent;
                
                const imgData = canvas.toDataURL('image/png');
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 295; // A4 height in mm
                const imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;

                doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    doc.addPage();
                    doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                // Get lesson title for filename
                const lessonTitle = "<?php echo isset($preview_data['title']) ? addslashes($preview_data['title']) : (isset($lesson['title']) ? addslashes($lesson['title']) : 'lesson_plan'); ?>";
                const fileName = lessonTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.pdf';
                
                doc.save(fileName);
            }).catch(error => {
                console.error('Error generating PDF:', error);
                lessonPlanElement.innerHTML = originalContent;
                alert('Error generating PDF. Please try again.');
            });
        }

        // Form validation for create form
        document.getElementById('lessonForm')?.addEventListener('submit', function(e) {
            if (e.submitter && e.submitter.name === 'create_lesson') {
                const requiredFields = this.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields before saving.');
                } else {
                    if (!confirm('Are you sure you want to save and mark this lesson plan as completed?')) {
                        e.preventDefault();
                    }
                }
            }
        });

        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    </script>
</body>
</html>