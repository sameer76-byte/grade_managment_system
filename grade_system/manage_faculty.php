<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if ($_SESSION['role'] != 'admin') die("Access denied");

$success = '';
$error = '';

// Add Faculty
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_faculty'])) {
    try {
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, 'faculty')");
        $stmt->execute([$_POST['username'], $hashed, $_POST['full_name'], $_POST['email']]);
        $success = "Faculty member added successfully!";
    } catch(PDOException $e) {
        $error = "Error adding faculty. Username might already exist.";
    }
}

// Delete Faculty
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'faculty'");
    $stmt->execute([$_GET['delete']]);
    $success = "Faculty account permanently removed.";
}

// Edit Faculty
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_faculty'])) {
    $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=? WHERE id=? AND role='faculty'");
    $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['user_id']]);
    if (!empty($_POST['new_password'])) {
        $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $pwd = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $pwd->execute([$hashed, $_POST['user_id']]);
    }
    $success = "Faculty details updated successfully!";
}

$faculty = $pdo->query("SELECT id, username, full_name, email FROM users WHERE role = 'faculty' ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - EduGrade</title>
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
            border-color: #3b82f6; /* Blue */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            background-color: #ffffff;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.4rem;
        }

        /* Buttons */
        .btn-blue {
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s;
        }
        .btn-blue:hover {
            background-color: #2563eb;
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
        .table tbody tr:hover td { background-color: #f8fafc; }

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
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=7c3aed&color=fff" class="rounded-circle me-2" width="28" height="28">
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
                <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-decoration-none fw-semibold" style="color: #3b82f6;">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Faculty</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">Faculty Roster</h3>
                <p class="text-muted mb-0">Onboard new professors and manage existing faculty credentials.</p>
            </div>
        </div>
        
        <?php if($success) echo "<div class='alert alert-success border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-check-circle me-2'></i>$success</div>"; ?>
        <?php if($error) echo "<div class='alert alert-danger border-0 shadow-sm rounded-3 fw-medium'><i class='fas fa-exclamation-triangle me-2'></i>$error</div>"; ?>
        
        <div class="row g-4">
            <div class="col-xl-4 col-lg-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3" style="background-color: #eff6ff; color: #3b82f6;">
                                <i class="fas fa-user-plus fa-lg"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Add Staff Account</h5>
                        </div>
                        
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" placeholder="e.g., Dr. Jane Smith" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" placeholder="jane.smith@university.edu" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" placeholder="janesmith" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" name="add_faculty" class="btn btn-blue w-100">
                                        <i class="fas fa-plus me-2"></i>Create Faculty Record
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
                            <h5 class="fw-bold mb-0">Active Faculty Members</h5>
                            <span class="badge bg-light text-dark border"><?php echo count($faculty); ?> Total</span>
                        </div>
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Faculty Details</th>
                                        <th>Contact / Username</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($faculty as $f): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($f['full_name']); ?>&background=eff6ff&color=1e3a8a" class="rounded-circle me-3" width="40" height="40">
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($f['full_name']); ?></div>
                                                    <span class="badge bg-light text-secondary border mt-1" style="font-size: 0.65rem;">FACULTY</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-dark small fw-medium mb-1"><i class="fas fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($f['email']); ?></div>
                                            <div class="text-muted small"><i class="fas fa-at text-muted me-1"></i> <?php echo htmlspecialchars($f['username']); ?></div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="action-btn btn-edit me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $f['id']; ?>" title="Edit">
                                                <i class="fas fa-pen fa-sm"></i>
                                            </button>
                                            <a href="?delete=<?php echo $f['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($f['full_name']); ?>? This cannot be undone.')" title="Delete">
                                                <i class="fas fa-trash fa-sm"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    
                                    <div class="modal fade" id="editModal<?php echo $f['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                                                <form method="POST">
                                                    <div class="modal-header border-0 pb-0 pt-4 px-4">
                                                        <h5 class="modal-title fw-bold">Edit Faculty Record</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body px-4 py-4">
                                                        <input type="hidden" name="user_id" value="<?php echo $f['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Full Name</label>
                                                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($f['full_name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Email Address</label>
                                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($f['email']); ?>" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">New Password <span class="text-muted fw-normal">(Leave blank to keep current)</span></label>
                                                            <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="edit_faculty" class="btn btn-blue px-4">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if(count($faculty)==0): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5">
                                            <div class="text-muted mb-2"><i class="fas fa-users-slash fa-3x opacity-50"></i></div>
                                            <h6 class="fw-bold mb-1">No Faculty Found</h6>
                                            <p class="small text-muted mb-0">Use the form to onboard a new staff member.</p>
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