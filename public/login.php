<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "lesopo";

// Database connection
try {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's login or registration
    if (isset($_POST['login'])) {
        // Login process
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        
        if (!empty($email) && !empty($pass)) {
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user && password_verify($pass, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                header('Location: dashboard.php');
                exit;
            } else {
                $message = 'Email or password is incorrect.';
            }
            $stmt->close();
        } else {
            $message = 'Please fill in all fields.';
        }
    } elseif (isset($_POST['register'])) {
        // Registration process
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        $role = 'teacher'; // Default role
        
        if (!empty($name) && !empty($email) && !empty($pass) && !empty($confirm_pass)) {
            if ($pass !== $confirm_pass) {
                $message = 'Passwords do not match.';
            } elseif (strlen($pass) < 6) {
                $message = 'Password must be at least 6 characters long.';
            } else {
                // Check if email already exists
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = 'Email already registered. Please use a different email.';
                } else {
                    // Insert new user
                    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
                    $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                    $insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
                    
                    if ($insert_stmt->execute()) {
                        $message = 'Registration successful! You can now login.';
                        // Switch to login view after successful registration
                        echo "<script>document.getElementById('login-form').style.display='block'; document.getElementById('register-form').style.display='none';</script>";
                    } else {
                        $message = 'Registration failed. Please try again.';
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
        } else {
            $message = 'Please fill in all fields.';
        }
    }
}
$conn->close();
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - LEPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --success-color: #4bb543;
            --gradient-bg: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        }
        
        body {
            background: var(--gradient-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            background: var(--gradient-bg);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .login-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .login-header .subtitle {
            opacity: 0.9;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .login-body {
            padding: 2.5rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .btn-primary {
            background: var(--gradient-bg);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }
        
        .input-group-icon {
            position: relative;
        }
        
        .input-group-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
        }
        
        .input-group-icon .form-control {
            padding-left: 45px;
        }
        
        .features-list {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: #495057;
        }
        
        .feature-item i {
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .switch-form {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .switch-form a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        
        .switch-form a:hover {
            text-decoration: underline;
        }
        
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .login-body {
                padding: 2rem 1.5rem;
            }
            
            body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="login-container">
                <div class="login-header">
                    <h4><i class="fas fa-chalkboard-teacher me-2"></i>LEPOS</h4>
                    <div class="subtitle">Lesson Plan Organizer System</div>
                </div>
                
                <div class="login-body">
                    <?php if($message): ?>
                    <div class="alert alert-<?php echo strpos($message, 'successful') !== false ? 'success' : 'danger'; ?> d-flex align-items-center">
                        <i class="fas <?php echo strpos($message, 'successful') !== false ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                        <?=htmlspecialchars($message)?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <div id="login-form" class="form-section active">
                        <h5 class="text-center mb-4" style="color: var(--secondary-color);">Welcome Back</h5>
                        
                        <form method="post">
                            <div class="mb-4">
                                <label class="form-label">Email Address</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input name="email" type="email" class="form-control" placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-lock"></i>
                                    <input name="password" type="password" class="form-control" placeholder="Enter your password" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>
                        
                        <div class="switch-form">
                            Don't have an account? <a onclick="showRegister()">Create one here</a>
                        </div>
                    </div>
                    
                    <!-- Registration Form -->
                    <div id="register-form" class="form-section">
                        <h5 class="text-center mb-4" style="color: var(--secondary-color);">Create Account</h5>
                        
                        <form method="post">
                            <div class="mb-4">
                                <label class="form-label">Full Name</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-user"></i>
                                    <input name="name" type="text" class="form-control" placeholder="Enter your full name" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Email Address</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input name="email" type="email" class="form-control" placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-lock"></i>
                                    <input name="password" type="password" class="form-control" placeholder="Create a password" required>
                                </div>
                                <small class="text-muted">Password must be at least 6 characters long</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-lock"></i>
                                    <input name="confirm_password" type="password" class="form-control" placeholder="Confirm your password" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="register" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </form>
                        
                        <div class="switch-form">
                            Already have an account? <a onclick="showLogin()">Login here</a>
                        </div>
                    </div>
                    
                    <div class="features-list">
                        <h6 class="mb-3" style="color: var(--secondary-color);"><i class="fas fa-star me-2"></i>System Features</h6>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Create and manage lesson plans</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Organize teaching materials</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Track curriculum progress</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showRegister() {
        document.getElementById('login-form').classList.remove('active');
        document.getElementById('register-form').classList.add('active');
    }
    
    function showLogin() {
        document.getElementById('register-form').classList.remove('active');
        document.getElementById('login-form').classList.add('active');
    }
    
    // Show register form if there was a registration error
    <?php if (isset($_POST['register'])): ?>
        document.getElementById('login-form').classList.remove('active');
        document.getElementById('register-form').classList.add('active');
    <?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>