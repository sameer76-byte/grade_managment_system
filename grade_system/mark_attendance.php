<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) die("Access denied");

$courses = $pdo->query("SELECT id, course_code, course_name, semester FROM courses ORDER BY course_code")->fetchAll();
$selected_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$students_data = [];
$success = '';

// Handle Saving Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $course_id = $_POST['course_id'];
    $date = $_POST['attendance_date'];
    $count = 0;
    
    foreach ($_POST['status'] as $enrollment_id => $status) {
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE enrollment_id = ? AND attendance_date = ?");
        $stmt->execute([$enrollment_id, $date]);
        
        if ($stmt->fetch()) {
            $update = $pdo->prepare("UPDATE attendance SET status = ?, marked_by = ? WHERE enrollment_id = ? AND attendance_date = ?");
            $update->execute([$status, $_SESSION['user_id'], $enrollment_id, $date]);
        } else {
            $insert = $pdo->prepare("INSERT INTO attendance (enrollment_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?)");
            $insert->execute([$enrollment_id, $date, $status, $_SESSION['user_id']]);
        }
        $count++;
    }
    $success = "$count attendance records saved for " . date('M j, Y', strtotime($date));
}

// Load Roster
if ($selected_course > 0) {
    $sql = "SELECT e.id as enrollment_id, s.roll_number, u.full_name, 
            COALESCE(a.status, 'present') as current_status
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN attendance a ON e.id = a.enrollment_id AND a.attendance_date = ?
            WHERE e.course_id = ? ORDER BY s.roll_number";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selected_date, $selected_course]);
    $students_data = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-modern { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); background: #ffffff; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; }
        .form-control:focus, .form-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
        
        /* Modern Radio Buttons for Attendance */
        .btn-check:checked + .btn-outline-success { background-color: #10b981; color: white; border-color: #10b981; }
        .btn-check:checked + .btn-outline-danger { background-color: #ef4444; color: white; border-color: #ef4444; }
        .status-toggle .btn { border-radius: 8px; font-weight: 600; padding: 0.4rem 1.5rem; }
    </style>
</head>
<body>
    <nav class="top-navbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-2 me-3"><i class="fas fa-graduation-cap fs-5"></i></div>
            <h5 class="mb-0 fw-bold">EduGrade Portal</h5>
        </div>
        <a href="faculty_dashboard.php" class="btn btn-outline-light rounded-pill px-4 fw-semibold border-0"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
    </nav>
    
    <div class="container px-4 pb-5">
        <h3 class="fw-bold mb-4">Class Attendance</h3>
        <?php if($success) echo "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-check-circle me-2'></i>$success</div>"; ?>
        
        <div class="card card-modern mb-4">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label text-muted">Select Course</label>
                        <select name="course_id" class="form-select" required>
                            <option value="" disabled <?php echo !$selected_course ? 'selected' : ''; ?>>-- Choose a course --</option>
                            <?php foreach($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($selected_course == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted">Date</label>
                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-dark w-100 py-2" style="border-radius: 8px;">Load Roster</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if($selected_course > 0): ?>
        <div class="card card-modern">
            <div class="card-body p-0">
                <form method="POST">
                    <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                    <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                    
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3 text-muted text-uppercase" style="font-size: 0.8rem;">Student Identity</th>
                                <th class="pe-4 py-3 text-end text-muted text-uppercase" style="font-size: 0.8rem;">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students_data as $student): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['full_name']); ?>&background=f1f5f9" class="rounded-circle me-3" width="40" height="40">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($student['roll_number']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <div class="btn-group status-toggle" role="group">
                                        <input type="radio" class="btn-check" name="status[<?php echo $student['enrollment_id']; ?>]" id="present_<?php echo $student['enrollment_id']; ?>" value="present" <?php echo ($student['current_status'] == 'present') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-success" for="present_<?php echo $student['enrollment_id']; ?>">Present</label>
                                        
                                        <input type="radio" class="btn-check" name="status[<?php echo $student['enrollment_id']; ?>]" id="absent_<?php echo $student['enrollment_id']; ?>" value="absent" <?php echo ($student['current_status'] == 'absent') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-danger" for="absent_<?php echo $student['enrollment_id']; ?>">Absent</label>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if(empty($students_data)): ?>
                        <div class="text-center py-5 text-muted">No students enrolled in this course.</div>
                    <?php else: ?>
                        <div class="p-4 bg-light text-end" style="border-radius: 0 0 16px 16px;">
                            <button type="submit" name="save_attendance" class="btn btn-primary px-5 py-2 fw-bold" style="border-radius: 8px;">Save Attendance</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>