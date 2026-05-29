<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) die("Access denied");

$audit_log = $pdo->query("
    SELECT g.*, u.full_name as entered_by_name, s.roll_number, c.course_code, c.course_name, g.assessment_type
    FROM grades g
    JOIN enrollments e ON g.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON g.entered_by = u.id
    ORDER BY g.updated_at DESC
    LIMIT 100
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Audit Trail - EduGrade</title>
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

        /* Buttons */
        .btn-slate {
            background-color: #64748b;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s;
        }
        .btn-slate:hover {
            background-color: #475569;
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
            background-color: #f8fafc;
        }

        /* Custom Scrollbar for Audit Log */
        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        /* Badges */
        .badge-internal { background-color: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; }
        .badge-practical { background-color: #f0fdf4; color: #10b981; border: 1px solid #bbf7d0; }
        .badge-final { background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
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
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=64748b&color=fff" class="rounded-circle me-2" width="28" height="28">
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
                <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-decoration-none fw-semibold" style="color: #64748b;">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Grade Audit Trail</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">System Audit Log</h3>
                <p class="text-muted mb-0">Track recent grade entries, modifications, and system updates.</p>
            </div>
            <div>
                <span class="badge bg-white text-secondary border px-3 py-2 shadow-sm rounded-pill">
                    <i class="fas fa-clock me-1"></i> Showing last 100 entries
                </span>
            </div>
        </div>

        <div class="card h-100 border-0">
            <div class="card-body p-0">
                <div class="p-4 border-bottom border-light d-flex align-items-center">
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded p-2 me-3" style="background-color: #f1f5f9; color: #64748b;">
                        <i class="fas fa-history fa-lg"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Recent Activity</h5>
                </div>
                
                <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Timestamp</th>
                                <th>Student Identity</th>
                                <th>Course Data</th>
                                <th>Assessment</th>
                                <th class="text-center">Score Logged</th>
                                <th class="pe-4 text-end">Authorized By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($audit_log as $log): 
                                // Format timestamp for better UX
                                $time_stamp = date("M j, Y", strtotime($log['updated_at']));
                                $time_hour = date("g:i A", strtotime($log['updated_at']));
                                
                                // Determine Badge Style
                                $badge_class = 'badge-internal';
                                if($log['assessment_type'] == 'practical') $badge_class = 'badge-practical';
                                if($log['assessment_type'] == 'final') $badge_class = 'badge-final';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?php echo $time_stamp; ?></div>
                                    <div class="text-muted small"><?php echo $time_hour; ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($log['roll_number']); ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($log['course_code']); ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($log['course_name']); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?> rounded-pill px-3 py-1 fw-semibold">
                                        <?php echo ucfirst($log['assessment_type']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-dark fs-6"><?php echo $log['marks_obtained']; ?></span>
                                    <span class="text-muted small fw-medium">/ <?php echo $log['max_marks']; ?></span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div class="text-end me-2">
                                            <div class="fw-medium text-dark small"><?php echo htmlspecialchars($log['entered_by_name']); ?></div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Faculty / Admin</div>
                                        </div>
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($log['entered_by_name']); ?>&background=f1f5f9&color=475569" class="rounded-circle" width="28" height="28">
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(count($audit_log)==0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="fas fa-clipboard-list fa-3x opacity-25"></i></div>
                                    <h6 class="fw-bold mb-1">Log is Empty</h6>
                                    <p class="small text-muted mb-0">No grade entries or modifications have been recorded yet.</p>
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
</body>
</html>