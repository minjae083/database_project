<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    if ($user_id === 'deleted_user') {
        echo "<script>alert('deleted_user 계정은 삭제할 수 없습니다.');</script>";
    } else {
        try {
            $sql = "UPDATE Reviews SET user_id = 'deleted_user' WHERE user_id = :id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":id", $user_id);
            oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            oci_free_statement($stmt);

            $sql = "DELETE FROM ReservedSeats WHERE reservation_id IN (
                        SELECT reservation_id FROM Reservations WHERE user_id = :id)";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":id", $user_id);
            oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            oci_free_statement($stmt);

            $sql = "DELETE FROM Reservations WHERE user_id = :id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":id", $user_id);
            oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            oci_free_statement($stmt);

            $sql = "DELETE FROM Users WHERE user_id = :id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":id", $user_id);
            oci_execute($stmt);
            oci_commit($conn);
            oci_free_statement($stmt);

            echo "<script>alert('유저 삭제 완료'); window.location.href='manage_users.php';</script>";
        } catch (Exception $e) {
            oci_rollback($conn);
            echo "<script>alert('삭제 중 오류 발생: " . htmlspecialchars($e->getMessage()) . "');</script>";
        }
    }
}

$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage + 1;
$end = $page * $perPage;

$searchType = $_GET['type'] ?? '';
$searchQuery = trim($_GET['query'] ?? '');
$where = '';
$bindParam = '';

if ($searchType === 'user_id' || $searchType === 'email') {
    $where = " WHERE $searchType LIKE :query ";
    $bindParam = "%{$searchQuery}%";
}

$countSql = "SELECT COUNT(*) AS CNT FROM Users" . $where;
$countStmt = oci_parse($conn, $countSql);
if ($bindParam) oci_bind_by_name($countStmt, ":query", $bindParam);
oci_execute($countStmt);
$row = oci_fetch_assoc($countStmt);
$total = $row ? intval($row['CNT']) : 0;
$totalPages = ceil($total / $perPage);
oci_free_statement($countStmt);

$sql = "
SELECT * FROM (
  SELECT inner.*, ROWNUM rnum FROM (
    SELECT user_id, name, email FROM Users $where ORDER BY user_id
  ) inner
  WHERE ROWNUM <= :eidx
)
WHERE rnum >= :sidx
";
$stmt = oci_parse($conn, $sql);
if ($bindParam) oci_bind_by_name($stmt, ":query", $bindParam);
oci_bind_by_name($stmt, ":sidx", $start);
oci_bind_by_name($stmt, ":eidx", $end);
oci_execute($stmt);
$users = [];
while ($row = oci_fetch_assoc($stmt)) {
    $users[] = $row;
}
oci_free_statement($stmt);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>유저 관리</title>
</head>
<body>
  <h1>유저 관리</h1>

  <h2>유저 검색</h2>
  <form method="get" action="">
    <select name="type" id="type">
      <option value="user_id" <?= $searchType === 'user_id' ? 'selected' : '' ?>>ID</option>
      <option value="email" <?= $searchType === 'email' ? 'selected' : '' ?>>이메일</option>
    </select>
    <input type="text" name="query" id="query" value="<?= htmlspecialchars($searchQuery) ?>" required>
    <button type="submit">검색</button>
  </form>

  <h2>유저 목록</h2>
  <table border="1" cellpadding="8">
    <tr>
      <th>유저 ID</th>
      <th>이름</th>
      <th>이메일</th>
      <th>수정</th>
      <th>리뷰</th>
      <th>삭제</th>
    </tr>
    <?php foreach ($users as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['USER_ID']) ?></td>
      <td><?= htmlspecialchars($row['NAME']) ?></td>
      <td><?= htmlspecialchars($row['EMAIL']) ?></td>
      <td><a href="edit_user.php?user_id=<?= urlencode($row['USER_ID']) ?>">수정</a></td>
      <td><a href="manage_reviews.php?user_id=<?= urlencode($row['USER_ID']) ?>">리뷰보기</a></td>
      <td>
        <form method="POST" onsubmit="return confirm('정말로 삭제하시겠습니까?')">
          <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['USER_ID']) ?>">
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
      'type' => $searchType,
      'query' => $searchQuery
    ]);
    ?>
    <?php if ($i === $page): ?>
      <strong>[<?= $i ?>]</strong>
    <?php else: ?>
      <a href="?<?= $query ?>">[<?= $i ?>]</a>
    <?php endif; ?>
  <?php endfor; ?>
  </p>

  <p><a href="admin_index.php">← 관리자 대시보드로 돌아가기</a></p>
</body>
</html>
<?php oci_close($conn); ?>
