<?php
session_start();
require_once 'conn.php';

$user_id = trim($_POST['user_id']);
$password = $_POST['password'];

$sql = "SELECT name, password, is_admin FROM Users WHERE user_id = :user_id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':user_id', $user_id);
oci_execute($stid);

$row = oci_fetch_assoc($stid);

if ($row && password_verify($password, $row['PASSWORD'])) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $row['NAME'];
    $_SESSION['is_admin'] = $row['IS_ADMIN'];

    header("Location: main.php");
    exit;
} else {
    echo "<script>alert('아이디 또는 비밀번호가 잘못되었습니다.'); history.back();</script>";
}

oci_free_statement($stid);
oci_close($conn);
