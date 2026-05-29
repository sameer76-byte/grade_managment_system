<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isStudent()) die("Access denied");

// Fetch Announcements targeted to 'all' or 'student'
$announcements = $pdo->query("
    SELECT a.*, u.full_name 
    FROM announcements a 
    JOIN users u ON a.created_by = u.id 
    WHERE a.target_role IN ('all', 'student')
    ORDER BY a.created_at DESC 
    LIMIT 50
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .top-navbar { background-color: #111827; color: white; padding: 1rem 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-modern { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); background: #ffffff; }
    </style>
</head>
<body>
    <nav class="top-navbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-2 me-3"><i class="fas fa-user-graduate fs-5"></i></div>
            <h5 class="mb-0 fw-bold">EduGrade Student Portal</h5>
        </div>
        <a href="student_dashboard.php" class="btn btn-outline-light rounded-pill px-4 fw-semibold border-0"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
    </nav>
    
    <div class="container pb-5" style="max-width: 800px;">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="student_dashboard.php" class="text-decoration-none fw-semibold" style="color: #0ea5e9;">Dashboard</a></li>
                <li class="breadcrumb-item active">Notice Board</li>
            </ol>
        </nav>

        <div class="d-flex align-items-center mb-4">
            <div class="bg-info bg-opacity-10 text-info rounded p-3 me-3" style="color: #0ea5e9 !important;">
                <i class="fas fa-bullhorn fa-2x"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-1">Campus Notice Board</h3>
                <p class="text-muted mb-0">Important announcements from your faculty and administration.</p>
            </div>
        </div>

        <div class="card card-modern">
            <div class="card-body p-4 bg-light rounded-3">
                <?php foreach($announcements as $a): 
                    $badge = $a['target_role'] == 'all' ? 'bg-secondary' : 'bg-primary';
                ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($a['title']); ?></h5>
                            <span class="badge <?php echo $badge; ?> rounded-pill px-3 text-capitalize"><?php echo $a['target_role']; ?></span>
                        </div>
                        <p class="text-secondary mb-3" style="white-space: pre-wrap;"><?php echo htmlspecialchars($a['message']); ?></p>
                        <div class="d-flex align-items-center text-muted small fw-medium">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($a['full_name']); ?>&background=f1f5f9" class="rounded-circle me-2" width="20" height="20">
                            Posted by <?php echo htmlspecialchars($a['full_name']); ?> • <?php echo date("F j, Y, g:i a", strtotime($a['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($announcements)) echo "<div class='text-center py-5 text-muted'><i class='fas fa-bell-slash fa-3x mb-3 opacity-25'></i><p>No new announcements.</p></div>"; ?>
            </div>
        </div>
    </div>
</body>
</html>