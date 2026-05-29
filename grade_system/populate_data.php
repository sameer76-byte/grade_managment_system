<?php
require_once 'config.php';

$dept_map = [
    'Computer Science' => ['CS'],
    'Information Technology' => ['IT'],
    'Electronics & Comm' => ['EC'],
    'Mechanical' => ['ME'],
    'Civil' => ['CE'],
];

$students = $pdo->query("SELECT id, department, current_semester FROM students")->fetchAll();
$courses = $pdo->query("SELECT id, department, semester FROM courses")->fetchAll();

$enroll_count = 0;
$grade_count = 0;

foreach ($students as $s) {
    $dept = $s['department'];
    $match = $dept_map[$dept] ?? [$dept];
    $max_sem = $s['current_semester'];
    
    for ($sem = 1; $sem <= $max_sem; $sem++) {
        $eligible = [];
        foreach ($courses as $c) {
            if ($c['semester'] != $sem) continue;
            if (in_array($c['department'], $match) || $c['department'] == 'Common') {
                $eligible[] = $c['id'];
            }
        }
        if (empty($eligible)) {
            foreach ($courses as $c) {
                if ($c['semester'] == $sem) $eligible[] = $c['id'];
            }
        }
        $selected = array_slice($eligible, 0, 5);
        foreach ($selected as $cid) {
            // avoid duplicates
            $chk = $pdo->prepare("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND semester=?");
            $chk->execute([$s['id'], $cid, $sem]);
            if (!$chk->fetch()) {
                $year = (2023 + floor(($sem-1)/2)) . '-' . (2024 + floor(($sem-1)/2));
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, academic_year, semester) VALUES (?,?,?,?)");
                $stmt->execute([$s['id'], $cid, $year, $sem]);
                $eid = $pdo->lastInsertId();
                $enroll_count++;
                
                $marks = [rand(15,30), rand(5,20), rand(20,50)];
                $types = ['internal','practical','final'];
                $maxs = [30,20,50];
                for ($i=0; $i<3; $i++) {
                    $gstmt = $pdo->prepare("INSERT INTO grades (enrollment_id, assessment_type, marks_obtained, max_marks, entered_by) VALUES (?,?,?,?,?)");
                    $gstmt->execute([$eid, $types[$i], $marks[$i], $maxs[$i], 1]);
                    $grade_count++;
                }
            }
        }
    }
}
echo "Enrollments added: $enroll_count<br>Grades added: $grade_count<br><a href='grade_entry.php'>Go to Grade Entry</a>";
?>