<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch statistics
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_faculty = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'faculty'")->fetchColumn();
$total_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$total_enrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
$total_grades = $pdo->query("SELECT COUNT(*) FROM grades")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Modern Filled Aesthetic */
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            overflow-x: hidden;
        }

        /* Top Navbar */
        .top-navbar {
            background-color: #111827; /* Solid Dark */
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Gradient Stat Cards */
        .stat-card {
            border-radius: 16px;
            border: none;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .bg-gradient-indigo { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .bg-gradient-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .bg-gradient-amber { background: linear-gradient(135deg, #ea580c 0%, #b45309 100%); }
        .bg-gradient-cyan { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }

        /* Tinted Action Cards */
        .action-card {
            border-radius: 16px;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none !important;
            display: block;
            height: 100%;
        }
        .action-card:hover { transform: translateY(-5px); }
        
        /* Solid Tint Colors for Actions */
        .card-blue { background-color: #eff6ff; color: #1e3a8a; box-shadow: 0 8px 16px rgba(59, 130, 246, 0.15); }
        .card-blue:hover { box-shadow: 0 12px 20px rgba(59, 130, 246, 0.3); }
        .icon-blue { background-color: #3b82f6; color: white; }

        .card-green { background-color: #f0fdf4; color: #14532d; box-shadow: 0 8px 16px rgba(34, 197, 94, 0.15); }
        .card-green:hover { box-shadow: 0 12px 20px rgba(34, 197, 94, 0.3); }
        .icon-green { background-color: #22c55e; color: white; }

        .card-dark { background-color: #f8fafc; color: #0f172a; box-shadow: 0 8px 16px rgba(100, 116, 139, 0.15); }
        .card-dark:hover { box-shadow: 0 12px 20px rgba(100, 116, 139, 0.3); }
        .icon-dark { background-color: #64748b; color: white; }

        .card-orange { background-color: #fff7ed; color: #9a3412; box-shadow: 0 8px 16px rgba(234, 88, 12, 0.15); }
        .card-orange:hover { box-shadow: 0 12px 20px rgba(234, 88, 12, 0.3); }
        .icon-orange { background-color: #f97316; color: white; }

        .icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <nav class="top-navbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-shield-alt fs-5"></i>
            </div>
            <h5 class="mb-0 fw-bold">EduGrade Admin Panel</h5>
        </div>
        <div>
            <div class="dropdown">
                <button class="btn btn-dark border-secondary dropdown-toggle d-flex align-items-center rounded-pill px-3" type="button" id="userMenu" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=7c3aed&color=fff" class="rounded-circle me-2" width="28" height="28">
                    <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item fw-semibold" href="profile.php"><i class="fas fa-user-cog me-2 text-muted"></i>My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger fw-semibold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-3 pb-5">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="fw-bold mb-1">System Overview</h2>
                <p class="text-muted mb-0">High-level administrative metrics and user management.</p>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-indigo h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Registered Students</p>
                            <h2 class="fw-bold mb-0 display-5"><?php echo $total_students; ?></h2>
                        </div>
                        <i class="fas fa-user-graduate fa-3x text-white opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-emerald h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Active Faculty</p>
                            <h2 class="fw-bold mb-0 display-5"><?php echo $total_faculty; ?></h2>
                        </div>
                        <i class="fas fa-chalkboard-teacher fa-3x text-white opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-amber h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Total Courses</p>
                            <h2 class="fw-bold mb-0 display-5"><?php echo $total_courses; ?></h2>
                        </div>
                        <i class="fas fa-book-open fa-3x text-white opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-cyan h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Total Enrollments</p>
                            <h2 class="fw-bold mb-0 display-5"><?php echo $total_enrollments; ?></h2>
                        </div>
                        <i class="fas fa-link fa-3x text-white opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="fw-bold mb-4">System Administration</h4>
        
        <div class="row g-4 pb-5">
            
            <div class="col-md-6 col-lg-4">
                <a href="manage_faculty.php" class="card action-card card-blue p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="icon-wrapper icon-blue">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <span class="bg-white rounded-pill px-3 py-1 text-primary fw-bold text-sm shadow-sm border border-primary border-opacity-25">Faculty</span>
                    </div>
                    <h5 class="fw-bold mt-2">Manage Faculty</h5>
                    <p class="mb-0 opacity-75 small">Onboard new professors, manage credentials, and remove inactive staff accounts.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="all_users.php" class="card action-card card-green p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="icon-wrapper icon-green">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <span class="bg-white rounded-pill px-3 py-1 text-success fw-bold text-sm shadow-sm border border-success border-opacity-25">Global</span>
                    </div>
                    <h5 class="fw-bold mt-2">All System Users</h5>
                    <p class="mb-0 opacity-75 small">Complete directory of all registered accounts. Manage roles, passwords, and access.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="grade_audit.php" class="card action-card card-dark p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="icon-wrapper icon-dark">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="bg-white rounded-pill px-3 py-1 text-secondary fw-bold text-sm shadow-sm border border-secondary border-opacity-25"><?php echo number_format($total_grades); ?> Logs</span>
                    </div>
                    <h5 class="fw-bold mt-2">Grade Audit Log</h5>
                    <p class="mb-0 opacity-75 small">Monitor academic integrity. View complete history of all grade entries and modifications.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="admin_teaching_logs.php" class="card action-card card-orange p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="icon-wrapper icon-orange">
                            <i class="fas fa-list-alt"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mt-2">Teaching Logs (All)</h5>
                    <p class="mb-0 opacity-75 small">Review and filter global faculty daily teaching logs to monitor curriculum pacing.</p>
                </a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>