<?php
session_start();

$host = 'localhost';
$dbname = 'grade_management_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isFaculty() {
    return isset($_SESSION['role']) && ($_SESSION['role'] == 'faculty' || $_SESSION['role'] == 'admin');
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'student';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

function getGradePoint($percentage) {
    if ($percentage >= 90) return 10;
    if ($percentage >= 80) return 9;
    if ($percentage >= 70) return 8;
    if ($percentage >= 60) return 7;
    if ($percentage >= 50) return 6;
    if ($percentage >= 40) return 5;
    return 0;
}
?>