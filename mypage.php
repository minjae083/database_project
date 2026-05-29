<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'conn.php';

$user_id = $_SESSION['user_id'];
$rows_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start_row = ($page - 1) * $rows_per_page + 1;
$end_row = $start_row + $rows_per_page - 1;

$count_sql = "SELECT COUNT(DISTINCT reservation_id) AS total FROM Reservations WHERE user_id = :user_id";
$count_stmt = oci_parse($conn, $count_sql);
oci_bind_by_name($count_stmt, ":user_id", $user_id);
oci_execute($count_stmt);
$count_row = oci_fetch_assoc($count_stmt);
$total_rows = $count_row['TOTAL'];
oci_free_statement($count_stmt);

$sql = "
SELECT * FROM (
    SELECT r.reservation_id,
           r.movie_title,
           r.screen_name,
           TO_CHAR(r.start_time_snapshot, 'YYYY-MM-DD HH24:MI:SS') AS start_time,
           TO_CHAR(r.reservation_time, 'YYYY-MM-DD HH24:MI:SS') AS reservation_time,
           r.cancel_status,
           NVL(LISTAGG(seat.seat_number, ', ') WITHIN GROUP (ORDER BY seat.seat_number), '-') AS seat_numbers,
           ROW_NUMBER() OVER (ORDER BY r.reservation_time DESC) AS rn
    FROM Reservations r
    LEFT JOIN ReservedSeats rs ON r.reservation_id = rs.reservation_id
    LEFT JOIN Seats seat ON rs.seat_id = seat.seat_id
    WHERE r.user_id = :user_id
    GROUP BY r.reservation_id, r.movie_title, r.screen_name, r.start_time_snapshot, r.reservation_time, r.cancel_status
)
WHERE rn BETWEEN :start_row AND :end_row
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":user_id", $user_id);
oci_bind_by_name($stmt, ":start_row", $start_row);
oci_bind_by_name($stmt, ":end_row", $end_row);
oci_execute($stmt);

$reservations = [];
while ($row = oci_fetch_assoc($stmt)) {
    $reservations[] = $row;
}
oci_free_statement($stmt);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <title>마이페이지 - 예매 내역</title>
  <style>
    .nav {
      background-color:rgb(71, 71, 71);
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-family: Arial, sans-serif;
      color: white;
    }
    .nav-logo img {
      height: 40px;
    }
    .nav-links a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-size: 16px;
    }
    .nav-links a:hover {
      text-decoration: underline;
    }

    body {
      background-color:#d4d4d4;
      color: white;
      font-family: Arial, sans-serif;
      padding: 40px;
      margin: 0;
    }
    h1 {
      margin-top: 30px;
      margin-bottom: 40px;
    }
    .reservation-box {
      border: 1px solid #aaa;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      background-color:rgb(71, 71, 71);
    }
    .reservation-box p {
      margin: 8px 0;
    }
    .cancelled {
      color: red;
      font-weight: bold;
    }
    form.cancel-form {
      margin-top: 10px;
    }
    form.cancel-form button {
      padding: 8px 12px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
      background-color: #e74c3c;
      color: white;
      font-weight: bold;
    }
    form.cancel-form button:hover {
      background-color: #c0392b;
    }
    .pagination {
      margin-top: 30px;
      text-align: center;
    }
    .pagination a, .pagination strong {
      color: white;
      margin: 0 8px;
      text-decoration: none;
      font-size: 16px;
    }
    .pagination strong {
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="nav">
  <a href="main.php" class="nav-logo">
    <img src="images/logo.png" alt="logo" />
  </a>
  <div class="nav-links">
    <a href="logout.php">로그아웃</a>
  </div>
</div>


  <?php if (isset($_GET['message'])): ?>
  <p class="message" style="background-color:#222; padding:12px; border-radius:5px; color:lightgreen; margin-bottom:20px;">
    <?= htmlspecialchars($_GET['message']) ?>
  </p>
  <?php endif; ?>

  <h1><?= htmlspecialchars($_SESSION['user_id']) ?>님의 예매 내역</h1>

  <?php if (empty($reservations)): ?>
    <p>예매 내역이 없습니다.</p>
  <?php else: ?>
    <?php foreach ($reservations as $row): ?>
      <div class="reservation-box">
        <p><strong>예매 ID:</strong> <?= htmlspecialchars($row['RESERVATION_ID']) ?></p>
        <?php if ($row['CANCEL_STATUS'] === 'Y'): ?>
          <p class="cancelled">[취소된 예매]</p>
        <?php endif; ?>
        <p><strong>영화 제목:</strong> <?= $row['MOVIE_TITLE'] ? htmlspecialchars($row['MOVIE_TITLE']) : '정보 없음' ?></p>
        <p><strong>상영관:</strong> <?= $row['SCREEN_NAME'] ? htmlspecialchars($row['SCREEN_NAME']) : '정보 없음' ?></p>
        <p><strong>상영 시작 시간:</strong> <?= $row['START_TIME'] ? htmlspecialchars($row['START_TIME']) : '정보 없음' ?></p>
        <p><strong>예매 시간:</strong> <?= htmlspecialchars($row['RESERVATION_TIME']) ?></p>
        <p><strong>예매 좌석:</strong> <?= htmlspecialchars($row['SEAT_NUMBERS']) ?></p>

        <?php if ($row['CANCEL_STATUS'] === 'N'): ?>
        <form method="post" action="cancel_reservation.php" class="cancel-form" onsubmit="return confirm('정말로 예매를 취소하시겠습니까?');">
          <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($row['RESERVATION_ID']) ?>">
          <button type="submit">예매 취소</button>
        </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php
    $total_pages = ceil($total_rows / $rows_per_page);
    if ($total_pages > 1):
  ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <?php if ($i == $page): ?>
        <strong>[<?= $i ?>]</strong>
      <?php else: ?>
        <a href="mypage.php?page=<?= $i ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</body>
</html>
