<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) die("Access denied");

$message = '';
$error = '';

// Fetch all courses for the dropdown
$courses = $pdo->query("SELECT id, course_code, course_name, semester FROM courses ORDER BY course_code")->fetchAll();

$selected_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$selected_type = isset($_GET['type']) ? $_GET['type'] : 'internal';
$students_data = [];

// Handle grade saving
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_grades'])) {
    $assessment_type = $_POST['assessment_type'];
    $max_marks = ($assessment_type == 'internal') ? 30 : (($assessment_type == 'practical') ? 20 : 50);
    $count = 0;
    
    foreach ($_POST['marks'] as $enrollment_id => $marks_obtained) {
        if ($marks_obtained === '' || $marks_obtained < 0) continue;
        
        // Check if grade already exists
        $stmt = $pdo->prepare("SELECT id FROM grades WHERE enrollment_id = ? AND assessment_type = ?");
        $stmt->execute([$enrollment_id, $assessment_type]);
        
        if ($stmt->fetch()) {
            $update = $pdo->prepare("UPDATE grades SET marks_obtained = ?, entered_by = ? WHERE enrollment_id = ? AND assessment_type = ?");
            $update->execute([$marks_obtained, $_SESSION['user_id'], $enrollment_id, $assessment_type]);
        } else {
            $insert = $pdo->prepare("INSERT INTO grades (enrollment_id, assessment_type, marks_obtained, max_marks, entered_by) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$enrollment_id, $assessment_type, $marks_obtained, $max_marks, $_SESSION['user_id']]);
        }
        $count++;
    }
    $message = "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-check-circle me-2'></i>$count grade(s) saved successfully!</div>";
}

// Load students if a course is selected
if ($selected_course > 0) {
    $sql = "SELECT e.id as enrollment_id, s.roll_number, u.full_name, 
            COALESCE(g.marks_obtained, '') as marks
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN grades g ON e.id = g.enrollment_id AND g.assessment_type = ?
            WHERE e.course_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selected_type, $selected_course]);
    $students_data = $stmt->fetchAll();
    
    if (count($students_data) == 0) {
        $error = "No active student enrollments found for this course.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Entry - EduGrade</title>
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
        .card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            background: #ffffff;
            margin-bottom: 2rem;
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
            border-color: #ef4444; /* Red */
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
            background-color: #ffffff;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.4rem;
        }

        /* Buttons */
        .btn-rose {
            background-color: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s;
        }
        .btn-rose:hover {
            background-color: #dc2626;
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
        .table tbody tr:hover td {
            background-color: #fef2f2; /* Light Rose tint on hover */
        }

        /* Inline Input */
        .inline-input {
            width: 120px;
            text-align: center;
            font-weight: 600;
            color: #0f172a;
        }

        /* Custom Scrollbar */
        .table-responsive::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
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
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=ef4444&color=fff" class="rounded-circle me-2" width="28" height="28">
                    <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item text-danger fw-semibold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container px-4 pb-5">
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-decoration-none text-danger fw-semibold">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Grade Entry</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">Batch Grade Entry</h3>
                <p class="text-muted mb-0">Input internal assessments, practical marks, and final exam scores.</p>
            </div>
        </div>
        
        <?php echo $message; ?>
        <?php if($error): ?>
            <div class='alert alert-warning border-0 shadow-sm rounded-3 fw-medium'>
                <i class='fas fa-exclamation-triangle me-2'></i><?php echo $error; ?>
                <a href="enroll_students.php" class="alert-link ms-2">Manage Enrollments →</a>
            </div>
        <?php endif; ?>
        
        <div class="card border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2 me-3" style="background-color: #fef2f2; color: #ef4444;">
                        <i class="fas fa-filter fa-lg"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Step 1: Configuration</h5>
                </div>
                
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Select Course</label>
                        <select name="course_id" class="form-select" required>
                            <option value="" disabled <?php echo !$selected_course ? 'selected' : ''; ?>>-- Choose a course --</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name'] . ' (Sem '.$course['semester'].')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assessment Type</label>
                        <select name="type" class="form-select">
                            <option value="internal" <?php echo ($selected_type == 'internal') ? 'selected' : ''; ?>>Internal Assessment (Max 30)</option>
                            <option value="practical" <?php echo ($selected_type == 'practical') ? 'selected' : ''; ?>>Practical Exam (Max 20)</option>
                            <option value="final" <?php echo ($selected_type == 'final') ? 'selected' : ''; ?>>Final Examination (Max 50)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-dark w-100">Load Roster</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if($selected_course > 0 && !empty($students_data)): ?>
        <div class="card border-0">
            <div class="card-body p-0">
                
                <div class="p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 text-danger rounded p-2 me-3" style="background-color: #fef2f2; color: #ef4444;">
                            <i class="fas fa-marker fa-lg"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Step 2: Enter <?php echo ucfirst($selected_type); ?> Marks</h5>
                    </div>
                    <span class="badge bg-light text-dark border"><?php echo count($students_data); ?> Students</span>
                </div>

                <form method="POST">
                    <input type="hidden" name="assessment_type" value="<?php echo $selected_type; ?>">
                    
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4" style="width: 40%">Student Identity</th>
                                    <th class="text-center">Score Input</th>
                                    <th class="pe-4 text-end">Maximum Possible</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $max = ($selected_type == 'internal') ? 30 : (($selected_type == 'practical') ? 20 : 50);
                                foreach($students_data as $student): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['full_name']); ?>&background=f1f5f9&color=475569" class="rounded-circle me-3" width="36" height="36">
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                                <div class="text-muted small"><?php echo htmlspecialchars($student['roll_number']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center">
                                            <input type="number" step="0.01" name="marks[<?php echo $student['enrollment_id']; ?>]" 
                                                   value="<?php echo htmlspecialchars($student['marks']); ?>" 
                                                   class="form-control inline-input" 
                                                   min="0" max="<?php echo $max; ?>"
                                                   placeholder="--"
                                                   required>
                                        </div>
                                    </td>
                                    <td class="pe-4 text-end text-muted fw-semibold">
                                        / <?php echo $max; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-4 border-top border-light bg-light rounded-bottom text-end">
                        <button type="submit" name="save_grades" class="btn btn-rose px-5">
                            <i class="fas fa-save me-2"></i>Save All Grades
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>