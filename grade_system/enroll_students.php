<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) die("Access denied");

$success = '';
$error = '';

// Handle Enrollment (Now supports Multiple Courses)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enroll'])) {
    $student_id = $_POST['student_id'];
    $course_ids = $_POST['course_ids']; // This is now an Array
    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];
    
    $success_count = 0;
    $skipped_count = 0;

    // Loop through each selected course and enroll the student
    foreach($course_ids as $course_id) {
        // Check if already enrolled
        $check = $pdo->prepare("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND semester=?");
        $check->execute([$student_id, $course_id, $semester]);
        
        if(!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, academic_year, semester) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $course_id, $academic_year, $semester]);
            $success_count++;
        } else {
            $skipped_count++;
        }
    }
    
    if ($success_count > 0) {
        $success = "Successfully enrolled student in $success_count course(s)!";
        if ($skipped_count > 0) $success .= " ($skipped_count skipped because they were already enrolled).";
    } else {
        $error = "Student is already enrolled in the selected course(s) for this semester.";
    }
}

// Handle Unenroll
if (isset($_GET['unenroll'])) {
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ?");
    $stmt->execute([$_GET['unenroll']]);
    $success = "Enrollment record successfully removed.";
}

// Get lists
$students = $pdo->query("SELECT s.id, s.roll_number, u.full_name FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.roll_number")->fetchAll();
$courses = $pdo->query("SELECT id, course_code, course_name, semester FROM courses ORDER BY course_code")->fetchAll();

// Fetch current enrollments with details
$enrollments = $pdo->query("
    SELECT e.id, s.roll_number, u.full_name as student_name, c.course_code, c.course_name, e.semester, e.academic_year
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY e.academic_year DESC, e.semester DESC, c.course_code
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enroll Students - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Select2 Searchable Dropdown CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
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
            border-color: #f59e0b; /* Amber */
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
            background-color: #ffffff;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.4rem;
        }

        /* Select2 Custom Overrides to match our SaaS theme */
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.4rem 0.5rem;
            background-color: #f8fafc;
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
            background-color: #ffffff;
        }
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            color: #d97706;
            border-radius: 6px;
        }

        /* Buttons */
        .btn-amber {
            background-color: #f59e0b;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s;
        }
        .btn-amber:hover {
            background-color: #d97706;
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
        }
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .table tbody tr:hover td {
            background-color: #fffbeb;
        }

        /* Action Buttons */
        .action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-delete { background-color: #fef2f2; color: #ef4444; border: 1px solid transparent; }
        .btn-delete:hover { background-color: #ef4444; color: white; }

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

    <!-- Solid Dark Navbar -->
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
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=f59e0b&color=fff" class="rounded-circle me-2" width="28" height="28">
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
        
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-decoration-none text-warning fw-semibold" style="color: #d97706 !important;">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Enroll Students</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">Course Enrollments</h3>
                <p class="text-muted mb-0">Assign students to classes and manage active semester rosters.</p>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if($success) echo "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-check-circle me-2'></i>$success</div>"; ?>
        <?php if($error) echo "<div class='alert alert-danger border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-exclamation-triangle me-2'></i>$error</div>"; ?>
        
        <div class="row g-4">
            
            <!-- Add Enrollment Form -->
            <div class="col-xl-4 col-lg-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-warning bg-opacity-10 text-warning rounded p-2 me-3" style="background-color: #fffbeb; color: #f59e0b;">
                                <i class="fas fa-user-plus fa-lg"></i>
                            </div>
                            <h5 class="fw-bold mb-0">New Enrollment</h5>
                        </div>
                        
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Search & Select Student</label>
                                    <select name="student_id" class="form-select searchable-select" required data-placeholder="-- Type a name or roll number --">
                                        <option value=""></option>
                                        <?php foreach($students as $s): ?>
                                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['roll_number'] . ' - ' . $s['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Select Course(s) <span class="text-muted fw-normal small">- You can select multiple</span></label>
                                    <select name="course_ids[]" class="form-select searchable-select-multiple" multiple="multiple" required data-placeholder="-- Click to select courses --">
                                        <?php foreach($courses as $c): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name'] . ' (Sem ' . $c['semester'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Semester</label>
                                    <input type="number" name="semester" class="form-control" placeholder="e.g., 1" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Academic Year</label>
                                    <input type="text" name="academic_year" class="form-control" placeholder="e.g., 2024-25" value="<?php echo date('Y') . '-' . (date('Y')+1); ?>" required>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" name="enroll" class="btn btn-amber w-100">
                                        <i class="fas fa-check-circle me-2"></i>Enroll Student
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Current Enrollments List -->
            <div class="col-xl-8 col-lg-7">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Current Enrollment Roster</h5>
                            <span class="badge bg-light text-dark border"><?php echo count($enrollments); ?> Records</span>
                        </div>
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Student</th>
                                        <th>Course Enrolled</th>
                                        <th>Term</th>
                                        <th class="text-end pe-4">Unenroll</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($enrollments as $e): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($e['student_name']); ?>&background=f1f5f9&color=475569" class="rounded-circle me-3" width="36" height="36">
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($e['student_name']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($e['roll_number']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($e['course_code']); ?></div>
                                            <div class="text-muted small text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($e['course_name']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">
                                                Sem <?php echo $e['semester']; ?>
                                            </span>
                                            <div class="text-muted small mt-1"><?php echo htmlspecialchars($e['academic_year']); ?></div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="?unenroll=<?php echo $e['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($e['student_name']); ?> from <?php echo htmlspecialchars($e['course_code']); ?>?')" title="Remove Enrollment">
                                                <i class="fas fa-times fa-sm"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if(count($enrollments)==0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted mb-2"><i class="fas fa-clipboard-list fa-3x opacity-50"></i></div>
                                            <h6 class="fw-bold mb-1">No Active Enrollments</h6>
                                            <p class="small text-muted mb-0">Use the form to assign a student to a course.</p>
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
    </div>
    
    <!-- Scripts for Bootstrap, jQuery, and Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize single searchable dropdown
            $('.searchable-select').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
            
            // Initialize multiple searchable dropdown
            $('.searchable-select-multiple').select2({
                theme: 'bootstrap-5',
                width: '100%',
                closeOnSelect: false // Keeps the dropdown open while selecting multiple
            });
        });
    </script>
</body>
</html>