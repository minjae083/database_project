<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    die("로그인이 필요합니다.");
}

$user_id = $_SESSION['user_id'];
$movie_id = $_POST['movie_id'] ?? '';
$is_like = $_POST['is_like'] ?? '';
$title = $_POST['title'] ?? '';

if (!is_numeric($movie_id) || !in_array($is_like, ['0', '1'])) {
    die("잘못된 요청입니다.");
}

$sql = "
    MERGE INTO MovieLikes target
    USING (SELECT :user_id AS user_id, :movie_id AS movie_id FROM dual) source
    ON (target.user_id = source.user_id AND target.movie_id = source.movie_id)
    WHEN MATCHED THEN
        UPDATE SET is_like = :is_like
    WHEN NOT MATCHED THEN
        INSERT (user_id, movie_id, is_like)
        VALUES (:user_id, :movie_id, :is_like)
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_bind_by_name($stmt, ':movie_id', $movie_id);
oci_bind_by_name($stmt, ':is_like', $is_like);
$result = oci_execute($stmt);

if (!$result) {
    $e = oci_error($stmt);
    echo "DB 오류: " . $e['message'];
    exit;
}

oci_free_statement($stmt);
oci_close($conn);

header("Location: detail.php?title=" . urlencode($title));
exit;
?>
