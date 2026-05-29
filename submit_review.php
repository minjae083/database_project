<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("로그인이 필요합니다.");
}

require_once 'conn.php';

$movie_id = $_POST['movie_id'];
$user_id = $_SESSION['user_id'];
$rating = $_POST['rating'] ?? null;
$review_content = $_POST['review_content'];

if ($rating === null || $rating === "") {
    echo "<script>alert('별점을 선택해 주세요.'); history.back();</script>";
    exit;
}

$sql = "INSERT INTO Reviews (user_id, movie_id, review_content, rating, created_at) 
        VALUES (:user_id, :movie_id, :review_content, :rating, SYSDATE)";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":user_id", $user_id);
oci_bind_by_name($stid, ":movie_id", $movie_id);
oci_bind_by_name($stid, ":review_content", $review_content);
oci_bind_by_name($stid, ":rating", $rating);

if (oci_execute($stid)) {
    echo "<script>alert('리뷰가 등록되었습니다!'); window.history.back();</script>";
} else {
    $error = oci_error($stid)['message'];
    echo "<script>alert('오류 발생: " . addslashes($error) . "'); window.history.back();</script>";
}

oci_free_statement($stid);
oci_close($conn);
?>
