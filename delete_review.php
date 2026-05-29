<?php
session_start();
require_once 'conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $review_id = $_POST['review_id'];
    $user_id = $_SESSION['user_id'];

    $check_sql = "SELECT review_id FROM Reviews WHERE review_id = :review_id AND user_id = :user_id";
    $check_stmt = oci_parse($conn, $check_sql);
    oci_bind_by_name($check_stmt, ":review_id", $review_id);
    oci_bind_by_name($check_stmt, ":user_id", $user_id);
    oci_execute($check_stmt);

    if (oci_fetch($check_stmt)) {
        $delete_sql = "DELETE FROM Reviews WHERE review_id = :review_id";
        $delete_stmt = oci_parse($conn, $delete_sql);
        oci_bind_by_name($delete_stmt, ":review_id", $review_id);
        oci_execute($delete_stmt);
        oci_free_statement($delete_stmt);

        $message = "리뷰가 삭제되었습니다.";
    } else {
        $message = "리뷰 삭제에 실패했습니다. 다시 시도해주세요.";
    }

    oci_free_statement($check_stmt);
    oci_close($conn);

    header("Location: my_reviews.php?message=" . urlencode($message));
    exit;
}
?>
