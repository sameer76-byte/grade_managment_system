<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) die("Access denied");

$success = '';
$error = '';

// Handle New Post
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_announcement'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $target = $_POST['target_role'];
    
    if($title && $message) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, message, target_role, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $message, $target, $_SESSION['user_id']]);
        $success = "Announcement broadcasted successfully!";
    } else {
        $error = "Title and message are required.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ? AND created_by = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    $success = "Announcement removed.";
}

// Fetch Announcements
$announcements = $pdo->query("
    SELECT a.*, u.full_name 
    FROM announcements a 
    JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notice Board - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-modern { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); background: #ffffff; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; background-color: #f8fafc; }
        .form-control:focus, .form-select:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.15); background-color: #ffffff; }
        .btn-cyan { background-color: #0ea5e9; color: white; border-radius: 8px; font-weight: 600; padding: 0.6rem 1.2rem; }
        .btn-cyan:hover { background-color: #0284c7; color: white; }
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
        <h3 class="fw-bold mb-4">Global Notice Board</h3>
        <?php if($success) echo "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium'>$success</div>"; ?>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card card-modern h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-info bg-opacity-10 text-info rounded p-2 me-3" style="color: #0ea5e9 !important;"><i class="fas fa-bullhorn fa-lg"></i></div>
                            <h5 class="fw-bold mb-0">Broadcast Notice</h5>
                        </div>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted">Audience</label>
                                <select name="target_role" class="form-select">
                                    <option value="all">Everyone (Students & Faculty)</option>
                                    <option value="student">Students Only</option>
                                    <option value="faculty">Faculty Only</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g., Midterm Exam Schedule" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted">Message</label>
                                <textarea name="message" class="form-control" rows="4" placeholder="Type your announcement here..." required></textarea>
                            </div>
                            <button type="submit" name="post_announcement" class="btn btn-cyan w-100"><i class="fas fa-paper-plane me-2"></i>Publish Notice</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card card-modern h-100">
                    <div class="card-body p-4 bg-light rounded-3" style="max-height: 70vh; overflow-y: auto;">
                        <?php foreach($announcements as $a): 
                            $badge = $a['target_role'] == 'all' ? 'bg-secondary' : ($a['target_role'] == 'student' ? 'bg-primary' : 'bg-dark');
                        ?>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($a['title']); ?></h5>
                                    <div>
                                        <span class="badge <?php echo $badge; ?> rounded-pill px-3 me-2 text-capitalize"><?php echo $a['target_role']; ?></span>
                                        <?php if($a['created_by'] == $_SESSION['user_id']): ?>
                                            <a href="?delete=<?php echo $a['id']; ?>" class="text-danger" onclick="return confirm('Delete this notice?');"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-secondary mb-3" style="white-space: pre-wrap;"><?php echo htmlspecialchars($a['message']); ?></p>
                                <div class="d-flex align-items-center text-muted small fw-medium">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($a['full_name']); ?>&background=f1f5f9" class="rounded-circle me-2" width="20" height="20">
                                    Posted by <?php echo htmlspecialchars($a['full_name']); ?> on <?php echo date("M j, g:i a", strtotime($a['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($announcements)) echo "<div class='text-center py-5 text-muted'><i class='fas fa-bell-slash fa-3x mb-3 opacity-25'></i><p>No active announcements.</p></div>"; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>