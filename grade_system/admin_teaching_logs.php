<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if ($_SESSION['role'] != 'admin') die("Access denied");

$filter_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$filter_faculty = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : 0;

$sql = "SELECT l.*, c.course_code, c.course_name, u.full_name as teacher_name 
        FROM teaching_log l
        JOIN courses c ON l.course_id = c.id
        JOIN users u ON l.faculty_id = u.id
        WHERE 1=1";
$params = [];
if ($filter_course > 0) {
    $sql .= " AND l.course_id = ?";
    $params[] = $filter_course;
}
if ($filter_faculty > 0) {
    $sql .= " AND l.faculty_id = ?";
    $params[] = $filter_faculty;
}
$sql .= " ORDER BY l.taught_date DESC, l.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$courses = $pdo->query("SELECT id, course_code, course_name FROM courses ORDER BY course_code")->fetchAll();
$faculty_list = $pdo->query("SELECT id, full_name FROM users WHERE role = 'faculty' ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Teaching Logs - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-modern { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); background: #ffffff; }
        
        /* Form Inputs */
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; background-color: #f8fafc; }
        .form-control:focus, .form-select:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15); background-color: #ffffff; }
        
        /* Buttons */
        .btn-orange { background-color: #f97316; color: white; border-radius: 8px; font-weight: 600; padding: 0.6rem 1.2rem; transition: all 0.2s; }
        .btn-orange:hover { background-color: #ea580c; color: white; transform: translateY(-1px); }
        .btn-light-orange { background-color: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; border-radius: 8px; font-weight: 600; padding: 0.6rem 1.2rem; }
        .btn-light-orange:hover { background-color: #ffedd5; color: #c2410c; }

        /* Table Styling */
        .table { margin-bottom: 0; border-collapse: separate; border-spacing: 0; }
        .table thead th { background-color: #f8fafc; color: #64748b; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #e2e8f0; padding: 1rem; position: sticky; top: 0; z-index: 10; }
        .table tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .table tbody tr:hover td { background-color: #fff7ed; }

        .table-responsive::-webkit-scrollbar { width: 6px; height: 6px; }
        .table-responsive::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <nav class="top-navbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-2 me-3"><i class="fas fa-shield-alt fs-5"></i></div>
            <h5 class="mb-0 fw-bold">EduGrade Admin Panel</h5>
        </div>
        <div>
            <div class="dropdown">
                <button class="btn btn-dark border-secondary dropdown-toggle d-flex align-items-center rounded-pill px-3" type="button" data-bs-toggle="dropdown">
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

    <div class="container-fluid px-4 pb-5">
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-decoration-none fw-semibold" style="color: #f97316;">Dashboard</a></li>
                <li class="breadcrumb-item active">Teaching Logs</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">Global Teaching Audit</h3>
                <p class="text-muted mb-0">Monitor and filter the daily teaching logs recorded by faculty members.</p>
            </div>
        </div>
        
        <div class="card card-modern border-0">
            <div class="card-body p-0">
                
                <div class="p-4 border-bottom border-light bg-white" style="border-radius: 16px 16px 0 0;">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label text-muted fw-semibold small">Filter by Course</label>
                            <select name="course_id" class="form-select">
                                <option value="0">All Courses</option>
                                <?php foreach($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($filter_course == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted fw-semibold small">Filter by Faculty</label>
                            <select name="faculty_id" class="form-select">
                                <option value="0">All Faculty</option>
                                <?php foreach($faculty_list as $f): ?>
                                    <option value="<?php echo $f['id']; ?>" <?php echo ($filter_faculty == $f['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($f['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-orange flex-grow-1"><i class="fas fa-filter me-2"></i>Apply Filter</button>
                            <a href="admin_teaching_logs.php" class="btn btn-light-orange"><i class="fas fa-times"></i></a>
                        </div>
                    </form>
                </div>
                
                <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Date Taught</th>
                                <th>Teacher</th>
                                <th>Course</th>
                                <th>Topic & Description</th>
                                <th class="text-end pe-4">Logged On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-orange bg-opacity-10 text-orange rounded p-2 me-3" style="color: #ea580c; background-color: #fff7ed;">
                                            <i class="fas fa-calendar-day"></i>
                                        </div>
                                        <?php echo date('M j, Y', strtotime($log['taught_date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($log['teacher_name']); ?>&background=eff6ff&color=1e3a8a" class="rounded-circle me-2" width="32" height="32">
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($log['teacher_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 shadow-sm"><?php echo htmlspecialchars($log['course_code']); ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($log['topic_title']); ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 350px;" title="<?php echo htmlspecialchars($log['topic_description']); ?>">
                                        <?php echo htmlspecialchars($log['topic_description'] ?: 'No description provided.'); ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4 text-muted small fst-italic">
                                    <?php echo date('M j, Y g:i a', strtotime($log['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(count($logs) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="fas fa-folder-open fa-3x opacity-25"></i></div>
                                    <h6 class="fw-bold mb-1">No Logs Found</h6>
                                    <p class="small text-muted mb-0">Try adjusting your filters or clear them to see all logs.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>