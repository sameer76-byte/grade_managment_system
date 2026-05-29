<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) {
    header("Location: student_dashboard.php");
    exit();
}

// Get statistics
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$total_enrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f4f6; color: #1f2937; overflow-x: hidden; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }

        /* Gradient Stat Cards */
        .stat-card { border-radius: 16px; border: none; color: white; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); position: relative; overflow: hidden; }
        .stat-card::after { content: ''; position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; }
        .bg-gradient-primary { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); }
        .bg-gradient-success { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        .bg-gradient-warning { background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%); }

        /* Action Cards */
        .action-card { border-radius: 16px; border: none; transition: all 0.3s ease; cursor: pointer; text-decoration: none !important; display: block; height: 100%; }
        .action-card:hover { transform: translateY(-5px); }
        .icon-wrapper { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }

        /* 10 Distinct Colors */
        .card-blue { background-color: #eff6ff; color: #1e3a8a; box-shadow: 0 8px 16px rgba(59, 130, 246, 0.15); }
        .card-blue:hover { box-shadow: 0 12px 20px rgba(59, 130, 246, 0.3); }
        .icon-blue { background-color: #3b82f6; color: white; }

        .card-green { background-color: #f0fdf4; color: #14532d; box-shadow: 0 8px 16px rgba(34, 197, 94, 0.15); }
        .card-green:hover { box-shadow: 0 12px 20px rgba(34, 197, 94, 0.3); }
        .icon-green { background-color: #22c55e; color: white; }

        .card-yellow { background-color: #fffbeb; color: #78350f; box-shadow: 0 8px 16px rgba(245, 158, 11, 0.15); }
        .card-yellow:hover { box-shadow: 0 12px 20px rgba(245, 158, 11, 0.3); }
        .icon-yellow { background-color: #f59e0b; color: white; }

        .card-red { background-color: #fef2f2; color: #7f1d1d; box-shadow: 0 8px 16px rgba(239, 68, 68, 0.15); }
        .card-red:hover { box-shadow: 0 12px 20px rgba(239, 68, 68, 0.3); }
        .icon-red { background-color: #ef4444; color: white; }

        .card-purple { background-color: #faf5ff; color: #581c87; box-shadow: 0 8px 16px rgba(168, 85, 247, 0.15); }
        .card-purple:hover { box-shadow: 0 12px 20px rgba(168, 85, 247, 0.3); }
        .icon-purple { background-color: #a855f7; color: white; }

        .card-dark { background-color: #f8fafc; color: #0f172a; box-shadow: 0 8px 16px rgba(100, 116, 139, 0.15); }
        .card-dark:hover { box-shadow: 0 12px 20px rgba(100, 116, 139, 0.3); }
        .icon-dark { background-color: #64748b; color: white; }

        .card-cyan { background-color: #ecfeff; color: #164e63; box-shadow: 0 8px 16px rgba(6, 182, 212, 0.15); }
        .card-cyan:hover { box-shadow: 0 12px 20px rgba(6, 182, 212, 0.3); }
        .icon-cyan { background-color: #06b6d4; color: white; }

        .card-indigo { background-color: #e0e7ff; color: #3730a3; box-shadow: 0 8px 16px rgba(99, 102, 241, 0.15); }
        .card-indigo:hover { box-shadow: 0 12px 20px rgba(99, 102, 241, 0.3); }
        .icon-indigo { background-color: #6366f1; color: white; }

        .card-teal { background-color: #f0fdfa; color: #134e4a; box-shadow: 0 8px 16px rgba(20, 184, 166, 0.15); }
        .card-teal:hover { box-shadow: 0 12px 20px rgba(20, 184, 166, 0.3); }
        .icon-teal { background-color: #14b8a6; color: white; }

        .card-orange { background-color: #fff7ed; color: #9a3412; box-shadow: 0 8px 16px rgba(234, 88, 12, 0.15); }
        .card-orange:hover { box-shadow: 0 12px 20px rgba(234, 88, 12, 0.3); }
        .icon-orange { background-color: #f97316; color: white; }
    </style>
</head>
<body>

    <nav class="top-navbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-graduation-cap fs-5"></i>
            </div>
            <h5 class="mb-0 fw-bold">EduGrade Portal</h5>
        </div>
        <div>
            <div class="dropdown">
                <button class="btn btn-dark border-secondary dropdown-toggle d-flex align-items-center rounded-pill px-3" type="button" id="userMenu" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=4f46e5&color=fff" class="rounded-circle me-2" width="28" height="28">
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

    <div class="container py-4 pb-5">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="fw-bold mb-1">Faculty Dashboard</h2>
                <p class="text-muted mb-0">Welcome back! Here is your system overview.</p>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card stat-card bg-gradient-primary h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Total Students</p>
                            <h2 class="fw-bold mb-0 display-5"><?php echo $total_students; ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x text-white opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-gradient-success h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Active Courses</p>
                            <h2 class="fw-bold mb-0 display-5"><?php echo $total_courses; ?></h2>
                        </div>
                        <i class="fas fa-book-open fa-3x text-white opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-gradient-warning h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Enrollments</p>
                            <h2 class="fw-bold mb-0 display-5"><?php echo $total_enrollments; ?></h2>
                        </div>
                        <i class="fas fa-user-graduate fa-3x text-white opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="fw-bold mb-4">Quick Actions</h4>
        
        <div class="row g-4 pb-5">
            
            <div class="col-md-6 col-lg-4">
                <a href="manage_students.php" class="card action-card card-blue p-4">
                    <div class="icon-wrapper icon-blue"><i class="fas fa-user-edit"></i></div>
                    <h5 class="fw-bold">Manage Students</h5>
                    <p class="mb-0 opacity-75 small">Add, edit, or remove student profiles from the database.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="manage_courses.php" class="card action-card card-green p-4">
                    <div class="icon-wrapper icon-green"><i class="fas fa-layer-group"></i></div>
                    <h5 class="fw-bold">Manage Courses</h5>
                    <p class="mb-0 opacity-75 small">Create new curriculum, edit details, or delete active courses.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="enroll_students.php" class="card action-card card-yellow p-4">
                    <div class="icon-wrapper icon-yellow"><i class="fas fa-user-plus"></i></div>
                    <h5 class="fw-bold">Enroll Students</h5>
                    <p class="mb-0 opacity-75 small">Assign students to specific courses for the current semester.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="grade_entry.php" class="card action-card card-red p-4">
                    <div class="icon-wrapper icon-red"><i class="fas fa-marker"></i></div>
                    <h5 class="fw-bold">Grade Entry</h5>
                    <p class="mb-0 opacity-75 small">Input internal, practical, and final exam marks efficiently.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="analytics.php" class="card action-card card-purple p-4">
                    <div class="icon-wrapper icon-purple"><i class="fas fa-chart-pie"></i></div>
                    <h5 class="fw-bold">Class Analytics</h5>
                    <p class="mb-0 opacity-75 small">View performance metrics, averages, and detailed pass rates.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="grade_audit.php" class="card action-card card-dark p-4">
                    <div class="icon-wrapper icon-dark"><i class="fas fa-history"></i></div>
                    <h5 class="fw-bold">Grade Audit Log</h5>
                    <p class="mb-0 opacity-75 small">View secure grade change history and complete audit trails.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="manage_announcements.php" class="card action-card card-cyan p-4">
                    <div class="icon-wrapper icon-cyan"><i class="fas fa-bullhorn"></i></div>
                    <h5 class="fw-bold">Notice Board</h5>
                    <p class="mb-0 opacity-75 small">Broadcast important messages and system announcements.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="mark_attendance.php" class="card action-card card-indigo p-4">
                    <div class="icon-wrapper icon-indigo"><i class="fas fa-user-check"></i></div>
                    <h5 class="fw-bold">Class Attendance</h5>
                    <p class="mb-0 opacity-75 small">Record daily present/absent logs for your assigned rosters.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="view_grades.php" class="card action-card card-teal p-4">
                    <div class="icon-wrapper icon-teal"><i class="fas fa-eye"></i></div>
                    <h5 class="fw-bold">View Grades</h5>
                    <p class="mb-0 opacity-75 small">Access a read-only matrix of complete student course marks.</p>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="teaching_log.php" class="card action-card card-orange p-4">
                    <div class="icon-wrapper icon-orange"><i class="fas fa-chalkboard"></i></div>
                    <h5 class="fw-bold">Teaching Log</h5>
                    <p class="mb-0 opacity-75 small">Record daily topics taught per course for your records.</p>
                </a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>