<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) die("Access denied");

$selected_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$message = '';

// Fetch all courses for dropdown
$courses = $pdo->query("SELECT id, course_code, course_name, semester FROM courses ORDER BY course_code")->fetchAll();

$students_data = [];
$course_info = null;

if ($selected_course > 0) {
    // Get course details
    $stmt = $pdo->prepare("SELECT course_code, course_name, credits FROM courses WHERE id = ?");
    $stmt->execute([$selected_course]);
    $course_info = $stmt->fetch();
    
    if ($course_info) {
        // Fetch all students enrolled in this course with their grades
        $sql = "SELECT s.roll_number, u.full_name,
                COALESCE(MAX(CASE WHEN g.assessment_type = 'internal' THEN g.marks_obtained END), 0) as internal,
                COALESCE(MAX(CASE WHEN g.assessment_type = 'practical' THEN g.marks_obtained END), 0) as practical,
                COALESCE(MAX(CASE WHEN g.assessment_type = 'final' THEN g.marks_obtained END), 0) as final_exam
                FROM enrollments e
                JOIN students s ON e.student_id = s.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN grades g ON e.id = g.enrollment_id
                WHERE e.course_id = ?
                GROUP BY e.student_id, s.roll_number, u.full_name
                ORDER BY s.roll_number";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$selected_course]);
        $students_data = $stmt->fetchAll();
        
        if (count($students_data) == 0) {
            $message = "<div class='alert alert-warning border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-exclamation-triangle me-2'></i>No students enrolled in this course.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-times-circle me-2'></i>Course not found.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student Grades - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }

        /* Top Navbar */
        .top-navbar {
            background-color: #111827;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Modern Card Styling */
        .card-modern {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            background: #ffffff;
        }

        /* Form Inputs */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
            background-color: #f8fafc;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6; /* Blue */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            background-color: #ffffff;
        }

        /* Buttons */
        .btn-blue {
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s;
        }
        .btn-blue:hover {
            background-color: #2563eb;
            color: white;
            transform: translateY(-1px);
        }

        /* Table Styling */
        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table thead th {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .table tbody tr:hover td { background-color: #f8fafc; }
        
        /* Subtle Row Highlight for Failures instead of harsh red */
        .row-fail td { background-color: #fef2f2 !important; }

        /* Custom Scrollbar */
        .table-responsive::-webkit-scrollbar { width: 6px; height: 6px; }
        .table-responsive::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* KPI Cards */
        .kpi-card { padding: 1.5rem; border-radius: 16px; display: flex; align-items: center; }
        .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; margin-right: 1rem; }
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
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=3b82f6&color=fff" class="rounded-circle me-2" width="28" height="28">
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
                <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-decoration-none fw-semibold" style="color: #3b82f6;">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Grades</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">Student Grade Report</h3>
                <p class="text-muted mb-0">Review comprehensive marks and pass/fail statuses for a specific course.</p>
            </div>
        </div>
        
        <?php echo $message; ?>
        
        <div class="card card-modern mb-4">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-9 col-lg-6">
                        <label class="form-label text-muted">Select Course Roster</label>
                        <select name="course_id" class="form-select" required>
                            <option value="" disabled <?php echo !$selected_course ? 'selected' : ''; ?>>-- Choose a course --</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name'] . ' (Sem '.$course['semester'].')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-blue w-100">
                            <i class="fas fa-search me-2"></i>View Grades
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($selected_course > 0 && !empty($students_data) && $course_info): 
            // Pre-calculate statistics
            $total_students = count($students_data);
            $pass_count = 0;
            $sum_percentages = 0;
            
            foreach($students_data as $student) {
                $total = $student['internal'] + $student['practical'] + $student['final_exam'];
                $percentage = ($total / 100) * 100;
                if ($percentage >= 40) $pass_count++;
                $sum_percentages += $percentage;
            }
            $pass_rate = ($total_students > 0) ? round(($pass_count/$total_students)*100, 1) : 0;
            $avg_score = ($total_students > 0) ? number_format($sum_percentages/$total_students, 1) : 0;
        ?>
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="kpi-card card-modern border border-primary border-opacity-10 bg-white shadow-sm">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-users"></i></div>
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Total Students</p>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $total_students; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card card-modern border border-success border-opacity-25 bg-white shadow-sm">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Passed Students</p>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $pass_count; ?> <span class="fs-6 text-muted fw-normal">(<?php echo $pass_rate; ?>%)</span></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card card-modern border border-warning border-opacity-50 bg-white shadow-sm">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning" style="color: #d97706 !important;"><i class="fas fa-chart-bar"></i></div>
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-0" style="font-size: 0.75rem;">Class Average</p>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $avg_score; ?>%</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-modern border-0">
            <div class="card-body p-0">
                <div class="p-4 border-bottom border-light d-flex align-items-center bg-light" style="border-radius: 16px 16px 0 0;">
                    <i class="fas fa-file-alt text-muted me-2"></i>
                    <h6 class="fw-bold mb-0">Grade Matrix: <?php echo htmlspecialchars($course_info['course_code'] . ' - ' . $course_info['course_name']); ?></h6>
                    <span class="badge bg-white text-dark border ms-auto px-3 py-2 shadow-sm"><?php echo $course_info['credits']; ?> Credits</span>
                </div>
                
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Student Identity</th>
                                <th class="text-center">Internal <br><span class="text-muted text-lowercase fw-normal">/30</span></th>
                                <th class="text-center">Practical <br><span class="text-muted text-lowercase fw-normal">/20</span></th>
                                <th class="text-center">Final <br><span class="text-muted text-lowercase fw-normal">/50</span></th>
                                <th class="text-center">Total <br><span class="text-muted text-lowercase fw-normal">/100</span></th>
                                <th class="text-center">Score</th>
                                <th class="text-center">GP</th>
                                <th class="text-end pe-4">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($students_data as $student):
                                $total = $student['internal'] + $student['practical'] + $student['final_exam'];
                                $percentage = ($total / 100) * 100;
                                $grade_point = getGradePoint($percentage);
                                $status = ($percentage >= 40) ? 'Pass' : 'Fail';
                                
                                // Apply faint red background only to failing rows
                                $row_class = ($status == 'Fail') ? 'row-fail' : '';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['full_name']); ?>&background=f1f5f9&color=475569" class="rounded-circle me-3" width="36" height="36">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($student['roll_number']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center fw-medium"><?php echo $student['internal']; ?></td>
                                <td class="text-center fw-medium"><?php echo $student['practical']; ?></td>
                                <td class="text-center fw-medium"><?php echo $student['final_exam']; ?></td>
                                <td class="text-center">
                                    <span class="fw-bold text-blue fs-6"><?php echo $total; ?></span>
                                </td>
                                <td class="text-center fw-semibold text-dark"><?php echo number_format($percentage, 1); ?>%</td>
                                <td class="text-center fw-bold"><?php echo $grade_point; ?></td>
                                <td class="text-end pe-4">
                                    <?php if ($status == 'Pass'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-1">Pass</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-3 py-1">Fail</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>