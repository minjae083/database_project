<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}

if (isset($_GET['movie_id'])) {
    $movie_id = intval($_GET['movie_id']);

    $getScreenings = oci_parse($conn, "SELECT screening_id FROM Screenings WHERE movie_id = :id");
    oci_bind_by_name($getScreenings, ":id", $movie_id);
    oci_execute($getScreenings);

    while ($screening = oci_fetch_assoc($getScreenings)) {
        $screening_id = $screening['SCREENING_ID'];

        $getReservations = oci_parse($conn, "SELECT reservation_id FROM Reservations WHERE screening_id = :sid");
        oci_bind_by_name($getReservations, ":sid", $screening_id);
        oci_execute($getReservations);

        while ($res = oci_fetch_assoc($getReservations)) {
            $res_id = $res['RESERVATION_ID'];

            $delSeats = oci_parse($conn, "DELETE FROM ReservedSeats WHERE reservation_id = :rid");
            oci_bind_by_name($delSeats, ":rid", $res_id);
            oci_execute($delSeats);
            oci_free_statement($delSeats);
        }
        oci_free_statement($getReservations);

        $delReservations = oci_parse($conn, "DELETE FROM Reservations WHERE screening_id = :sid");
        oci_bind_by_name($delReservations, ":sid", $screening_id);
        oci_execute($delReservations);
        oci_free_statement($delReservations);
    }
    oci_free_statement($getScreenings);

    $delReviews = oci_parse($conn, "DELETE FROM Reviews WHERE movie_id = :id");
    oci_bind_by_name($delReviews, ":id", $movie_id);
    oci_execute($delReviews);
    oci_free_statement($delReviews);

    $delLikes = oci_parse($conn, "DELETE FROM MovieLikes WHERE movie_id = :id");
    oci_bind_by_name($delLikes, ":id", $movie_id);
    oci_execute($delLikes);
    oci_free_statement($delLikes);

    $delScreenings = oci_parse($conn, "DELETE FROM Screenings WHERE movie_id = :id");
    oci_bind_by_name($delScreenings, ":id", $movie_id);
    oci_execute($delScreenings);
    oci_free_statement($delScreenings);

    $delMovie = oci_parse($conn, "DELETE FROM Movies WHERE movie_id = :id");
    oci_bind_by_name($delMovie, ":id", $movie_id);
    if (oci_execute($delMovie)) {
        echo "<script>alert('삭제 완료'); location.href='delete_movie.php';</script>";
    } else {
        $e = oci_error($delMovie);
        echo "<script>alert('삭제 실패: " . htmlspecialchars($e['message']) . "');</script>";
    }
    oci_free_statement($delMovie);
}


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
        SELECT movie_id, title, genre
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
    <title>영화 삭제</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: center; }
    </style>
</head>
<body>
    <h1>영화 삭제</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>제목</th>
                <th>장르</th>
                <th>삭제</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = oci_fetch_assoc($stmt)): ?>
            <tr>
                <td><?= htmlspecialchars($row['MOVIE_ID']) ?></td>
                <td><?= htmlspecialchars($row['TITLE']) ?></td>
                <td><?= htmlspecialchars($row['GENRE']) ?></td>
                <td>
                    <a href="?movie_id=<?= $row['MOVIE_ID'] ?>" onclick="return confirm('정말 삭제하시겠습니까?')">삭제</a>
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
