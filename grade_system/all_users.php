<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if ($_SESSION['role'] != 'admin') die("Access denied");

$success = '';
$error = '';

// Process the password reset if it was submitted back to this same page
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    if (!empty($_POST['new_password']) && !empty($_POST['user_id'])) {
        $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $pwd = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $pwd->execute([$hashed, $_POST['user_id']]);
        $success = "Password successfully reset!";
    } else {
        $error = "Failed to reset password. Invalid input.";
    }
}

$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$stmt = $pdo->prepare("SELECT id, username, full_name, email, role FROM users WHERE role != 'admin' AND (username LIKE ? OR full_name LIKE ?) ORDER BY role, full_name");
$stmt->execute([$search, $search]);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users - EduGrade</title>
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
            border-color: #10b981; /* Emerald */
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            background-color: #ffffff;
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
        
        .btn-warning-soft {
            background-color: #fffbeb;
            color: #d97706;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.4rem 1rem;
            transition: all 0.2s;
        }
        .btn-warning-soft:hover {
            background-color: #fef3c7;
            color: #b45309;
            border-color: #fde68a;
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
        .table tbody tr:hover td { background-color: #f0fdf4; } /* Very light emerald hover */

        /* Badges */
        .badge-faculty { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-student { background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

        /* Custom Scrollbar for Table */
        .table-responsive::-webkit-scrollbar { width: 6px; height: 6px; }
        .table-responsive::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <nav class="top-navbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-shield-alt fs-5"></i>
            </div>
            <h5 class="mb-0 fw-bold">EduGrade Admin Panel</h5>
        </div>
        <div>
            <div class="dropdown">
                <button class="btn btn-dark border-secondary dropdown-toggle d-flex align-items-center rounded-pill px-3" type="button" id="userMenu" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=10b981&color=fff" class="rounded-circle me-2" width="28" height="28">
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
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-decoration-none fw-semibold" style="color: #10b981;">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">All Users</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">Global User Directory</h3>
                <p class="text-muted mb-0">Search all active student and faculty accounts across the system.</p>
            </div>
        </div>
        
        <?php if($success) echo "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-check-circle me-2'></i>$success</div>"; ?>
        <?php if($error) echo "<div class='alert alert-danger border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-exclamation-triangle me-2'></i>$error</div>"; ?>

        <div class="card h-100 border-0">
            <div class="card-body p-0">
                
                <div class="p-4 border-bottom border-light bg-white" style="border-radius: 16px 16px 0 0;">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-md-5 col-lg-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by name or username..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-emerald w-100">Filter</button>
                        </div>
                        <div class="col-md-5 col-lg-6 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-light text-dark border px-3 py-2 shadow-sm fs-6"><?php echo count($users); ?> Users Found</span>
                        </div>
                    </form>
                </div>
                
                <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">User Identity</th>
                                <th>Role</th>
                                <th>Contact Information</th>
                                <th class="text-end pe-4">Security Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): 
                                $badge_class = ($u['role'] == 'faculty') ? 'badge-faculty' : 'badge-student';
                                $avatar_color = ($u['role'] == 'faculty') ? '1e3a8a' : '15803d';
                                $avatar_bg = ($u['role'] == 'faculty') ? 'eff6ff' : 'f0fdf4';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['full_name']); ?>&background=<?php echo $avatar_bg; ?>&color=<?php echo $avatar_color; ?>" class="rounded-circle me-3" width="40" height="40">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                            <div class="text-muted small"><i class="fas fa-at fa-sm opacity-50 me-1"></i><?php echo htmlspecialchars($u['username']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?> rounded-pill px-3 py-1 fw-bold text-uppercase">
                                        <i class="fas <?php echo ($u['role'] == 'faculty') ? 'fa-chalkboard-teacher' : 'fa-user-graduate'; ?> me-1"></i> <?php echo $u['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-dark small fw-medium">
                                        <?php if($u['email']): ?>
                                            <i class="fas fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($u['email']); ?>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">No email provided</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-warning-soft" data-bs-toggle="modal" data-bs-target="#resetModal<?php echo $u['id']; ?>">
                                        <i class="fas fa-key me-1"></i> Reset Password
                                    </button>
                                </td>
                            </tr>
                            
                            <div class="modal fade" id="resetModal<?php echo $u['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                                        <form method="POST">
                                            <div class="modal-header border-0 pb-0 pt-4 px-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2 me-3" style="color: #d97706 !important;">
                                                        <i class="fas fa-unlock-alt fa-lg"></i>
                                                    </div>
                                                    <h5 class="modal-title fw-bold">Force Password Reset</h5>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body px-4 py-4">
                                                <div class="alert bg-light border text-secondary small mb-4">
                                                    You are about to overwrite the password for <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>. They will be logged out of any active sessions.
                                                </div>
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="reset_password" value="1">
                                                <div class="mb-2">
                                                    <label class="form-label">Assign New Password</label>
                                                    <input type="password" name="new_password" class="form-control" placeholder="••••••••" required minlength="6">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-emerald px-4">Confirm Reset</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if(count($users)==0): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="fas fa-search fa-3x opacity-25"></i></div>
                                    <h6 class="fw-bold mb-1">No Users Found</h6>
                                    <p class="small text-muted mb-0">Try adjusting your search criteria.</p>
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