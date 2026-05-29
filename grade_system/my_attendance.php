<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isStudent()) die("Access denied");

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student_id = $stmt->fetchColumn();

// Fetch all attendance records for this student
$sql = "SELECT c.course_code, c.course_name, a.attendance_date, a.status 
        FROM attendance a 
        JOIN enrollments e ON a.enrollment_id = e.id 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.student_id = ? 
        ORDER BY a.attendance_date DESC, c.course_code";
$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id]);
$attendance_logs = $stmt->fetchAll();

// Calculate Analytics
$total_classes = count($attendance_logs);
$classes_attended = 0;
foreach($attendance_logs as $log) {
    if($log['status'] == 'present') $classes_attended++;
}
$attendance_percentage = $total_classes > 0 ? round(($classes_attended / $total_classes) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-modern { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); background: #ffffff; }
        .table thead th { background-color: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #e2e8f0; }
        .table tbody td { padding: 1rem; vertical-align: middle; }
    </style>
</head>
<body>
    <nav class="top-navbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-2 me-3"><i class="fas fa-user-graduate fs-5"></i></div>
            <h5 class="mb-0 fw-bold">EduGrade Student Portal</h5>
        </div>
        <a href="student_dashboard.php" class="btn btn-outline-light rounded-pill px-4 fw-semibold border-0"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
    </nav>
    
    <div class="container-fluid px-4 pb-5" style="max-width: 1000px;">
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="student_dashboard.php" class="text-decoration-none fw-semibold" style="color: #6366f1;">Dashboard</a></li>
                <li class="breadcrumb-item active">My Attendance</li>
            </ol>
        </nav>

        <h2 class="fw-bold mb-4">Attendance Report</h2>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card card-modern h-100 border-0 bg-white shadow-sm p-4 d-flex flex-row align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-4 me-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-chart-pie fa-2x"></i>
                    </div>
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.8rem; letter-spacing: 1px;">Overall Attendance</p>
                        <h2 class="fw-bold mb-0 text-dark"><?php echo $attendance_percentage; ?>%</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-modern h-100 border-0 bg-white shadow-sm p-4 d-flex flex-row align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-4 me-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.8rem; letter-spacing: 1px;">Classes Attended</p>
                        <h2 class="fw-bold mb-0 text-dark"><?php echo $classes_attended; ?> <span class="fs-5 text-muted fw-normal">/ <?php echo $total_classes; ?></span></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-modern">
            <div class="card-body p-0">
                <div class="p-4 border-bottom d-flex align-items-center bg-light" style="border-radius: 16px 16px 0 0;">
                    <i class="fas fa-list text-muted me-2"></i>
                    <h6 class="fw-bold mb-0">Detailed Log</h6>
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Course</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($attendance_logs as $log): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark"><?php echo date('M j, Y', strtotime($log['attendance_date'])); ?></td>
                                <td>
                                    <span class="fw-bold"><?php echo htmlspecialchars($log['course_code']); ?></span>
                                    <span class="text-muted ms-2 small"><?php echo htmlspecialchars($log['course_name']); ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($log['status'] == 'present'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-2"><i class="fas fa-check me-1"></i> Present</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-3 py-2"><i class="fas fa-times me-1"></i> Absent</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($attendance_logs)): ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">No attendance records found yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>