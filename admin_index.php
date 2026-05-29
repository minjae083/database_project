<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php"); 
    exit;
}

oci_close($conn);  
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>관리자 대시보드</title>
</head>
<body>
    <h1>관리자 대시보드</h1>
    <ul>
        <li><a href="add_movie.php">영화 등록</a></li>
        <li><a href="manage_movie.php">영화 수정</a></li>
        <li><a href="delete_movie.php">영화 삭제</a></li>
        <li><a href="manage_screens.php">상영관 관리</a></li>
        <li><a href="manage_screenings.php">상영시간 관리</a></li>
        <li><a href="manage_seats.php">좌석 조회</a></li>
        <li><a href="manage_reservations.php">예매 관리</a></li>
        <li><a href="manage_users.php">유저 관리</a></li>
    </ul>
</body>
</html>
