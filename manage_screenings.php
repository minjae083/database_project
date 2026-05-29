<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}

$filter_movie_id = $_GET['filter_movie_id'] ?? '';
$filter_screen_id = $_GET['filter_screen_id'] ?? '';
$successMsg = $_GET['success'] ?? '';
$errorMsg = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['screening_id'])) {
    $screening_id = $_POST['screening_id'];

    $res_sql = "SELECT reservation_id FROM Reservations WHERE screening_id = :id";
    $res_stmt = oci_parse($conn, $res_sql);
    oci_bind_by_name($res_stmt, ":id", $screening_id);
    oci_execute($res_stmt);

    $reservation_ids = [];
    while ($row = oci_fetch_assoc($res_stmt)) {
        $reservation_ids[] = $row['RESERVATION_ID'];
    }
    oci_free_statement($res_stmt);

    foreach ($reservation_ids as $res_id) {
        $del_seat = oci_parse($conn, "DELETE FROM ReservedSeats WHERE reservation_id = :rid");
        oci_bind_by_name($del_seat, ":rid", $res_id);
        oci_execute($del_seat);
        oci_free_statement($del_seat);
    }

    foreach ($reservation_ids as $res_id) {
        $del_res = oci_parse($conn, "DELETE FROM Reservations WHERE reservation_id = :rid");
        oci_bind_by_name($del_res, ":rid", $res_id);
        oci_execute($del_res);
        oci_free_statement($del_res);
    }

    $del_sql = "DELETE FROM Screenings WHERE screening_id = :id";
    $stmt = oci_parse($conn, $del_sql);
    oci_bind_by_name($stmt, ':id', $screening_id);

    if (oci_execute($stmt)) {
        oci_free_statement($stmt);
        header("Location: manage_screenings.php?success=" . urlencode("상영 및 관련 예매가 삭제되었습니다."));
    } else {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        header("Location: manage_screenings.php?error=" . urlencode("삭제 실패: {$e['message']}"));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movie_id'], $_POST['screen_id'], $_POST['start_time'])) {
    $movie_id = $_POST['movie_id'];
    $screen_id = $_POST['screen_id'];
    $start_time = str_replace("T", " ", $_POST['start_time']);

    $insertSql = "INSERT INTO Screenings (screening_id, movie_id, screen_id, start_time)
                  VALUES (Screenings_seq.NEXTVAL, :movie_id, :screen_id, TO_DATE(:start_time, 'YYYY-MM-DD HH24:MI:SS'))";

    $stmt = oci_parse($conn, $insertSql);
    oci_bind_by_name($stmt, ':movie_id', $movie_id);
    oci_bind_by_name($stmt, ':screen_id', $screen_id);
    oci_bind_by_name($stmt, ':start_time', $start_time);
    if (oci_execute($stmt)) {
        oci_free_statement($stmt);
        header("Location: manage_screenings.php?success=" . urlencode("새 상영 일정을 추가했습니다."));
    } else {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        header("Location: manage_screenings.php?error=" . urlencode("추가 실패: {$e['message']}"));
    }
    exit;
}

$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage + 1;
$end = $page * $perPage;

$countSql = "SELECT COUNT(*) AS CNT FROM Screenings WHERE 1=1";
if (!empty($filter_movie_id)) $countSql .= " AND movie_id = :movie_id";
if (!empty($filter_screen_id)) $countSql .= " AND screen_id = :screen_id";
$countStmt = oci_parse($conn, $countSql);
if (!empty($filter_movie_id)) oci_bind_by_name($countStmt, ':movie_id', $filter_movie_id);
if (!empty($filter_screen_id)) oci_bind_by_name($countStmt, ':screen_id', $filter_screen_id);
oci_execute($countStmt);
$totalRow = oci_fetch_assoc($countStmt);
$total = $totalRow ? intval($totalRow['CNT']) : 0;
$totalPages = ceil($total / $perPage);
oci_free_statement($countStmt);

$listSql = "
SELECT * FROM (
    SELECT inner_data.*, ROWNUM rnum FROM (
        SELECT sc.screening_id, m.title AS movie_title, s.name AS screen_name,
               TO_CHAR(sc.start_time, 'YYYY-MM-DD HH24:MI') AS start_time
        FROM Screenings sc
        JOIN Movies m ON sc.movie_id = m.movie_id
        JOIN Screens s ON sc.screen_id = s.screen_id
        WHERE 1=1";
if (!empty($filter_movie_id)) $listSql .= " AND sc.movie_id = :movie_id";
if (!empty($filter_screen_id)) $listSql .= " AND sc.screen_id = :screen_id";
$listSql .= " ORDER BY sc.start_time
    ) inner_data
    WHERE ROWNUM <= :eidx
)
WHERE rnum >= :sidx";

$stmt = oci_parse($conn, $listSql);
if (!empty($filter_movie_id)) oci_bind_by_name($stmt, ':movie_id', $filter_movie_id);
if (!empty($filter_screen_id)) oci_bind_by_name($stmt, ':screen_id', $filter_screen_id);
oci_bind_by_name($stmt, ':sidx', $start);
oci_bind_by_name($stmt, ':eidx', $end);
oci_execute($stmt);
$screeningList = [];
while ($row = oci_fetch_assoc($stmt)) {
    $screeningList[] = $row;
}
oci_free_statement($stmt);

$movieOptions = [];
$screenOptions = [];
$movieStmt = oci_parse($conn, "SELECT movie_id, title FROM Movies ORDER BY title");
oci_execute($movieStmt);
while ($row = oci_fetch_assoc($movieStmt)) $movieOptions[] = $row;
oci_free_statement($movieStmt);

$screenStmt = oci_parse($conn, "SELECT screen_id, name FROM Screens ORDER BY name");
oci_execute($screenStmt);
while ($row = oci_fetch_assoc($screenStmt)) $screenOptions[] = $row;
oci_free_statement($screenStmt);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>상영시간 관리</title>
</head>
<body>
<?php if ($successMsg): ?>
<script>alert("<?= htmlspecialchars($successMsg) ?>");</script>
<?php endif; ?>
<?php if ($errorMsg): ?>
<script>alert("<?= htmlspecialchars($errorMsg) ?>");</script>
<?php endif; ?>

<h1>상영시간 관리</h1>

<h2>필터</h2>
<form method="get" action="manage_screenings.php">
  <label>영화:
    <select name="filter_movie_id">
      <option value="">전체</option>
      <?php foreach ($movieOptions as $m): ?>
        <option value="<?= $m['MOVIE_ID'] ?>" <?= $filter_movie_id == $m['MOVIE_ID'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['TITLE']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>상영관:
    <select name="filter_screen_id">
      <option value="">전체</option>
      <?php foreach ($screenOptions as $s): ?>
        <option value="<?= $s['SCREEN_ID'] ?>" <?= $filter_screen_id == $s['SCREEN_ID'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['NAME']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <button type="submit">적용</button>
</form>

<h2>현재 상영 일정 목록</h2>
<table border="1">
<tr><th>상영 ID</th><th>영화</th><th>상영관</th><th>시작 시간</th><th>삭제</th></tr>
<?php foreach ($screeningList as $scr): ?>
<tr>
  <td><?= htmlspecialchars($scr['SCREENING_ID']) ?></td>
  <td><?= htmlspecialchars($scr['MOVIE_TITLE']) ?></td>
  <td><?= htmlspecialchars($scr['SCREEN_NAME']) ?></td>
  <td><?= htmlspecialchars($scr['START_TIME']) ?></td>
  <td>
    <form method="post" action="manage_screenings.php" onsubmit="return confirm('정말 삭제하시겠습니까? 관련된 예매 내역도 삭제됩니다.');">
      <input type="hidden" name="screening_id" value="<?= $scr['SCREENING_ID'] ?>">
      <button type="submit">삭제</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>

<p>
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
  <?php
  $query = http_build_query([
      'page' => $i,
      'filter_movie_id' => $filter_movie_id,
      'filter_screen_id' => $filter_screen_id
  ]);
  ?>
  <?php if ($i === $page): ?>
    <strong>[<?= $i ?>]</strong>
  <?php else: ?>
    <a href="?<?= $query ?>">[<?= $i ?>]</a>
  <?php endif; ?>
<?php endfor; ?>
</p>

<h2>새 상영 일정 추가</h2>
<form method="post" action="manage_screenings.php">
  <label>영화:
    <select name="movie_id" required>
      <option value="">영화 선택</option>
      <?php foreach ($movieOptions as $movie): ?>
        <option value="<?= $movie['MOVIE_ID'] ?>"><?= htmlspecialchars($movie['TITLE']) ?></option>
      <?php endforeach; ?>
    </select>
  </label><br>
  <label>상영관:
    <select name="screen_id" required>
      <option value="">상영관 선택</option>
      <?php foreach ($screenOptions as $screen): ?>
        <option value="<?= $screen['SCREEN_ID'] ?>"><?= htmlspecialchars($screen['NAME']) ?></option>
      <?php endforeach; ?>
    </select>
  </label><br>
  <label>시작 시간:
    <input type="datetime-local" name="start_time" required>
  </label><br>
  <button type="submit">추가</button>
</form>

<p><a href="admin_index.php">← 관리자 대시보드로</a></p>
</body>
</html>

<?php oci_close($conn); ?>
