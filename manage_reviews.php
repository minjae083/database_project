<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}

$successMsg = "";
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    $review_id = $_POST['review_id'];
    $delSql = "DELETE FROM Reviews WHERE review_id = :rid";
    $delStmt = oci_parse($conn, $delSql);
    oci_bind_by_name($delStmt, ":rid", $review_id);
    if (oci_execute($delStmt)) {
        $successMsg = "리뷰가 삭제되었습니다.";
    } else {
        $e = oci_error($delStmt);
        $errorMsg = $e['message'];
    }
    oci_free_statement($delStmt);
}

$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$startrow = ($page - 1) * $perPage + 1;
$endrow = $page * $perPage;

$filter_user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;

$countSql = "SELECT COUNT(*) AS CNT FROM Reviews" . ($filter_user_id ? " WHERE user_id = :uid_count" : "");
$countStmt = oci_parse($conn, $countSql);
if ($filter_user_id) {
    oci_bind_by_name($countStmt, ":uid_count", $filter_user_id);
}
oci_execute($countStmt);
$totalRow = oci_fetch_assoc($countStmt);
$total = $totalRow ? intval($totalRow['CNT']) : 0;
$totalPages = ceil($total / $perPage);
oci_free_statement($countStmt);

$sql = "
SELECT * FROM (
    SELECT inner_query.*, ROWNUM rnum FROM (
        SELECT r.review_id, m.title AS movie_title, u.user_id, u.name AS user_name,
               r.rating, r.review_content, TO_CHAR(r.created_at, 'YYYY-MM-DD HH24:MI') AS created_at
        FROM Reviews r
        JOIN Movies m ON r.movie_id = m.movie_id
        JOIN Users u ON r.user_id = u.user_id
        " . ($filter_user_id ? "WHERE r.user_id = :uid_list " : "") . "
        ORDER BY r.created_at DESC
    ) inner_query
    WHERE ROWNUM <= :erow
)
WHERE rnum >= :srow
";

$stmt = oci_parse($conn, $sql);
if ($filter_user_id) {
    oci_bind_by_name($stmt, ":uid_list", $filter_user_id);
}
oci_bind_by_name($stmt, ":srow", $startrow);
oci_bind_by_name($stmt, ":erow", $endrow);
oci_execute($stmt);

$reviewList = [];
while ($row = oci_fetch_assoc($stmt)) {
    if (isset($row['REVIEW_CONTENT']) && is_object($row['REVIEW_CONTENT'])) {
        $row['REVIEW_CONTENT'] = $row['REVIEW_CONTENT']->load();
    }
    $reviewList[] = $row;
}
oci_free_statement($stmt);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>리뷰 관리</title>
</head>
<body>
    <h1>리뷰 관리</h1>

    <?php if ($successMsg): ?>
        <p style="color: green;"><?= htmlspecialchars($successMsg) ?></p>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <p style="color: red;">오류 발생: <?= htmlspecialchars($errorMsg) ?></p>
    <?php endif; ?>

    <?php if ($filter_user_id): ?>
        <p><strong><?= htmlspecialchars($filter_user_id) ?></strong>의 리뷰만 표시 중</p>
        <p><a href="manage_reviews.php">[전체 리뷰 보기]</a></p>
    <?php endif; ?>

    <table border="1" cellpadding="8">
        <tr>
            <th>리뷰 ID</th>
            <th>영화</th>
            <th>유저 ID</th>
            <th>이름</th>
            <th>평점</th>
            <th>내용</th>
            <th>작성일</th>
            <th>삭제</th>
        </tr>
        <?php foreach ($reviewList as $rev): ?>
        <tr>
            <td><?= htmlspecialchars($rev['REVIEW_ID']) ?></td>
            <td><?= htmlspecialchars($rev['MOVIE_TITLE']) ?></td>
            <td><?= htmlspecialchars($rev['USER_ID']) ?></td>
            <td><?= htmlspecialchars($rev['USER_NAME']) ?></td>
            <td><?= htmlspecialchars($rev['RATING']) ?></td>
            <td><?= nl2br(htmlspecialchars($rev['REVIEW_CONTENT'])) ?></td>
            <td><?= $rev['CREATED_AT'] ?></td>
            <td>
                <form method="post" onsubmit="return confirm('이 리뷰를 삭제하시겠습니까?');">
                    <input type="hidden" name="review_id" value="<?= $rev['REVIEW_ID'] ?>">
                    <button type="submit">삭제</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
            <strong>[<?= $i ?>]</strong>
        <?php else: ?>
            <a href="?page=<?= $i ?><?= $filter_user_id ? '&user_id=' . urlencode($filter_user_id) : '' ?>">[<?= $i ?>]</a>
        <?php endif; ?>
    <?php endfor; ?>
    </p>

    <p><a href="admin_index.php">← 관리자 대시보드로</a></p>
</body>
</html>

<?php oci_close($conn); ?>
