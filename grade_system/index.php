<?php
require_once 'config.php';

if (isLoggedIn()) {
if ($user['role'] == 'student') {
    header("Location: student_dashboard.php");
} elseif ($user['role'] == 'admin') {
    header("Location: admin_dashboard.php");
} else {
    header("Location: faculty_dashboard.php");
}
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        if ($user['role'] == 'student') {
            header("Location: student_dashboard.php");
        } elseif ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: faculty_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Management System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <!-- Link new CSS -->
    <style>
        body { 
            background: #f8fafc;
            height: 100vh; 
            display: flex;
            align-items: center;
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            padding: 2rem;
        }
        .brand-icon {
            background: #4f46e5;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 1.5rem;
            box-shadow: 0 4px 14px 0 rgba(79, 70, 229, 0.39);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="text-center">
            <div class="brand-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Welcome Back</h3>
            <p class="text-muted mb-4">Sign in to the Grade Management System</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger rounded-3 text-sm"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" name="username" class="form-control" id="floatingInput" placeholder="Username" required>
                        <label for="floatingInput">Username</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password" required>
                        <label for="floatingPassword">Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 mb-3 shadow-sm">Sign In</button>
                </form>
            </div>
        </div>
        <div class="mt-4 text-muted small text-center">
            Demo credentials: student1 / password123
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>