<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isStudent()) die("Access denied");

$user_id = $_SESSION['user_id'];

// Get student's enrolled courses
$stmt = $pdo->prepare("
    SELECT DISTINCT c.id, c.course_code, c.course_name 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    JOIN students s ON e.student_id = s.id 
    WHERE s.user_id = ?
    ORDER BY c.course_code
");
$stmt->execute([$user_id]);
$my_courses = $stmt->fetchAll();

$selected_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Fetch teaching logs for the selected course (or all if 0)
$logs = [];
if ($selected_course > 0) {
    $sql = "SELECT l.*, c.course_code, c.course_name, u.full_name as teacher_name 
            FROM teaching_log l
            JOIN courses c ON l.course_id = c.id
            JOIN users u ON l.faculty_id = u.id
            WHERE l.course_id = ?
            ORDER BY l.taught_date DESC, l.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selected_course]);
    $logs = $stmt->fetchAll();
} else if (count($my_courses) > 0) {
    // Show logs for all enrolled courses
    $course_ids = array_column($my_courses, 'id');
    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    $sql = "SELECT l.*, c.course_code, c.course_name, u.full_name as teacher_name 
            FROM teaching_log l
            JOIN courses c ON l.course_id = c.id
            JOIN users u ON l.faculty_id = u.id
            WHERE l.course_id IN ($placeholders)
            ORDER BY l.taught_date DESC, l.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($course_ids);
    $logs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Topics - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-modern { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); background: #ffffff; }
        
        /* Form Inputs */
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; background-color: #f8fafc; }
        .form-control:focus, .form-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); background-color: #ffffff; }
        
        /* Buttons */
        .btn-indigo { background-color: #4f46e5; color: white; border-radius: 8px; font-weight: 600; padding: 0.6rem 1.2rem; transition: all 0.2s; }
        .btn-indigo:hover { background-color: #4338ca; color: white; transform: translateY(-1px); }
        .btn-light-indigo { background-color: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; border-radius: 8px; font-weight: 600; padding: 0.6rem 1.2rem; }
        .btn-light-indigo:hover { background-color: #c7d2fe; color: #312e81; }

        /* Timeline Log Styling */
        .log-item {
            border-left: 3px solid #e0e7ff;
            padding-left: 1.5rem;
            position: relative;
            margin-bottom: 2.5rem;
        }
        .log-item:last-child { margin-bottom: 0; }
        .log-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background-color: #4f46e5;
            border: 2px solid white;
        }
        .log-date { font-size: 0.75rem; font-weight: 800; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem; }
        .log-title { font-weight: 700; color: #0f172a; margin-bottom: 0.25rem; font-size: 1.1rem; }
        .log-course { font-size: 0.85rem; color: #64748b; font-weight: 600; margin-bottom: 0.75rem; }
        .log-desc { color: #334155; font-size: 0.95rem; line-height: 1.6; background-color: #f8fafc; padding: 1.25rem; border-radius: 12px; border: 1px solid #f1f5f9; }
        
        /* Custom Scrollbar */
        .scroll-container::-webkit-scrollbar { width: 6px; }
        .scroll-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
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
                <button class="btn btn-dark border-secondary dropdown-toggle d-flex align-items-center rounded-pill px-3" type="button" data-bs-toggle="dropdown">
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

    <div class="container-fluid px-4 pb-5" style="max-width: 1000px;">
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="student_dashboard.php" class="text-decoration-none fw-semibold" style="color: #4f46e5;">Dashboard</a></li>
                <li class="breadcrumb-item active">Class Topics</li>
            </ol>
        </nav>

        <div class="d-flex align-items-center mb-4">
            <div class="bg-indigo bg-opacity-10 rounded p-3 me-3" style="color: #4f46e5; background-color: #e0e7ff;">
                <i class="fas fa-book-reader fa-2x"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-1">What Was Taught</h3>
                <p class="text-muted mb-0">Review daily topics, materials, and notes logged by your professors.</p>
            </div>
        </div>

        <div class="card card-modern border-0">
            <div class="card-body p-0">
                
                <div class="p-4 border-bottom border-light bg-white" style="border-radius: 16px 16px 0 0;">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-8 col-lg-6">
                            <label class="form-label text-muted fw-semibold small">Filter by Enrolled Course</label>
                            <select name="course_id" class="form-select">
                                <option value="0">-- All My Courses --</option>
                                <?php foreach($my_courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($selected_course == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-3 d-flex gap-2">
                            <button type="submit" class="btn btn-indigo flex-grow-1"><i class="fas fa-search me-2"></i>View</button>
                            <?php if($selected_course > 0): ?>
                                <a href="student_teaching_logs.php" class="btn btn-light-indigo" title="Clear Filter"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="p-5 scroll-container" style="max-height: 70vh; overflow-y: auto; background-color: #ffffff; border-radius: 0 0 16px 16px;">
                    <?php if(count($logs) == 0): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tasks fa-3x text-muted opacity-25 mb-3"></i>
                            <h6 class="fw-bold text-secondary">No Topics Logged Yet</h6>
                            <p class="text-muted small">Your professors haven't recorded any class topics for <?php echo $selected_course > 0 ? 'this course' : 'your courses'; ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="ms-2">
                            <?php foreach($logs as $log): ?>
                                <div class="log-item">
                                    <div class="log-date"><?php echo date('l, F j, Y', strtotime($log['taught_date'])); ?></div>
                                    <div class="log-title"><?php echo htmlspecialchars($log['topic_title']); ?></div>
                                    <div class="log-course">
                                        <span class="badge bg-light text-dark border me-2 py-1"><?php echo htmlspecialchars($log['course_code']); ?></span>
                                        <i class="fas fa-chalkboard-teacher text-muted mx-1"></i> <?php echo htmlspecialchars($log['teacher_name']); ?>
                                    </div>
                                    
                                    <?php if(!empty($log['topic_description'])): ?>
                                        <div class="log-desc mt-2 shadow-sm">
                                            <?php echo nl2br(htmlspecialchars($log['topic_description'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>