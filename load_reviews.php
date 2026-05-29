<?php
require_once 'conn.php';

$movie_id = isset($_GET['movie_id']) ? $_GET['movie_id'] : 1;

$sort = isset($_GET['sort']) && strtolower($_GET['sort']) === 'asc' ? 'ASC' : 'DESC';

$sql = "SELECT u.name AS user_name, r.rating, r.review_content, r.created_at 
        FROM Reviews r 
        JOIN Users u ON r.user_id = u.user_id
        WHERE r.movie_id = :movie_id 
        ORDER BY r.rating $sort";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":movie_id", $movie_id);

oci_execute($stid);

$output = '';
$review_found = false;

while ($row = oci_fetch_assoc($stid)) {
    $review_found = true;
    $review_content = is_object($row['REVIEW_CONTENT']) ? $row['REVIEW_CONTENT']->load() : $row['REVIEW_CONTENT'];

    $user_name = htmlspecialchars($row['USER_NAME']);
    $rating = (int)$row['RATING'];
    $created_at = $row['CREATED_AT'];

    $timestamp = strtotime($created_at);
    $formatted_date = $timestamp ? date('y-m-d', $timestamp) : $created_at;

    $filled_stars = str_repeat("★", $rating);
    $empty_stars = str_repeat("☆", 5 - $rating);
    $star_display = $filled_stars . $empty_stars;

    $output .= "<div class='review'>";
    $output .= "<strong>{$user_name}</strong><br>";
    $output .= "평점: <span style='color: gold; font-size: 1.2em;'>{$star_display}</span><br>";
    $output .= "리뷰 내용: " . nl2br(htmlspecialchars($review_content)) . "<br>";
    $output .= "작성일: {$formatted_date}<br>";
    $output .= "</div><hr>";
}

if (!$review_found) {
    $output = "등록된 리뷰가 없습니다.";
}

echo $output;

oci_free_statement($stid);
oci_close($conn);
?>
