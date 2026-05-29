<?php
session_start();
require_once 'conn.php';

$tns = "
(DESCRIPTION =
    (ADDRESS_LIST =
      (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
    )
    (CONNECT_DATA =
      (SERVICE_NAME = xe)
    )
)";


$user_id = trim($_POST['user_id']);
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

if ($password !== $confirm_password) {
    echo "<script>alert('비밀번호가 일치하지 않습니다.'); history.back();</script>";
    exit;
}

$sql_check = "SELECT COUNT(*) AS CNT FROM USERS WHERE USER_ID = :user_id";
$stid = oci_parse($conn, $sql_check);
oci_bind_by_name($stid, ":user_id", $user_id);
oci_execute($stid);
$row = oci_fetch_assoc($stid);

if ($row['CNT'] > 0) {
    echo "<script>alert('이미 존재하는 사용자 아이디입니다.'); history.back();</script>";
    exit;
}

$sql_check = "SELECT COUNT(*) AS CNT FROM USERS WHERE PHONE = :phone";
$stid = oci_parse($conn, $sql_check);
oci_bind_by_name($stid, ":phone", $phone);
oci_execute($stid);
$row = oci_fetch_assoc($stid);

if ($row['CNT'] > 0) {
    echo "<script>alert('이미 존재하는 전화번호입니다.'); history.back();</script>";
    exit;
}

$sql_check = "SELECT COUNT(*) AS CNT FROM USERS WHERE EMAIL = :email";
$stid = oci_parse($conn, $sql_check);
oci_bind_by_name($stid, ":email", $email);
oci_execute($stid);
$row = oci_fetch_assoc($stid);

if ($row['CNT'] > 0) {
    echo "<script>alert('이미 존재하는 사용자 이메일입니다.'); history.back();</script>";
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql_insert = "INSERT INTO USERS (USER_ID, NAME, EMAIL, PHONE, PASSWORD)
               VALUES (:user_id, :name, :email, :phone, :password)";
$stid = oci_parse($conn, $sql_insert);
oci_bind_by_name($stid, ":user_id", $user_id);
oci_bind_by_name($stid, ":name", $name);
oci_bind_by_name($stid, ":email", $email);
oci_bind_by_name($stid, ":phone", $phone);
oci_bind_by_name($stid, ":password", $hashed_password);

$result = oci_execute($stid, OCI_COMMIT_ON_SUCCESS);
if ($result) {
    header("Location: main.php?signup=success");
    exit;
} else {
    $e = oci_error($stid);
    echo "오류 발생: " . htmlentities($e['message'], ENT_QUOTES);
}

oci_free_statement($stid);
oci_close($conn);
?>
