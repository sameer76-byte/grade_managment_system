<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) die("Access denied");

$user_id = $_SESSION['user_id'];
$message = '';
$success = '';

// Handle adding new log entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_log'])) {
    $course_id = (int)$_POST['course_id'];
    $topic_title = trim($_POST['topic_title']);
    $topic_description = trim($_POST['topic_description']);
    $taught_date = $_POST['taught_date'];
    
    if ($course_id > 0 && !empty($topic_title) && !empty($taught_date)) {
        $stmt = $pdo->prepare("INSERT INTO teaching_log (course_id, faculty_id, topic_title, topic_description, taught_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$course_id, $user_id, $topic_title, $topic_description, $taught_date]);
        $success = "Teaching log added successfully!";
    } else {
        $message = "Please fill all required fields.";
    }
}

// Get courses taught by this faculty (For simplicity, showing all courses)
$courses = $pdo->query("SELECT id, course_code, course_name, semester FROM courses ORDER BY course_code")->fetchAll();

// Get existing logs for this faculty
$logs = $pdo->prepare("SELECT l.*, c.course_code, c.course_name FROM teaching_log l JOIN courses c ON l.course_id = c.id WHERE l.faculty_id = ? ORDER BY l.taught_date DESC, l.created_at DESC");
$logs->execute([$user_id]);
$logs = $logs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Teaching Log - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-modern { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); background: #ffffff; }
        
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; background-color: #f8fafc; }
        .form-control:focus, .form-select:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15); background-color: #ffffff; }
        
        .btn-teal { background-color: #14b8a6; color: white; border-radius: 8px; font-weight: 600; padding: 0.6rem 1.2rem; transition: all 0.2s; }
        .btn-teal:hover { background-color: #0d9488; color: white; transform: translateY(-1px); }

        /* Timeline Log Styling */
        .log-item {
            border-left: 3px solid #ccfbf1;
            padding-left: 1.5rem;
            position: relative;
            margin-bottom: 2rem;
        }
        .log-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background-color: #14b8a6;
            border: 2px solid white;
        }
        .log-date { font-size: 0.75rem; font-weight: 700; color: #14b8a6; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem; }
        .log-title { font-weight: 700; color: #0f172a; margin-bottom: 0.25rem; font-size: 1.1rem; }
        .log-course { font-size: 0.85rem; color: #64748b; font-weight: 600; margin-bottom: 0.75rem; }
        .log-desc { color: #475569; font-size: 0.95rem; line-height: 1.5; background-color: #f8fafc; padding: 1rem; border-radius: 8px; }
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

    <div class="container-fluid px-4 pb-5">
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-decoration-none fw-semibold" style="color: #14b8a6;">Dashboard</a></li>
                <li class="breadcrumb-item active">Teaching Log</li>
            </ol>
        </nav>

        <h3 class="fw-bold mb-4">Daily Teaching Diary</h3>
        
        <?php if($success) echo "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-check-circle me-2'></i>$success</div>"; ?>
        <?php if($message) echo "<div class='alert alert-danger border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-exclamation-triangle me-2'></i>$message</div>"; ?>

        <div class="row g-4">
            <div class="col-xl-4 col-lg-5">
                <div class="card card-modern h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-teal bg-opacity-10 rounded p-2 me-3" style="color: #14b8a6; background-color: #f0fdfa;">
                                <i class="fas fa-pen-nib fa-lg"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Record Today's Topic</h5>
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold small">Select Course</label>
                                <select name="course_id" class="form-select" required>
                                    <option value="" disabled selected>-- Choose Course --</option>
                                    <?php foreach($courses as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold small">Topic Title</label>
                                <input type="text" name="topic_title" class="form-control" placeholder="e.g. Introduction to Arrays" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold small">Date Taught</label>
                                <input type="date" name="taught_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted fw-semibold small">Detailed Notes (Optional)</label>
                                <textarea name="topic_description" class="form-control" rows="4" placeholder="Briefly describe what was covered in class today..."></textarea>
                            </div>
                            <button type="submit" name="add_log" class="btn btn-teal w-100">
                                <i class="fas fa-save me-2"></i>Save to Diary
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-8 col-lg-7">
                <div class="card card-modern h-100 border-0 shadow-sm">
                    <div class="card-body p-5" style="max-height: 75vh; overflow-y: auto;">
                        <h5 class="fw-bold mb-4 border-bottom pb-3"><i class="fas fa-history text-muted me-2"></i> Previous Entries</h5>
                        
                        <?php if(count($logs) == 0): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book-open fa-3x text-muted opacity-25 mb-3"></i>
                                <h6 class="fw-bold text-secondary">Your diary is empty</h6>
                                <p class="text-muted small">Record your first teaching log using the form on the left.</p>
                            </div>
                        <?php else: ?>
                            <div class="ms-2"> <?php foreach($logs as $log): ?>
                                    <div class="log-item">
                                        <div class="log-date"><?php echo date('F j, Y', strtotime($log['taught_date'])); ?></div>
                                        <div class="log-title"><?php echo htmlspecialchars($log['topic_title']); ?></div>
                                        <div class="log-course"><i class="fas fa-book text-muted me-1"></i> <?php echo htmlspecialchars($log['course_code'] . ' - ' . $log['course_name']); ?></div>
                                        
                                        <?php if($log['topic_description']): ?>
                                            <div class="log-desc"><?php echo nl2br(htmlspecialchars($log['topic_description'])); ?></div>
                                        <?php endif; ?>
                                        <div class="text-muted small mt-2 fst-italic">Logged on <?php echo date('M j, Y @ g:i a', strtotime($log['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>