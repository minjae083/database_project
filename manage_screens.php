<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php"); 
    exit;
}

$errorMsg = "";
$successMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['screen_name']) && trim($_POST['screen_name']) !== "") {
        $screen_name = trim($_POST['screen_name']);

        $checkSql = "SELECT COUNT(*) AS cnt FROM Screens WHERE name = :name";
        $checkStmt = oci_parse($conn, $checkSql);
        oci_bind_by_name($checkStmt, ":name", $screen_name);
        oci_execute($checkStmt);
        $row = oci_fetch_assoc($checkStmt);
        oci_free_statement($checkStmt);

        if ($row['CNT'] > 0) {
            $errorMsg = "⚠ 이미 같은 이름의 상영관이 존재합니다.";
        } else {
            $insertSql = "INSERT INTO Screens (screen_id, name) VALUES (Screens_seq.NEXTVAL, :name) RETURNING screen_id INTO :new_id";
            $insertStmt = oci_parse($conn, $insertSql);
            oci_bind_by_name($insertStmt, ":name", $screen_name);
            oci_bind_by_name($insertStmt, ":new_id", $new_screen_id, 32);

            if (oci_execute($insertStmt)) {
                oci_free_statement($insertStmt);

                $rows = range('A', 'H');
$cols = range(1, 14);
$excluded = ['F1','F2','G1','G2','H1','H2','A13','A14','B13','B14','C13','C14'];

foreach ($rows as $r) {
    foreach ($cols as $c) {
        $seat_number = $r . $c;

        if (in_array($seat_number, $excluded)) {
            continue;
        }

        $seatSql = "INSERT INTO Seats (seat_id, seat_number, screen_id, is_active)
                    VALUES (Seats_seq.NEXTVAL, :seat_number, :screen_id, 'Y')";
        $seatStmt = oci_parse($conn, $seatSql);
        oci_bind_by_name($seatStmt, ":seat_number", $seat_number);
        oci_bind_by_name($seatStmt, ":screen_id", $new_screen_id);
        oci_execute($seatStmt);
        oci_free_statement($seatStmt);
    }
}

                $disabledSeats = ['F1', 'F2', 'G1', 'G2', 'H1', 'H2', 'A13', 'A14', 'B13', 'B14', 'C13', 'C14'];
                foreach ($disabledSeats as $ds) {
                    $updateSql = "UPDATE Seats SET is_active = 'N' WHERE screen_id = :screen_id AND seat_number = :seat_number";
                    $updateStmt = oci_parse($conn, $updateSql);
                    oci_bind_by_name($updateStmt, ":screen_id", $new_screen_id);
                    oci_bind_by_name($updateStmt, ":seat_number", $ds);
                    oci_execute($updateStmt);
                    oci_free_statement($updateStmt);
                }

                $successMsg = "✅ 상영관과 좌석이 추가되었습니다.";
            } else {
                $e = oci_error($insertStmt);
                $errorMsg = $e['message'];
                oci_free_statement($insertStmt);
            }
        }
    }

    if (isset($_POST['screen_id'])) {
        $screen_id = $_POST['screen_id'];

        try {
            $sql = "
                DELETE FROM ReservedSeats
                WHERE seat_id IN (
                    SELECT seat_id FROM Seats WHERE screen_id = :id
                )";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":id", $screen_id);
            oci_execute($stmt);
            oci_free_statement($stmt);

            $sql = "
                DELETE FROM Reservations
                WHERE screening_id IN (
                    SELECT screening_id FROM Screenings WHERE screen_id = :id
                )";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":id", $screen_id);
            oci_execute($stmt);
            oci_free_statement($stmt);

            $sql = "DELETE FROM Screenings WHERE screen_id = :id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":id", $screen_id);
            oci_execute($stmt);
            oci_free_statement($stmt);

            $sql = "DELETE FROM Seats WHERE screen_id = :id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":id", $screen_id);
            oci_execute($stmt);
            oci_free_statement($stmt);

            $sql = "DELETE FROM Screens WHERE screen_id = :id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":id", $screen_id);
            if (oci_execute($stmt)) {
                $successMsg = "🗑 상영관과 관련된 모든 데이터가 삭제되었습니다.";
            } else {
                $e = oci_error($stmt);
                $errorMsg = $e['message'];
            }
            oci_free_statement($stmt);
        } catch (Exception $e) {
            $errorMsg = "삭제 도중 오류 발생: " . $e->getMessage();
        }
    }
}

$screenList = [];
$listSql = "SELECT screen_id, name FROM Screens ORDER BY screen_id";
$listStmt = oci_parse($conn, $listSql);
oci_execute($listStmt);
while ($row = oci_fetch_assoc($listStmt)) {
    $screenList[] = $row;
}
oci_free_statement($listStmt);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>상영관 관리</title>
</head>
<body>
  <h1>상영관 관리</h1>

  <?php if ($successMsg): ?>
    <p style="color: green;"><?= htmlspecialchars($successMsg) ?></p>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <p style="color: red;"><?= htmlspecialchars($errorMsg) ?></p>
  <?php endif; ?>

  <h2>등록된 상영관 목록</h2>
  <table border="1">
    <tr>
      <th>ID</th>
      <th>이름</th>
      <th>삭제</th>
    </tr>
    <?php foreach ($screenList as $s): ?>
      <tr>
        <td><?= htmlspecialchars($s['SCREEN_ID']) ?></td>
        <td><?= htmlspecialchars($s['NAME']) ?></td>
        <td>
          <form method="post" action="manage_screens.php" onsubmit="return confirm('정말 삭제하시겠습니까?');">
            <input type="hidden" name="screen_id" value="<?= $s['SCREEN_ID'] ?>">
            <button type="submit">삭제</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h2>새 상영관 추가</h2>
  <form method="post" action="manage_screens.php">
    <label>상영관 이름: <input type="text" name="screen_name" required></label>
    <button type="submit">추가</button>
  </form>

  <p><a href="admin_index.php">← 관리자 대시보드로</a></p>
</body>
</html>
<?php oci_close($conn); ?>
