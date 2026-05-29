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
$academic_year = date('Y') . '-' . (date('Y')+1);

$success = '';
$error = '';

// Handle Enrollment (Add Course)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enroll'])) {
    $course_id = $_POST['course_id'];
    
    // Double check they aren't already enrolled
    $check = $pdo->prepare("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND semester=?");
    $check->execute([$student['id'], $course_id, $student['current_semester']]);
    
    if(!$check->fetch()) {
        $ins = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, academic_year, semester) VALUES (?, ?, ?, ?)");
        $ins->execute([$student['id'], $course_id, $academic_year, $student['current_semester']]);
        $success = "Successfully registered for the course!";
    } else {
        $error = "You are already enrolled in this course.";
    }
}

// Handle Unenroll (Drop Course)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unenroll'])) {
    $enrollment_id = $_POST['enrollment_id'];
    
    // Ensure the student owns this enrollment before deleting
    $del = $pdo->prepare("DELETE FROM enrollments WHERE id=? AND student_id=?");
    $del->execute([$enrollment_id, $student['id']]);
    $success = "Course dropped successfully.";
}

// Fetch Currently Enrolled Courses
$enrolled_stmt = $pdo->prepare("SELECT e.id as enrollment_id, c.* FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.student_id = ? AND e.semester = ?");
$enrolled_stmt->execute([$student['id'], $student['current_semester']]);
$my_courses = $enrolled_stmt->fetchAll();
$enrolled_course_ids = array_column($my_courses, 'id'); // Get just the course IDs to filter available list

// Fetch Available Courses (Matching student's semester, excluding already enrolled)
$avail_stmt = $pdo->prepare("SELECT * FROM courses WHERE semester = ? ORDER BY department, course_code");
$avail_stmt->execute([$student['current_semester']]);
$all_avail = $avail_stmt->fetchAll();

$available_courses = array_filter($all_avail, function($c) use ($enrolled_course_ids) {
    return !in_array($c['id'], $enrolled_course_ids);
});

// Calculate total enrolled credits
$total_credits = array_sum(array_column($my_courses, 'credits'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Registration - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f4f6; color: #1f2937; overflow-x: hidden; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-modern { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); background: #ffffff; }
        
        .table { margin-bottom: 0; border-collapse: separate; border-spacing: 0; }
        .table thead th { background-color: #f8fafc; color: #64748b; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #e2e8f0; padding: 1rem; position: sticky; top: 0; }
        .table tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .table tbody tr:hover td { background-color: #f8fafc; }
        
        /* Action Buttons */
        .btn-add { background-color: #eff6ff; color: #3b82f6; border: 1px solid transparent; border-radius: 8px; font-weight: 600; padding: 0.4rem 1rem; transition: all 0.2s; }
        .btn-add:hover { background-color: #3b82f6; color: white; }
        .btn-drop { background-color: #fef2f2; color: #ef4444; border: 1px solid transparent; border-radius: 8px; font-weight: 600; padding: 0.4rem 1rem; transition: all 0.2s; }
        .btn-drop:hover { background-color: #ef4444; color: white; }
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
                    <li><a class="dropdown-item fw-semibold" href="student_dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                    <li><a class="dropdown-item text-danger fw-semibold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid px-4 py-2 pb-5">
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="student_dashboard.php" class="text-decoration-none fw-semibold" style="color: #4f46e5;">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Course Registration</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="fw-bold mb-1">Semester Registration</h2>
                <p class="text-muted mb-0">Select your courses for Semester <?php echo $student['current_semester']; ?> (Academic Year <?php echo $academic_year; ?>).</p>
            </div>
            <div class="bg-white px-4 py-2 rounded-pill shadow-sm border">
                <span class="fw-bold text-dark">Total Credits: </span>
                <span class="fw-bold text-primary fs-5 ms-1"><?php echo $total_credits; ?></span>
            </div>
        </div>
        
        <?php if($success) echo "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-check-circle me-2'></i>$success</div>"; ?>
        <?php if($error) echo "<div class='alert alert-danger border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-exclamation-triangle me-2'></i>$error</div>"; ?>

        <div class="row g-4">
            
            <div class="col-xl-7 col-lg-6">
                <div class="card card-modern h-100">
                    <div class="p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                                <i class="fas fa-list fa-lg"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Course Catalog</h5>
                        </div>
                        <span class="badge bg-light text-dark border"><?php echo count($available_courses); ?> Available</span>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">Course Info</th>
                                    <th>Credits</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($available_courses as $c): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($c['course_code']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($c['course_name']); ?></div>
                                        <?php if($c['department']): ?>
                                            <span class="badge bg-light text-secondary border mt-1" style="font-size: 0.65rem;"><?php echo htmlspecialchars($c['department']); ?> Dept</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-secondary border px-2 py-1"><?php echo $c['credits']; ?> Cr</span></td>
                                    <td class="text-end pe-4">
                                        <form method="POST">
                                            <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" name="enroll" class="btn btn-add"><i class="fas fa-plus me-1"></i> Add</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($available_courses)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="fas fa-check-circle fa-3x mb-2 opacity-25"></i>
                                        <h6>No more courses available</h6>
                                        <p class="small mb-0">You have registered for all available courses this semester.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-5 col-lg-6">
                <div class="card card-modern h-100 border border-primary border-opacity-25" style="box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.1);">
                    <div class="p-4 border-bottom border-light d-flex justify-content-between align-items-center bg-primary bg-opacity-10" style="border-radius: 16px 16px 0 0;">
                        <div class="d-flex align-items-center">
                            <div class="bg-white text-primary rounded p-2 me-3 shadow-sm">
                                <i class="fas fa-calendar-check fa-lg"></i>
                            </div>
                            <h5 class="fw-bold text-primary mb-0">My Schedule</h5>
                        </div>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">Enrolled Course</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($my_courses as $c): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($c['course_code']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($c['course_name']); ?></div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to drop <?php echo htmlspecialchars($c['course_code']); ?>?');">
                                            <input type="hidden" name="enrollment_id" value="<?php echo $c['enrollment_id']; ?>">
                                            <button type="submit" name="unenroll" class="btn btn-drop"><i class="fas fa-minus me-1"></i> Drop</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($my_courses)): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-5 text-muted">
                                        <i class="fas fa-clipboard fa-3x mb-2 opacity-25"></i>
                                        <h6>Your schedule is empty</h6>
                                        <p class="small mb-0">Add courses from the catalog to build your schedule.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>