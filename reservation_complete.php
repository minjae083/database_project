<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("접근 권한이 없습니다.");
}

$reservation_id = $_GET['reservation_id'] ?? null;
if (!$reservation_id) {
    die("예매 정보가 없습니다.");
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>예매 완료</title>
<style>
  body {
    background-color: #d4d4d4;
    color: white;
    font-family: sans-serif;
    text-align: center;
    padding: 50px;
  }
  .message-box {
    background-color: rgb(71, 71, 71);
    padding: 30px;
    border-radius: 15px;
    display: inline-block;
  }
  h1 {
    color: #4caf50;
  }
  a {
    color: white;
    text-decoration: none;
    border: 1px solid white;
    padding: 10px 20px;
    border-radius: 10px;
    display: inline-block;
    margin-top: 20px;
  }
  a:hover {
    background-color: white;
    color: black;
  }
</style>
</head>
<body>

<div class="message-box">
  <h1>예매 완료 🎉</h1>
  <p>예매 번호: <strong>#<?=htmlspecialchars($reservation_id)?></strong></p>
  <p>즐거운 영화 관람 되세요!</p>
  <a href="main.php">메인으로 이동</a>
</div>

</body>
</html>
