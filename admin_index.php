<?php
session_start();

// --- 디버깅 코드 추가 ---
echo "현재 로그인한 ID: " . $_SESSION['user_id'] . "<br>";
echo "현재 관리자 권한 값(is_admin): " . $_SESSION['is_admin'] . "<br>";
// ----------------------

require_once 'conn.php';

// 여기서 1이 아닌지 체크해서 튕겨나가는 중입니다.
if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    echo "관리자 권한이 없어서 튕깁니다."; // 튕기기 전에 왜 튕기는지 글씨를 띄워줌
    // header("Location: main.php"); // 잠시 주석 처리하세요 (// 추가)
    exit;
}

oci_close($conn);  
?><!DOCTYPE html>
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
