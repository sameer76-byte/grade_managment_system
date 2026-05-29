<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) die("Access denied");

$selected_course = isset($_GET['course_id']) ? $_GET['course_id'] : '';
$courses = $pdo->query("SELECT id, course_code, course_name, semester FROM courses")->fetchAll();
$chart_data = ['labels' => [], 'averages' => []];

if ($selected_course) {
    $sql = "SELECT u.full_name, 
            SUM(CASE WHEN g.assessment_type='internal' THEN g.marks_obtained ELSE 0 END) as internal,
            SUM(CASE WHEN g.assessment_type='practical' THEN g.marks_obtained ELSE 0 END) as practical,
            SUM(CASE WHEN g.assessment_type='final' THEN g.marks_obtained ELSE 0 END) as final_exam
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN grades g ON e.id = g.enrollment_id
            WHERE e.course_id = ?
            GROUP BY e.student_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selected_course]);
    $results = $stmt->fetchAll();
    
    $total_percentages = [];
    foreach($results as $r) {
        $total = $r['internal'] + $r['practical'] + $r['final_exam'];
        $total_percentages[] = $total;
    }
    
    $pass_count = 0;
    foreach($total_percentages as $p) {
        if($p >= 40) $pass_count++;
    }
    $pass_percent = count($total_percentages) > 0 ? ($pass_count / count($total_percentages)) * 100 : 0;
    $avg_score = count($total_percentages) > 0 ? array_sum($total_percentages) / count($total_percentages) : 0;
    $chart_data = ['avg' => round($avg_score,2), 'pass_rate' => round($pass_percent,2)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Analytics - EduGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            border-color: #8b5cf6; /* Violet */
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
            background-color: #ffffff;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.4rem;
        }

        /* Buttons */
        .btn-violet {
            background-color: #8b5cf6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s;
        }
        .btn-violet:hover {
            background-color: #7c3aed;
            color: white;
            transform: translateY(-1px);
        }

        /* KPI Cards */
        .kpi-card {
            padding: 1.5rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .kpi-violet { background-color: #faf5ff; color: #581c87; border: 1px solid #f3e8ff; }
        .kpi-emerald { background-color: #f0fdf4; color: #14532d; border: 1px solid #dcfce7; }
        
        .kpi-icon {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .kpi-icon-violet { background-color: #8b5cf6; color: white; }
        .kpi-icon-emerald { background-color: #10b981; color: white; }

        /* Progress Bar Override */
        .progress {
            height: 10px;
            border-radius: 10px;
            background-color: #e2e8f0;
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
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=8b5cf6&color=fff" class="rounded-circle me-2" width="28" height="28">
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
                <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-decoration-none fw-semibold" style="color: #7c3aed;">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Class Analytics</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">Class Performance Analytics</h3>
                <p class="text-muted mb-0">Visualize course averages and student success metrics.</p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-9 col-lg-7">
                        <label class="form-label text-muted">Select a Course to Analyze</label>
                        <select name="course_id" class="form-select" required>
                            <option value="" disabled <?php echo !$selected_course ? 'selected' : ''; ?>>-- Choose a course --</option>
                            <?php foreach($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $selected_course == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name'] . ' (Sem '.$c['semester'].')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-violet w-100">
                            <i class="fas fa-chart-pie me-2"></i>Analyze
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if($selected_course && isset($chart_data['avg'])): ?>
        
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="kpi-card kpi-violet shadow-sm">
                    <div>
                        <p class="mb-1 text-uppercase fw-bold opacity-75" style="letter-spacing: 0.5px; font-size: 0.8rem;">Class Average Score</p>
                        <h2 class="fw-bold mb-0 display-6"><?php echo $chart_data['avg']; ?><span class="fs-4 text-muted">%</span></h2>
                    </div>
                    <div class="kpi-icon kpi-icon-violet shadow-sm">
                        <i class="fas fa-bullseye"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="kpi-card kpi-emerald shadow-sm">
                    <div class="w-100 me-4">
                        <p class="mb-1 text-uppercase fw-bold opacity-75" style="letter-spacing: 0.5px; font-size: 0.8rem;">Overall Pass Rate</p>
                        <h2 class="fw-bold mb-2 display-6"><?php echo $chart_data['pass_rate']; ?><span class="fs-4 text-muted">%</span></h2>
                        <div class="progress bg-white border">
                            <div class="progress-bar bg-success" style="width: <?php echo $chart_data['pass_rate']; ?>%"></div>
                        </div>
                    </div>
                    <div class="kpi-icon kpi-icon-emerald shadow-sm flex-shrink-0">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-light text-secondary rounded p-2 me-3">
                        <i class="fas fa-chart-bar fa-lg"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Performance Visualization</h5>
                </div>
                
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
        
        <script>
            // Setup modern Chart.js graphics
            const ctx = document.getElementById('performanceChart').getContext('2d');
            
            // Gradient for Average Score
            const gradientPurple = ctx.createLinearGradient(0, 0, 0, 400);
            gradientPurple.addColorStop(0, '#8b5cf6'); // Violet
            gradientPurple.addColorStop(1, '#a78bfa'); // Lighter violet

            // Gradient for Pass Rate
            const gradientEmerald = ctx.createLinearGradient(0, 0, 0, 400);
            gradientEmerald.addColorStop(0, '#10b981'); // Emerald
            gradientEmerald.addColorStop(1, '#34d399'); // Lighter emerald

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Average Class Score', 'Overall Pass Rate'],
                    datasets: [{
                        label: 'Percentage (%)',
                        data: [<?php echo $chart_data['avg']; ?>, <?php echo $chart_data['pass_rate']; ?>],
                        backgroundColor: [gradientPurple, gradientEmerald],
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 80 // Thicker, modern bars
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#111827',
                            padding: 12,
                            titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 14 },
                            bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 14, weight: 'bold' },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            max: 100,
                            grid: {
                                color: '#f1f5f9',
                                borderDash: [5, 5] // Dashed background lines
                            },
                            ticks: {
                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                                color: '#64748b'
                            }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: {
                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 13, weight: '600' },
                                color: '#334155'
                            }
                        }
                    }
                }
            });
        </script>
        <?php endif; ?>
    </div>
</body>
</html>