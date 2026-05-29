<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isStudent()) {
    header("Location: faculty_dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// Get current semester grades
$sql = "SELECT c.course_code, c.course_name, c.credits, c.semester,
        SUM(CASE WHEN g.assessment_type='internal' THEN g.marks_obtained ELSE 0 END) as internal,
        SUM(CASE WHEN g.assessment_type='practical' THEN g.marks_obtained ELSE 0 END) as practical,
        SUM(CASE WHEN g.assessment_type='final' THEN g.marks_obtained ELSE 0 END) as final_exam
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN grades g ON e.id = g.enrollment_id
        WHERE e.student_id = ? AND e.semester = ?
        GROUP BY e.id, c.id";
$stmt2 = $pdo->prepare($sql);
$stmt2->execute([$student['id'], $student['current_semester']]);
$courses = $stmt2->fetchAll();

// Calculate SGPA
$total_credits = 0;
$total_grade_points = 0;
foreach($courses as $course) {
    $total_marks = $course['internal'] + $course['practical'] + $course['final_exam'];
    $max_marks = 100; // 30+20+50
    $percentage = ($total_marks / $max_marks) * 100;
    $grade_point = getGradePoint($percentage);
    $total_grade_points += $grade_point * $course['credits'];
    $total_credits += $course['credits'];
}
$sgpa = $total_credits > 0 ? round($total_grade_points / $total_credits, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        .bg-gradient-primary { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); }
        .bg-gradient-success { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        .bg-gradient-warning { background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%); }

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
        }
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        
        /* Buttons */
        .btn-indigo {
            background-color: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s;
        }
        .btn-indigo:hover {
            background-color: #4338ca;
            color: white;
            transform: translateY(-1px);
        }
        
        /* PDF specific styles */
        .pdf-header { display: none; }
    </style>
</head>
<body>
    
    <nav class="top-navbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-user-graduate fs-5"></i>
            </div>
            <h5 class="mb-0 fw-bold">EduGrade Student Portal</h5>
        </div>
        <div>
            <div class="dropdown">
                <button class="btn btn-dark border-secondary dropdown-toggle d-flex align-items-center rounded-pill px-3" type="button" id="userMenu" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=4f46e5&color=fff" class="rounded-circle me-2" width="28" height="28">
                    <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item fw-semibold" href="student_dashboard.php"><i class="fas fa-home me-2 text-muted"></i>Dashboard</a></li>
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
                <h2 class="fw-bold mb-1">My Dashboard</h2>
                <p class="text-muted mb-0">Welcome back! Here is your academic overview.</p>
            </div>
            <div>
                <button class="btn btn-indigo shadow-sm" onclick="generateTranscript()">
                    <i class="fas fa-download me-2"></i>Download Transcript
                </button>
            </div>
        </div>
        
        <!-- Stats Row -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card stat-card bg-gradient-primary h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Current SGPA</p>
                            <h2 class="fw-bold mb-0 display-5"><?php echo number_format($sgpa, 2); ?></h2>
                        </div>
                        <i class="fas fa-chart-line fa-3x text-white opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-gradient-success h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Current Semester</p>
                            <h2 class="fw-bold mb-0 display-5">Sem <?php echo $student['current_semester']; ?></h2>
                        </div>
                        <i class="fas fa-calendar-alt fa-3x text-white opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-gradient-warning h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Roll Number</p>
                            <h2 class="fw-bold mb-0 display-5"><?php echo htmlspecialchars($student['roll_number']); ?></h2>
                        </div>
                        <i class="fas fa-id-card fa-3x text-white opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-3">Student Portal Actions</h5>
        <!-- Fixed 4-Column Grid -->
        <div class="row g-4 mb-5">
            
            <div class="col-md-6 col-lg-3">
                <a href="register_courses.php" class="text-decoration-none">
                    <div class="card card-modern h-100 border-0 bg-white shadow-sm action-card transition-all" style="transition: transform 0.2s;">
                        <div class="card-body p-4 text-center">
                            <div class="bg-indigo bg-opacity-10 text-indigo rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: #e0e7ff; color: #4f46e5;">
                                <i class="fas fa-laptop-code fa-2x"></i>
                            </div>
                            <h6 class="fw-bold text-dark">Course Registration</h6>
                            <p class="text-muted small mb-0">Select classes for Sem <?php echo $student['current_semester']; ?></p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="my_notices.php" class="text-decoration-none">
                    <div class="card card-modern h-100 border-0 bg-white shadow-sm action-card transition-all" style="transition: transform 0.2s;">
                        <div class="card-body p-4 text-center">
                            <div class="bg-info bg-opacity-10 text-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; color: #0ea5e9 !important;">
                                <i class="fas fa-bullhorn fa-2x"></i>
                            </div>
                            <h6 class="fw-bold text-dark">Notice Board</h6>
                            <p class="text-muted small mb-0">View campus announcements</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- New Integrated Teaching Log Card -->
            <div class="col-md-6 col-lg-3">
                <a href="student_teaching_logs.php" class="text-decoration-none">
                    <div class="card card-modern h-100 border-0 bg-white shadow-sm action-card transition-all" style="transition: transform 0.2s;">
                        <div class="card-body p-4 text-center">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; color: #d97706 !important;">
                                <i class="fas fa-chalkboard fa-2x"></i>
                            </div>
                            <h6 class="fw-bold text-dark">Class Topics</h6>
                            <p class="text-muted small mb-0">View topics taught in your courses</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="my_attendance.php" class="text-decoration-none">
                    <div class="card card-modern h-100 border-0 bg-white shadow-sm action-card transition-all" style="transition: transform 0.2s;">
                        <div class="card-body p-4 text-center">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: #dcfce7; color: #15803d;">
                                <i class="fas fa-calendar-check fa-2x"></i>
                            </div>
                            <h6 class="fw-bold text-dark">My Attendance</h6>
                            <p class="text-muted small mb-0">View your daily presence logs</p>
                        </div>
                    </div>
                </a>
            </div>

        </div>
        
        <h5 class="fw-bold mb-3">Academic Transcript</h5>
        <div class="card card-modern" id="transcriptArea">
            
            <div class="pdf-header p-4 border-bottom text-center mb-2" style="display: none;">
                <h2 class="fw-bold text-dark mb-1">Official Academic Transcript</h2>
                <h5 class="text-muted">Student: <?php echo htmlspecialchars($_SESSION['full_name']); ?> | Roll: <?php echo htmlspecialchars($student['roll_number']); ?></h5>
                <p class="mb-0">Semester: <?php echo $student['current_semester']; ?> | Academic Year: <?php echo date('Y'); ?></p>
            </div>
            
            <div class="card-body p-0">
                <div class="p-4 border-bottom border-light d-flex justify-content-between align-items-center bg-white" style="border-radius: 16px 16px 0 0;">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                            <i class="fas fa-file-alt fa-lg"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Grade Details - Semester <?php echo $student['current_semester']; ?></h5>
                    </div>
                    <span class="badge bg-light text-dark border px-3 py-2 fs-6 shadow-sm">SGPA: <?php echo number_format($sgpa, 2); ?></span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Course Info</th>
                                <th class="text-center">Credits</th>
                                <th class="text-center">Internal <br><small class="text-muted">(Max 30)</small></th>
                                <th class="text-center">Practical <br><small class="text-muted">(Max 20)</small></th>
                                <th class="text-center">Final <br><small class="text-muted">(Max 50)</small></th>
                                <th class="text-center">Total <br><small class="text-muted">(100)</small></th>
                                <th class="text-end pe-4">Grade Point</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($courses as $course): 
                                $total = $course['internal'] + $course['practical'] + $course['final_exam'];
                                $percentage = ($total / 100) * 100;
                                $gp = getGradePoint($percentage);
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($course['course_code']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border"><?php echo $course['credits']; ?> Cr</span>
                                </td>
                                <td class="text-center fw-medium"><?php echo $course['internal'] ?: '<span class="text-muted">-</span>'; ?></td>
                                <td class="text-center fw-medium"><?php echo $course['practical'] ?: '<span class="text-muted">-</span>'; ?></td>
                                <td class="text-center fw-medium"><?php echo $course['final_exam'] ?: '<span class="text-muted">-</span>'; ?></td>
                                <td class="text-center">
                                    <span class="fw-bold text-indigo"><?php echo $total; ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="badge <?php echo ($gp >= 8) ? 'bg-success' : (($gp >= 5) ? 'bg-warning' : 'bg-danger'); ?> rounded-pill px-3 py-2 fs-6">
                                        <?php echo number_format($gp, 1); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(count($courses) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="fas fa-folder-open fa-3x opacity-25"></i></div>
                                    <h6 class="fw-bold mb-1">No Grades Available</h6>
                                    <p class="small text-muted mb-0">Your grades for the current semester have not been published yet.</p>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
    function generateTranscript() {
        const element = document.getElementById('transcriptArea');
        const header = element.querySelector('.pdf-header');
        
        header.style.display = 'block';
        element.style.boxShadow = 'none'; 
        element.style.border = '1px solid #e2e8f0'; 
        
        const opt = {
            margin:       [0.5, 0.5, 0.5, 0.5],
            filename:     'Academic_Transcript_<?php echo htmlspecialchars($student['roll_number']); ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
        
        html2pdf().set(opt).from(element).save().then(() => {
            header.style.display = 'none';
            element.style.boxShadow = '';
            element.style.border = 'none';
        });
    }
    </script>
</body>
</html>