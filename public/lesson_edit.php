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
        $_SESSION['flash_message'] = '<div class="alert alert-danger">Lesson plan not found or you don\'t have permission to edit it.</div>';
        header("Location: dashboard.php");
        exit();
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Edit lesson error: " . $e->getMessage());
    $_SESSION['flash_message'] = '<div class="alert alert-danger">Error loading lesson plan.</div>';
    header("Location: dashboard.php");
    exit();
}

// Helper function to safely get lesson data
function getLessonData($lesson, $key, $default = '') {
    return isset($lesson[$key]) && !empty($lesson[$key]) ? $lesson[$key] : $default;
}

// Get safe lesson data - UPDATED to match the new structure
$title = getLessonData($lesson, 'title');
$subject = getLessonData($lesson, 'subject');
$grade_level = getLessonData($lesson, 'grade_level');
$duration = getLessonData($lesson, 'duration');
$objectives = getLessonData($lesson, 'objectives');
$materials = getLessonData($lesson, 'materials');
$activities = getLessonData($lesson, 'activities'); // CHANGED from 'procedure'
$assessment = getLessonData($lesson, 'assessment');
$notes = getLessonData($lesson, 'notes');
$progress_status = getLessonData($lesson, 'progress_status', 'pending');
$status = getLessonData($lesson, 'status', 'published');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $grade_level = $_POST['grade_level'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $objectives = $_POST['objectives'] ?? '';
    $materials = $_POST['materials'] ?? '';
    $activities = $_POST['activities'] ?? ''; // CHANGED from 'procedure'
    $assessment = $_POST['assessment'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $progress_status = $_POST['progress_status'] ?? 'pending';

    // Validate required fields
    if (empty($title) || empty($subject) || empty($grade_level) || empty($duration)) {
        $_SESSION['flash_message'] = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    } else {
        try {
            [$stmt, $result, $conn] = q(
                "UPDATE lesson_plans SET 
                 title = ?, subject = ?, grade_level = ?, duration = ?, 
                 progress_status = ?, objectives = ?, materials = ?, 
                 activities = ?, assessment = ?, notes = ?, updated_at = NOW()
                 WHERE id = ? AND user_id = ?",
                'sssissssssii',
                [
                    $title, $subject, $grade_level, $duration,
                    $progress_status, $objectives, $materials,
                    $activities, $assessment, $notes,
                    $lesson_id, $user['id']
                ]
            );

            if ($stmt && $stmt->affected_rows > 0) {
                $_SESSION['flash_message'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>Lesson plan updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                
                // Update the lesson data with new values
                $lesson['title'] = $title;
                $lesson['subject'] = $subject;
                $lesson['grade_level'] = $grade_level;
                $lesson['duration'] = $duration;
                $lesson['progress_status'] = $progress_status;
                $lesson['objectives'] = $objectives;
                $lesson['materials'] = $materials;
                $lesson['activities'] = $activities;
                $lesson['assessment'] = $assessment;
                $lesson['notes'] = $notes;
            } else {
                $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>Failed to update lesson plan. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }

            if ($stmt) $stmt->close();
            if ($conn) $conn->close();
        } catch (Exception $e) {
            error_log("Update lesson error: " . $e->getMessage());
            $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Error updating lesson plan. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lesson Plan - <?php echo htmlspecialchars($title); ?></title>
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
        
        .edit-container {
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
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
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
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
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

    <div class="container my-4">
        <!-- Flash Message -->
        <?php if (!empty($_SESSION['flash_message'])): ?>
            <?php echo $_SESSION['flash_message']; ?>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <!-- Edit Lesson Plan Section -->
        <div class="edit-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title">Edit Lesson Plan</h2>
            </div>
            
            <form method="POST" id="lessonForm">
                <!-- Basic Information -->
                <div class="lesson-section">
                    <h5 class="section-header">Basic Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label required-field">Lesson Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($title); ?>" required
                                       placeholder="Enter a descriptive lesson title">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="subject" class="form-label required-field">Subject</label>
                                <select class="form-select" id="subject" name="subject" required>
                                    <option value="">Select Subject</option>
                                    <option value="Mathematics" <?php echo $subject == 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                                    <option value="English" <?php echo $subject == 'English' ? 'selected' : ''; ?>>English</option>
                                    <option value="Science" <?php echo $subject == 'Science' ? 'selected' : ''; ?>>Science</option>
                                    <option value="Social Studies" <?php echo $subject == 'Social Studies' ? 'selected' : ''; ?>>Social Studies</option>
                                    <option value="Kiswahili" <?php echo $subject == 'Kiswahili' ? 'selected' : ''; ?>>Kiswahili</option>
                                    <option value="CRE" <?php echo $subject == 'CRE' ? 'selected' : ''; ?>>CRE</option>
                                    <option value="History" <?php echo $subject == 'History' ? 'selected' : ''; ?>>History</option>
                                    <option value="Geography" <?php echo $subject == 'Geography' ? 'selected' : ''; ?>>Geography</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="grade_level" class="form-label required-field">Class Level</label>
                                <select class="form-select" id="grade_level" name="grade_level" required>
                                    <option value="">Select class</option>
                                    <option value="Form 1" <?php echo $grade_level == 'Form 1' ? 'selected' : ''; ?>>Form 1</option>
                                    <option value="Form 2" <?php echo $grade_level == 'Form 2' ? 'selected' : ''; ?>>Form 2</option>
                                    <option value="Form 3" <?php echo $grade_level == 'Form 3' ? 'selected' : ''; ?>>Form 3</option>
                                    <option value="Form 4" <?php echo $grade_level == 'Form 4' ? 'selected' : ''; ?>>Form 4</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="duration" class="form-label required-field">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration" name="duration" 
                                       value="<?php echo htmlspecialchars($duration); ?>" required min="1" max="480">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="progress_status" class="form-label">Progress Status</label>
                        <select class="form-select" id="progress_status" name="progress_status">
                            <option value="pending" <?php echo $progress_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $progress_status == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $progress_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="review" <?php echo $progress_status == 'review' ? 'selected' : ''; ?>>Review</option>
                        </select>
                    </div>
                </div>
                
                <!-- Lesson Content -->
                <div class="lesson-section">
                    <h5 class="section-header">Lesson Content</h5>
                    <div class="mb-3">
                        <label for="objectives" class="form-label">Learning Objectives</label>
                        <textarea class="form-control" id="objectives" name="objectives" rows="3" 
                                  placeholder="What will students learn? List specific, measurable objectives..."><?php echo htmlspecialchars($objectives); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="materials" class="form-label">Materials & Resources</label>
                        <textarea class="form-control" id="materials" name="materials" rows="2" 
                                  placeholder="List all required materials, textbooks, equipment..."><?php echo htmlspecialchars($materials); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activities" class="form-label">Activities & Procedure</label>
                        <textarea class="form-control" id="activities" name="activities" rows="4" 
                                  placeholder="Describe step-by-step activities, timing, and instructions..."><?php echo htmlspecialchars($activities); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assessment" class="form-label">Assessment Methods</label>
                        <textarea class="form-control" id="assessment" name="assessment" rows="2" 
                                  placeholder="How will you assess student learning?"><?php echo htmlspecialchars($assessment); ?></textarea>
                    </div>
                </div>
                
                <!-- Additional Notes -->
                <div class="lesson-section">
                    <h5 class="section-header">Additional Notes</h5>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Teacher's Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Any additional notes, differentiation strategies, or reflections..."><?php echo htmlspecialchars($notes); ?></textarea>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                    
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </form>
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
        // Form validation
        document.getElementById('lessonForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const subject = document.getElementById('subject').value.trim();
            const gradeLevel = document.getElementById('grade_level').value.trim();
            const duration = document.getElementById('duration').value.trim();
            
            if (!title || !subject || !gradeLevel || !duration) {
                e.preventDefault();
                alert('Please fill in all required fields (marked with *).');
                return false;
            }
            
            if (duration <= 0) {
                e.preventDefault();
                alert('Duration must be greater than 0 minutes.');
                return false;
            }
        });

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            // Trigger initial resize
            textarea.dispatchEvent(new Event('input'));
        });
    </script>
</body>
</html>