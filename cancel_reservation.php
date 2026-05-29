<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "로그인이 필요합니다.";
    exit;
}

require_once 'conn.php';

$user_id = $_SESSION['user_id'];
$reservation_id = $_POST['reservation_id'] ?? null;

if (!$reservation_id) {
    http_response_code(400);
    echo "예매 ID가 전달되지 않았습니다.";
    exit;
}

$sql1 = "UPDATE Reservations SET cancel_status = 'Y'
         WHERE reservation_id = :res_id AND user_id = :user_id AND cancel_status = 'N'";
$stmt1 = oci_parse($conn, $sql1);
oci_bind_by_name($stmt1, ':res_id', $reservation_id);
oci_bind_by_name($stmt1, ':user_id', $user_id);

$success = oci_execute($stmt1);
if ($success) {
    if (oci_num_rows($stmt1) > 0) {
       
        header("Location: mypage.php?message=" . urlencode("예매가 취소되었습니다."));
        exit;
    } else {
        header("Location: mypage.php?message=" . urlencode("이미 취소되었거나 존재하지 않는 예매입니다."));
        exit;
    }
} else {
    $e = oci_error($stmt1);
    echo "DB 오류: " . $e['message'];
    exit;
}

oci_free_statement($stmt1);
oci_close($conn);
