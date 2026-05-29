<?php
// Ensure session is started if not already handled by config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - EduGrade' : 'EduGrade Portal'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css"> 
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom py-3 mb-4">
        <div class="container-fluid px-4">
            
            <a href="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'student') ? 'student_dashboard.php' : 'faculty_dashboard.php'; ?>" class="navbar-brand fw-bold text-primary text-decoration-none">
                <i class="fas fa-layer-group me-2"></i>EduGrade Portal
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <div class="d-flex align-items-center mt-3 mt-lg-0">
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill px-3 dropdown-toggle d-flex align-items-center" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'User'); ?>&background=random&color=fff" class="rounded-circle me-2" width="26" height="26" alt="Avatar">
                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2" aria-labelledby="userMenu">
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
        </div>
    </nav>

    <main class="container-fluid px-4 pb-5">