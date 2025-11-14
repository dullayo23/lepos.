<?php
require_once '../includes/auth.php';
requireLogin();

$user = getCurrentUser();

// Get lesson ID from URL
$lesson_id = $_GET['id'] ?? null;

if (!$lesson_id) {
    $_SESSION['flash_message'] = '<div class="alert alert-danger">Lesson plan not found.</div>';
    header("Location: dashboard.php");
    exit();
}

// Fetch lesson plan from database
$lesson = null;
try {
    [$stmt, $result, $conn] = q(
        "SELECT * FROM lesson_plans WHERE id = ? AND user_id = ?",
        'ii',
        [$lesson_id, $user['id']]
    );
    
    if ($result && $result->num_rows > 0) {
        $lesson = $result->fetch_assoc();
    } else {
        $_SESSION['flash_message'] = '<div class="alert alert-danger">Lesson plan not found or you don\'t have permission to view it.</div>';
        header("Location: dashboard.php");
        exit();
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("View lesson error: " . $e->getMessage());
    $_SESSION['flash_message'] = '<div class="alert alert-danger">Error loading lesson plan.</div>';
    header("Location: dashboard.php");
    exit();
}

// Helper function to safely get lesson data
function getLessonData($lesson, $key, $default = '') {
    return isset($lesson[$key]) && !empty($lesson[$key]) ? $lesson[$key] : $default;
}

// Helper function to format datetime
function formatDateTime($datetimeString) {
    if (empty($datetimeString) || $datetimeString == '0000-00-00 00:00:00') {
        return 'Not available';
    }
    return date('F j, Y g:i A', strtotime($datetimeString));
}

// Get safe lesson data - UPDATED to match new structure
$title = getLessonData($lesson, 'title', 'Untitled Lesson');
$subject = getLessonData($lesson, 'subject', 'Not specified');
$grade_level = getLessonData($lesson, 'grade_level', 'Not specified');
$duration = getLessonData($lesson, 'duration', 'Not specified');
$progress_status = getLessonData($lesson, 'progress_status', 'pending');
$objectives = getLessonData($lesson, 'objectives');
$materials = getLessonData($lesson, 'materials');
$activities = getLessonData($lesson, 'activities'); // CHANGED from 'procedure'
$assessment = getLessonData($lesson, 'assessment');
$notes = getLessonData($lesson, 'notes');
$status = getLessonData($lesson, 'status', 'published');
$created_at = formatDateTime(getLessonData($lesson, 'created_at'));
$updated_at = formatDateTime(getLessonData($lesson, 'updated_at'));
$completion_date = formatDateTime(getLessonData($lesson, 'completion_date'));

// Process objectives and materials into arrays
$objectives_array = !empty($objectives) ? explode("\n", $objectives) : [];
$materials_array = !empty($materials) ? explode("\n", $materials) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Lesson Plan - <?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .view-container {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
        }
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .lesson-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .objectives-list, .materials-list {
            list-style-type: none;
            padding-left: 0;
        }
        
        .objectives-list li, .materials-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .objectives-list li:before, .materials-list li:before {
            content: "•";
            color: var(--primary-color);
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }
        
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-in_progress { background-color: #17a2b8; }
        .badge-completed { background-color: #28a745; }
        .badge-review { background-color: var(--secondary-color); color: white; }
        .badge-published { background-color: #28a745; }
        .badge-draft { background-color: #6c757d; }
        
        .empty-state {
            color: #6c757d;
            font-style: italic;
            padding: 10px 0;
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
        
        .section-title-standard {
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
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chalkboard-teacher me-2"></i>LEPOS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Navigation items can be added here -->
                </ul>
                <div class="navbar-nav">
                    <span class="navbar-text me-3"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?></span>
                    <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Flash Message -->
        <?php if (!empty($_SESSION['flash_message'])): ?>
            <?php echo $_SESSION['flash_message']; ?>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <!-- View Lesson Plan Section -->
        <div class="view-container no-print">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title">View Lesson Plan</h2>
                <!--<div class="action-buttons">
                    <button onclick="window.print()" class="btn btn-light me-2">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                    <a href="edit_lesson.php?id=<?php echo $lesson['id']; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div> -->
            </div>
        </div>

        <!-- Standard Lesson Plan Format -->
        <div class="standard-lesson-plan" id="lessonPlanContent">
            <!-- Header -->
            <div class="lesson-plan-header">
                <div style="font-size: 1.1rem; color: #666;">Lesson Title</div>
                <div class="lesson-plan-title"><?php echo htmlspecialchars($title); ?></div>
            </div>

            <!-- Metadata -->
            <div class="lesson-plan-meta">
                <div class="meta-item">
                    <span class="meta-label">Subject:</span>
                    <?php echo htmlspecialchars($subject); ?>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Class Level:</span>
                    <?php echo htmlspecialchars($grade_level); ?>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Duration:</span>
                    <?php echo $duration; ?> minutes
                </div>
                <div class="meta-item">
                    <span class="meta-label">Status:</span>
                    <span class="badge badge-<?php echo $progress_status; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $progress_status)); ?>
                    </span>
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
                <div class="section-title-standard">1.Learning Objectives</div>
                <div class="section-content">
                    <?php if (!empty($objectives)): ?>
                        <?php echo nl2br(htmlspecialchars($objectives)); ?>
                    <?php else: ?>
                        <em>No learning objectives specified.</em>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Materials & Resources -->
            <div class="lesson-section-standard">
                <div class="section-title-standard">2. Materials & Resources</div>
                <div class="section-content">
                    <?php if (!empty($materials)): ?>
                        <?php echo nl2br(htmlspecialchars($materials)); ?>
                    <?php else: ?>
                        <em>No materials specified.</em>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lesson Procedure -->
            <div class="lesson-section-standard">
                <div class="section-title-standard">3. Lesson Procedure</div>
                
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
                    <?php if (!empty($activities)): ?>
                        <?php echo nl2br(htmlspecialchars($activities)); ?>
                    <?php else: ?>
                        <em>No activities specified.</em>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Assessment -->
            <div class="lesson-section-standard">
                <div class="section-title-standard">4. Assessment Methods</div>
                <div class="section-content">
                    <?php if (!empty($assessment)): ?>
                        <?php echo nl2br(htmlspecialchars($assessment)); ?>
                    <?php else: ?>
                        <em>No assessment methods specified.</em>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Teacher's Notes -->
            <div class="lesson-section-standard">
                <div class="section-title-standard">5. Teacher's Notes</div>
                <div class="section-content">
                    <?php if (!empty($notes)): ?>
                        <?php echo nl2br(htmlspecialchars($notes)); ?>
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

        <!-- Additional Action Buttons -->
        <div class="view-container no-print mt-4">
            <div class="action-buttons">
                <a href="lesson_edit.php?id=<?php echo $lesson['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit This Lesson Plan
                </a>
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Download
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div> 
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
                const lessonTitle = "<?php echo addslashes($title); ?>";
                const fileName = lessonTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.pdf';
                
                doc.save(fileName);
            }).catch(error => {
                console.error('Error generating PDF:', error);
                lessonPlanElement.innerHTML = originalContent;
                alert('Error generating PDF. Please try again.');
            });
        }

        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>