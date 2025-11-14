<?php
require_once '../includes/auth.php';
requireLogin();

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Choose a Template - LEPOS</title>
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
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .template-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 2rem auto;
            max-width: 1200px;
        }
        
        .template-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .template-body {
            padding: 3rem 2rem;
        }
        
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .template-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .template-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .template-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: var(--primary-color);
        }
        
        .template-card:hover::before {
            transform: scaleX(1);
        }
        
        .template-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }
        
        .template-standard .template-icon { background: rgba(67, 97, 238, 0.1); color: var(--primary-color); }
        .template-science .template-icon { background: rgba(40, 167, 69, 0.1); color: var(--success-color); }
        .template-art .template-icon { background: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
        .template-pe .template-icon { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        
        .template-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .template-description {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .btn-template {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-template:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            color: white;
        }
        
        .quick-actions {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .action-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .action-card:hover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.05);
            text-decoration: none;
            color: inherit;
            transform: translateY(-5px);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .back-btn {
            position: absolute;
            top: 2rem;
            left: 2rem;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }
        
        @media (max-width: 768px) {
            .template-body {
                padding: 2rem 1rem;
            }
            
            .template-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .back-btn {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Template Selection Page -->
    <div class="template-container">
        <!-- Header -->
        <div class="template-header position-relative">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="display-4 fw-bold mb-3">Choose a Template</h1>
            <p class="lead mb-0">Select a pre-designed template to start creating your lesson plan</p>
        </div>
        
        <!-- Body -->
        <div class="template-body">
            <!-- Stats Overview -->
            <div class="row mb-5">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number" id="totalLessons">0</div>
                        <div class="stats-label">All lesson plans</div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="quick-actions h-100">
                        <h5 class="fw-bold mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="lesson_created.php?action=create" class="action-card">
                                    <i class="fas fa-plus-circle fa-2x text-primary mb-2"></i>
                                    <h6 class="fw-bold mb-1">New Lesson</h6>
                                    <p class="small text-muted mb-0">Start from scratch</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="dashboard.php" class="action-card">
                                    <i class="fas fa-home fa-2x text-info mb-2"></i>
                                    <h6 class="fw-bold mb-1">Back to Dashboard</h6>
                                    <p class="small text-muted mb-0">Return to overview</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Template Grid -->
            <h3 class="fw-bold mb-4">Available Templates</h3>
            <div class="template-grid">
                <!-- Standard Lesson Template -->
                <div class="template-card template-standard" onclick="selectTemplate('standard')">
                    <div class="template-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <h4 class="template-title">Standard Lesson</h4>
                    <p class="template-description">Complete lesson plan with all sections including objectives, materials, procedure, and assessment.</p>
                    <button class="btn btn-template">Use Template</button>
                </div>
                
                <!-- Science Lab Template -->
                <div class="template-card template-science" onclick="selectTemplate('science')">
                    <div class="template-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <h4 class="template-title">Science Lab</h4>
                    <p class="template-description">Perfect for laboratory experiments with safety guidelines and experimental procedure sections.</p>
                    <button class="btn btn-template">Use Template</button>
                </div>
                
                <!-- Art Class Template -->
                <div class="template-card template-art" onclick="selectTemplate('art')">
                    <div class="template-icon">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <h4 class="template-title">Art Class</h4>
                    <p class="template-description">Creative arts and crafts template with materials list and step-by-step creative process.</p>
                    <button class="btn btn-template">Use Template</button>
                </div>
                
                <!-- PE Activity Template -->
                <div class="template-card template-pe" onclick="selectTemplate('pe')">
                    <div class="template-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h4 class="template-title">PE Activity</h4>
                    <p class="template-description">Physical education and sports template with warm-up, main activity, and cool-down sections.</p>
                    <button class="btn btn-template">Use Template</button>
                </div>
            </div>
            
            <!-- Additional Templates -->
            <div class="template-grid">
                <!-- Mathematics Template -->
                <div class="template-card template-standard" onclick="selectTemplate('math')">
                    <div class="template-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h4 class="template-title">Mathematics</h4>
                    <p class="template-description">Structured math lesson with problem-solving exercises and practice questions.</p>
                    <button class="btn btn-template">Use Template</button>
                </div>
                
                <!-- Language Arts Template -->
                <div class="template-card template-science" onclick="selectTemplate('language')">
                    <div class="template-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h4 class="template-title">Language Arts</h4>
                    <p class="template-description">Reading and writing focused template with vocabulary and comprehension sections.</p>
                    <button class="btn btn-template">Use Template</button>
                </div>
                
                <!-- History Template -->
                <div class="template-card template-art" onclick="selectTemplate('history')">
                    <div class="template-icon">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <h4 class="template-title">History</h4>
                    <p class="template-description">Historical analysis template with timeline and primary source analysis sections.</p>
                    <button class="btn btn-template">Use Template</button>
                </div>
                
                <!-- Music Template -->
                <div class="template-card template-pe" onclick="selectTemplate('music')">
                    <div class="template-icon">
                        <i class="fas fa-music"></i>
                    </div>
                    <h4 class="template-title">Music</h4>
                    <p class="template-description">Music education template with listening exercises and performance guidelines.</p>
                    <button class="btn btn-template">Use Template</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Template selection function
        function selectTemplate(templateType) {
            // Show loading state
            const event = new Event('templateSelected');
            document.dispatchEvent(event);
            
            // Redirect to create lesson page with template parameter
            window.location.href = `lesson_created.php?action=create&template=${templateType}`;
        }
        
        // Add click handlers to all template cards
        document.querySelectorAll('.template-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (!e.target.classList.contains('btn-template')) {
                    const btn = this.querySelector('.btn-template');
                    btn.click();
                }
            });
        });
        
        // Animate stats counter
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 30);
        }
        
        // Fetch and display user stats
        document.addEventListener('DOMContentLoaded', function() {
            // Simulate fetching total lessons (replace with actual API call)
            const totalLessons = <?php 
                try {
                    [$stmt, $result, $conn] = q(
                        "SELECT COUNT(*) as total FROM lesson_plans WHERE user_id = ?",
                        'i',
                        [$user['id']]
                    );
                    $data = $result->fetch_assoc();
                    echo $data['total'] ?? 0;
                    $stmt->close();
                    $conn->close();
                } catch (Exception $e) {
                    echo '0';
                }
            ?>;
            
            animateCounter(document.getElementById('totalLessons'), totalLessons);
            
            // Add hover effects
            document.querySelectorAll('.template-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
        
        // Template selection handler
        document.addEventListener('templateSelected', function() {
            // Show loading overlay
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255,255,255,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            overlay.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                    <h5>Loading Template...</h5>
                </div>
            `;
            document.body.appendChild(overlay);
        });
    </script>
</body>
</html>