<?php
require_once 'config.php';
redirectIfNotLoggedIn();
if ($_SESSION['role'] != 'admin') die("Access denied");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'], $_POST['new_password'])) {
    $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $_POST['user_id']]);
    header("Location: all_users.php?msg=password_reset");
    exit();
} else {
    header("Location: admin_dashboard.php");
}
?>