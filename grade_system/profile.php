<?php
require_once 'config.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT username, full_name, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Determine back link based on role
$dashboard_link = isStudent() ? 'student_dashboard.php' : 'faculty_dashboard.php';
$theme_color = isStudent() ? '#4f46e5' : '#111827'; // Indigo for students, Dark for faculty
$btn_class = isStudent() ? 'btn-indigo' : 'btn-dark';

// Handle General Info Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['full_name']);
    $new_email = trim($_POST['email']);
    
    if (empty($new_name)) {
        $error_msg = "Full name cannot be empty.";
    } else {
        $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $update->execute([$new_name, $new_email, $user_id]);
        
        // Update session variable so the navbar reflects the change immediately
        $_SESSION['full_name'] = $new_name;
        $user['full_name'] = $new_name;
        $user['email'] = $new_email;
        
        $success_msg = "Profile information updated successfully!";
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    // Verify current password
    $verify_stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $verify_stmt->execute([$user_id]);
    $db_pass = $verify_stmt->fetchColumn();
    
    if (!password_verify($current_pass, $db_pass)) {
        $error_msg = "Incorrect current password.";
    } elseif ($new_pass !== $confirm_pass) {
        $error_msg = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error_msg = "New password must be at least 6 characters long.";
    } else {
        $hashed_new = password_hash($new_pass, PASSWORD_DEFAULT);
        $update_pass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_pass->execute([$hashed_new, $user_id]);
        $success_msg = "Password changed successfully! Use your new password next time you log in.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-modern { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); background: #ffffff; }
        
        .form-control { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; background-color: #f8fafc; }
        .form-control:focus { border-color: <?php echo $theme_color; ?>; box-shadow: 0 0 0 3px <?php echo $theme_color; ?>25; background-color: #ffffff; }
        .form-label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.4rem; }
        
        .btn-indigo { background-color: #4f46e5; color: white; border-radius: 8px; font-weight: 600; padding: 0.6rem 1.2rem; }
        .btn-indigo:hover { background-color: #4338ca; color: white; }
        .btn-dark { background-color: #1f2937; color: white; border-radius: 8px; font-weight: 600; padding: 0.6rem 1.2rem; }
        .btn-dark:hover { background-color: #111827; color: white; }
        
        .profile-header { background: linear-gradient(135deg, <?php echo $theme_color; ?> 0%, #374151 100%); padding: 3rem 0; border-radius: 16px 16px 0 0; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: -60px; background-color: white; }
    </style>
</head>
<body>
    
    <nav class="top-navbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-user-circle fs-5"></i>
            </div>
            <h5 class="mb-0 fw-bold">EduGrade Portal</h5>
        </div>
        <div>
            <a href="<?php echo $dashboard_link; ?>" class="btn btn-outline-light rounded-pill px-4 fw-semibold border-0">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>
    
    <div class="container pb-5" style="max-width: 900px;">
        
        <?php if($success_msg) echo "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium mb-4'><i class='fas fa-check-circle me-2'></i>$success_msg</div>"; ?>
        <?php if($error_msg) echo "<div class='alert alert-danger border-0 shadow-sm rounded-3 fw-medium mb-4'><i class='fas fa-exclamation-triangle me-2'></i>$error_msg</div>"; ?>

        <div class="card card-modern mb-4">
            <div class="profile-header"></div>
            <div class="card-body px-5 pb-5 text-center">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=f1f5f9&color=1e293b&size=256" class="profile-avatar mb-3" alt="Profile">
                <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-muted mb-3"><i class="fas fa-at me-1"></i><?php echo htmlspecialchars($user['username']); ?> • <span class="badge bg-light text-dark border text-uppercase"><?php echo htmlspecialchars($user['role']); ?></span></p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card card-modern h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-light text-secondary rounded p-2 me-3">
                                <i class="fas fa-id-card fa-lg"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Personal Details</h5>
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Not provided">
                            </div>
                            <button type="submit" name="update_profile" class="btn <?php echo $btn_class; ?> w-100">
                                Save Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-modern h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-light text-secondary rounded p-2 me-3">
                                <i class="fas fa-lock fa-lg"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Security Settings</h5>
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="••••••••" required minlength="6">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required minlength="6">
                            </div>
                            <button type="submit" name="change_password" class="btn btn-danger w-100" style="border-radius: 8px; font-weight: 600; padding: 0.6rem 1.2rem;">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>