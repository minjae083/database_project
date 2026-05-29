<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage + 1;
$end = $page * $perPage;

$countSql = "SELECT COUNT(*) AS CNT FROM Movies";
$countStmt = oci_parse($conn, $countSql);
oci_execute($countStmt);
$totalRow = oci_fetch_assoc($countStmt);
$total = $totalRow ? intval($totalRow['CNT']) : 0;
$totalPages = ceil($total / $perPage);
oci_free_statement($countStmt);

$sql = "
SELECT * FROM (
    SELECT inner.*, ROWNUM rnum FROM (
        SELECT 
            movie_id,
            title,
            EXTRACT(MINUTE FROM duration) + EXTRACT(HOUR FROM duration) * 60 AS duration_min,
            genre,
            poster_path,
            teaser_url
        FROM Movies
        ORDER BY movie_id
    ) inner
    WHERE ROWNUM <= :eidx
)
WHERE rnum >= :sidx
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":sidx", $start);
oci_bind_by_name($stmt, ":eidx", $end);
oci_execute($stmt);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>영화 목록 관리</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: center; }
        img { max-width: 100px; }
    </style>
</head>
<body>
    <h1>영화 목록 관리</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>제목</th>
                <th>상영 시간</th>
                <th>장르</th>
                <th>포스터</th>
                <th>티저</th>
                <th>관리</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = oci_fetch_assoc($stmt)) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['MOVIE_ID']) ?></td>
                    <td><?= htmlspecialchars($row['TITLE']) ?></td>
                    <td><?= htmlspecialchars($row['DURATION_MIN']) ?>분</td>
                    <td><?= htmlspecialchars($row['GENRE']) ?></td>
                    <td>
                        <?php if (!empty($row['POSTER_PATH'])): ?>
                            <img src="<?= htmlspecialchars($row['POSTER_PATH']) ?>" alt="poster">
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($row['TEASER_URL'])): ?>
                            <a href="<?= htmlspecialchars($row['TEASER_URL']) ?>" target="_blank">링크</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_movie.php?movie_id=<?= $row['MOVIE_ID'] ?>">수정</a> |
                        <a href="delete_movie.php?movie_id=<?= $row['MOVIE_ID'] ?>" onclick="return confirm('정말 삭제할까요?')">삭제</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
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
    <p><a href="admin_index.php">← 관리자 대시보드로</a></p>
</body>
</html>

<?php
oci_free_statement($stmt);
oci_close($conn);
?>
