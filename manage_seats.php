<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}

$errorMsg = "";
$successMsg = "";

$screenOptions = [];
$stmt = oci_parse($conn, "SELECT screen_id, name FROM Screens ORDER BY name");
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $screenOptions[] = $row;
}
oci_free_statement($stmt);

$screeningList = [];
$sql = "SELECT sc.screening_id, m.title AS movie_title, s.name AS screen_name,
               TO_CHAR(sc.start_time, 'YYYY-MM-DD HH24:MI') AS start_time
        FROM Screenings sc
        JOIN Movies m ON sc.movie_id = m.movie_id
        JOIN Screens s ON sc.screen_id = s.screen_id
        ORDER BY sc.start_time";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $screeningList[] = $row;
}
oci_free_statement($stmt);

$selected_screening_id = isset($_GET['screening_id']) ? intval($_GET['screening_id']) : 0;
$seatMap = [];
$total_seats = 0;
$reserved_count = 0;

if ($selected_screening_id > 0) {
    $sql = "SELECT s.seat_id, s.seat_number, s.is_active,
                   u.user_id, u.name AS user_name, r.reservation_time, r.reservation_id, r.movie_title
            FROM Seats s
            LEFT JOIN ReservedSeats rs ON s.seat_id = rs.seat_id
            LEFT JOIN Reservations r ON rs.reservation_id = r.reservation_id
                                   AND r.screening_id = :sid
                                   AND r.cancel_status = 'N'
            LEFT JOIN Users u ON r.user_id = u.user_id
            WHERE s.screen_id = (SELECT screen_id FROM Screenings WHERE screening_id = :sid)
            ORDER BY s.seat_number";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":sid", $selected_screening_id);
    oci_execute($stmt);
    while ($row = oci_fetch_assoc($stmt)) {
        $num = intval(preg_replace("/[^0-9]/", "", $row['SEAT_NUMBER']));
        $row_char = preg_replace("/[^A-Z]/", "", $row['SEAT_NUMBER']);
        $seatMap[$row_char][$num] = $row;
        $total_seats++;
        if ($row['USER_ID']) $reserved_count++;
    }
    oci_free_statement($stmt);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>좌석 관리 (그리드)</title>
  <style>
    table.grid { border-collapse: collapse; margin-top: 20px; }
    table.grid td {
      width: 60px; height: 60px; text-align: center; vertical-align: middle;
      border: 1px solid #ccc; background: #eee;
    }
    td.reserved { background: #999; color: white; }
    td .info { font-size: 11px; color: darkred; line-height: 1.2; margin-top: 3px; }
  </style>
</head>
<body>
  <h1>좌석 조회</h1>

  <?php if ($successMsg): ?><p style="color: green;"><?= htmlspecialchars($successMsg) ?></p><?php endif; ?>
  <?php if ($errorMsg): ?><p style="color: red;"><?= htmlspecialchars($errorMsg) ?></p><?php endif; ?>

  <h2>상영 선택</h2>
  <form method="get">
    <label>상영:
      <select name="screening_id" required>
        <option value="">-- 선택 --</option>
        <?php foreach ($screeningList as $s): ?>
          <?php $label = "{$s['MOVIE_TITLE']} - {$s['SCREEN_NAME']} ({$s['START_TIME']})"; ?>
          <option value="<?= $s['SCREENING_ID'] ?>" <?= $selected_screening_id == $s['SCREENING_ID'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">조회</button>
  </form>

  <?php if (!empty($seatMap)): ?>
    <h3>좌석 상태 (A~H / 1~14)</h3>
    <p>총 좌석: <?= $total_seats ?> / 예매: <?= $reserved_count ?> / 예매율: <?= $total_seats > 0 ? round($reserved_count / $total_seats * 100, 1) : 0 ?>%</p>
    <table class="grid">
      <tr><td></td>
      <?php for ($i = 1; $i <= 14; $i++): ?>
        <td><strong><?= $i ?></strong></td>
      <?php endfor; ?></tr>

      <?php foreach (range('A', 'H') as $row): ?>
        <tr><td><strong><?= $row ?></strong></td>
        <?php for ($i = 1; $i <= 14; $i++): ?>
          <?php
            $cell = $seatMap[$row][$i] ?? null;
            if ($cell):
              $reservedClass = !empty($cell['USER_ID']) ? 'reserved' : '';
          ?>
            <td class="<?= $reservedClass ?>">
              <div><?= $cell['SEAT_NUMBER'] ?></div>
              <?php if (!empty($cell['USER_ID'])): ?>
                <div class="info">
                  <?= htmlspecialchars($cell['USER_NAME']) ?><br>
                  <?= htmlspecialchars($cell['RESERVATION_TIME']) ?><br>
                  예약 ID: <?= htmlspecialchars($cell['RESERVATION_ID']) ?><br>
                  영화: <?= htmlspecialchars($cell['MOVIE_TITLE']) ?>
                </div>
              <?php endif; ?>
            </td>
          <?php else: ?>
            <td></td>
          <?php endif; ?>
        <?php endfor; ?>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php elseif ($selected_screening_id): ?>
    <p>좌석 정보를 불러올 수 없습니다.</p>
  <?php endif; ?>

  <p><a href="admin_index.php">← 관리자 홈</a></p>
</body>
</html>
<?php oci_close($conn); ?>
