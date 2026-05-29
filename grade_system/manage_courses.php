<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) die("Access denied");

// Add Course
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
    $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, credits, department, semester) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['course_code'], $_POST['course_name'], $_POST['credits'], $_POST['department'], $_POST['semester']]);
    $success = "Course added successfully!";
}

// Edit Course
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_course'])) {
    $stmt = $pdo->prepare("UPDATE courses SET course_code=?, course_name=?, credits=?, department=?, semester=? WHERE id=?");
    $stmt->execute([$_POST['course_code'], $_POST['course_name'], $_POST['credits'], $_POST['department'], $_POST['semester'], $_POST['course_id']]);
    $success = "Course updated successfully!";
}

// Delete Course
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = "Course deleted successfully!";
}

$courses = $pdo->query("SELECT * FROM courses ORDER BY course_code")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses - EduGrade</title>
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
        }

        /* Form Inputs */
        .form-control {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
            background-color: #f8fafc;
        }
        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            background-color: #ffffff;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.4rem;
        }

        /* Buttons */
        .btn-emerald {
            background-color: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s;
        }
        .btn-emerald:hover {
            background-color: #059669;
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
        }
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .table tbody tr:hover td {
            background-color: #f8fafc;
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
        .btn-edit { background-color: #eff6ff; color: #3b82f6; border: 1px solid transparent; }
        .btn-edit:hover { background-color: #3b82f6; color: white; }
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
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=10b981&color=fff" class="rounded-circle me-2" width="28" height="28">
                    <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item text-danger fw-semibold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-decoration-none text-success fw-semibold">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Courses</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">Course Directory</h3>
                <p class="text-muted mb-0">Create and manage curriculum, credits, and active course offerings.</p>
            </div>
        </div>
        
        <?php if(isset($success)) echo "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-check-circle me-2'></i>$success</div>"; ?>
        
        <div class="row g-4">
            
            <div class="col-xl-4 col-lg-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-success bg-opacity-10 text-success rounded p-2 me-3" style="background-color: #f0fdf4; color: #10b981;">
                                <i class="fas fa-book-open fa-lg"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Create Course</h5>
                        </div>
                        
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-sm-12">
                                    <label class="form-label">Course Code</label>
                                    <input type="text" name="course_code" class="form-control" placeholder="e.g., CS-301" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Course Name</label>
                                    <input type="text" name="course_name" class="form-control" placeholder="e.g., Data Structures" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Department</label>
                                    <input type="text" name="department" class="form-control" placeholder="e.g., Computer Science">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Credits</label>
                                    <input type="number" name="credits" class="form-control" placeholder="3" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Semester Offered</label>
                                    <input type="number" name="semester" class="form-control" placeholder="1" required>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" name="add_course" class="btn btn-emerald w-100">
                                        <i class="fas fa-plus me-2"></i>Add Course
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-8 col-lg-7">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Active Curriculum</h5>
                            <span class="badge bg-light text-dark border"><?php echo count($courses); ?> Total</span>
                        </div>
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Course Info</th>
                                        <th>Credits</th>
                                        <th>Semester</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($courses as $c): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($c['course_code']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($c['course_name']); ?></div>
                                            <?php if($c['department']): ?>
                                                <span class="badge bg-light text-secondary border mt-1" style="font-size: 0.7rem;"><?php echo htmlspecialchars($c['department']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success bg-success bg-opacity-10 px-2 py-1 rounded">
                                                <?php echo $c['credits']; ?> Cr
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">Semester <?php echo $c['semester']; ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="action-btn btn-edit me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $c['id']; ?>" title="Edit Course">
                                                <i class="fas fa-pen fa-sm"></i>
                                            </button>
                                            <a href="?delete=<?php echo $c['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($c['course_code']); ?>? All student enrollments for this course will be lost.')" title="Delete Course">
                                                <i class="fas fa-trash fa-sm"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    
                                    <div class="modal fade" id="editModal<?php echo $c['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                                                <form method="POST">
                                                    <div class="modal-header border-0 pb-0 pt-4 px-4">
                                                        <h5 class="modal-title fw-bold">Edit Course Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body px-4 py-4">
                                                        <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Course Code</label>
                                                            <input type="text" name="course_code" class="form-control" value="<?php echo htmlspecialchars($c['course_code']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Course Name</label>
                                                            <input type="text" name="course_name" class="form-control" value="<?php echo htmlspecialchars($c['course_name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Department</label>
                                                            <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($c['department']); ?>">
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Credits</label>
                                                                <input type="number" name="credits" class="form-control" value="<?php echo $c['credits']; ?>" required>
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Semester</label>
                                                                <input type="number" name="semester" class="form-control" value="<?php echo $c['semester']; ?>" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="edit_course" class="btn btn-emerald px-4">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if(count($courses)==0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted mb-2"><i class="fas fa-book fa-3x opacity-50"></i></div>
                                            <h6 class="fw-bold mb-1">No Courses Found</h6>
                                            <p class="small text-muted mb-0">Use the form to add a new course.</p>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>