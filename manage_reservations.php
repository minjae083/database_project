<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}

$errorMsg = "";
$successMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];

    $sql = "UPDATE Reservations SET cancel_status = 'Y' WHERE reservation_id = :id AND cancel_status = 'N'";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $reservation_id);

    if (oci_execute($stmt)) {
        if (oci_num_rows($stmt) > 0) {
            $successMsg = "예매가 취소되었습니다.";
        } else {
            $errorMsg = "이미 취소된 예매이거나 존재하지 않습니다.";
        }
    } else {
        $e = oci_error($stmt);
        $errorMsg = $e['message'];
    }
    oci_free_statement($stmt);
}

$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage + 1;
$end = $page * $perPage;

$countSql = "SELECT COUNT(*) AS CNT FROM Reservations";
$countStmt = oci_parse($conn, $countSql);
oci_execute($countStmt);
$totalRow = oci_fetch_assoc($countStmt);
$total = $totalRow ? intval($totalRow['CNT']) : 0;
$totalPages = ceil($total / $perPage);
oci_free_statement($countStmt);

$listSql = "
SELECT * FROM (
    SELECT inner_data.*, ROWNUM rnum FROM (
        SELECT r.reservation_id, r.user_id, u.name AS user_name,
               r.movie_title, r.screen_name,
               TO_CHAR(r.start_time_snapshot, 'YYYY-MM-DD HH24:MI') AS start_time,
               TO_CHAR(r.reservation_time, 'YYYY-MM-DD HH24:MI') AS reservation_time,
               r.cancel_status,
               NVL(LISTAGG(seat.seat_number, ', ') WITHIN GROUP (ORDER BY seat.seat_number), '-') AS seat_numbers
        FROM Reservations r
        JOIN Users u ON r.user_id = u.user_id
        LEFT JOIN ReservedSeats rs ON r.reservation_id = rs.reservation_id
        LEFT JOIN Seats seat ON rs.seat_id = seat.seat_id
        GROUP BY r.reservation_id, r.user_id, u.name, r.movie_title, r.screen_name, r.start_time_snapshot, r.reservation_time, r.cancel_status
        ORDER BY r.reservation_id DESC
    ) inner_data
    WHERE ROWNUM <= :eidx
)
WHERE rnum >= :sidx
";

$listStmt = oci_parse($conn, $listSql);
oci_bind_by_name($listStmt, ":sidx", $start);
oci_bind_by_name($listStmt, ":eidx", $end);
oci_execute($listStmt);
oci_fetch_all($listStmt, $resData, 0, 0, OCI_FETCHSTATEMENT_BY_ROW);
oci_free_statement($listStmt);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>예매 관리</title>
  <style>
    table { border-collapse: collapse; width: 100%; margin-top: 20px; background: white; color: black; }
    th, td { border: 1px solid #999; padding: 10px; text-align: center; }
    .cancelled { color: red; font-weight: bold; }
  </style>
</head>
<body>
  <h1>예매 관리</h1>
  <?php if ($successMsg): ?>
    <p style="color: lightgreen;"><?= htmlspecialchars($successMsg) ?></p>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <p style="color: red;"><?= htmlspecialchars($errorMsg) ?></p>
  <?php endif; ?>

  <table>
    <tr>
      <th>예약 ID</th>
      <th>유저 ID</th>
      <th>이름</th>
      <th>영화</th>
      <th>상영관</th>
      <th>상영 시간</th>
      <th>예약 시간</th>
      <th>좌석</th>
      <th>상태</th>
      <th>취소</th>
    </tr>
    <?php foreach ($resData as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['RESERVATION_ID']) ?></td>
      <td><?= htmlspecialchars($row['USER_ID']) ?></td>
      <td><?= htmlspecialchars($row['USER_NAME']) ?></td>
      <td><?= htmlspecialchars($row['MOVIE_TITLE']) ?></td>
      <td><?= htmlspecialchars($row['SCREEN_NAME']) ?></td>
      <td><?= htmlspecialchars($row['START_TIME']) ?></td>
      <td><?= htmlspecialchars($row['RESERVATION_TIME']) ?></td>
      <td><?= htmlspecialchars($row['SEAT_NUMBERS']) ?></td>
      <td>
        <?= $row['CANCEL_STATUS'] === 'Y' ? "<span class='cancelled'>취소됨</span>" : "정상" ?>
      </td>
      <td>
        <?php if ($row['CANCEL_STATUS'] === 'N'): ?>
        <form method="post" onsubmit="return confirm('이 예매를 취소하시겠습니까?');">
          <input type="hidden" name="reservation_id" value="<?= $row['RESERVATION_ID'] ?>">
          <button type="submit">취소</button>
        </form>
        <?php else: ?>
        -
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <p>
  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <?php if ($i === $page): ?>
      <strong>[<?= $i ?>]</strong>
    <?php else: ?>
      <a href="?page=<?= $i ?>">[<?= $i ?>]</a>
    <?php endif; ?>
  <?php endfor; ?>
  </p>

  <p><a href="admin_index.php" style="color: white; text-decoration: underline;">← 관리자 홈</a></p>
</body>
</html>

<?php oci_close($conn); ?>
