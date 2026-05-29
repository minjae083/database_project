<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'conn.php';

$user_id = $_SESSION['user_id'];
$rows_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start_row = ($page - 1) * $rows_per_page + 1;
$end_row = $start_row + $rows_per_page - 1;

$count_sql = "SELECT COUNT(*) AS total FROM Reviews WHERE user_id = :user_id";
$count_stmt = oci_parse($conn, $count_sql);
oci_bind_by_name($count_stmt, ":user_id", $user_id);
oci_execute($count_stmt);
$count_row = oci_fetch_assoc($count_stmt);
$total_rows = $count_row['TOTAL'];
oci_free_statement($count_stmt);

$sql = "
SELECT * FROM (
    SELECT r.review_id,
           m.title AS movie_title,
           r.review_content,
           r.rating,
           TO_CHAR(r.created_at, 'YYYY-MM-DD HH24:MI') AS created_at,
           ROW_NUMBER() OVER (ORDER BY r.created_at DESC) AS rn
    FROM Reviews r
    JOIN Movies m ON r.movie_id = m.movie_id
    WHERE r.user_id = :user_id
)
WHERE rn BETWEEN :start_row AND :end_row
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":user_id", $user_id);
oci_bind_by_name($stmt, ":start_row", $start_row);
oci_bind_by_name($stmt, ":end_row", $end_row);
oci_execute($stmt);

$reviews = [];
while ($row = oci_fetch_assoc($stmt)) {
    if ($row['REVIEW_CONTENT'] instanceof OCILob) {
        $row['REVIEW_CONTENT'] = $row['REVIEW_CONTENT']->load();
    }
    $reviews[] = $row;
}
oci_free_statement($stmt);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <title>마이페이지 - 리뷰 조회</title>
  <style>
    .nav {
      background-color:rgb(71, 71, 71);
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-family: Arial, sans-serif;
      color: white;
    }
    .nav-logo img {
      height: 40px;
    }
    .nav-links a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-size: 16px;
    }
    .nav-links a:hover {
      text-decoration: underline;
    }

    body {
      background-color: #d4d4d4;
      color: white;
      font-family: Arial, sans-serif;
      padding: 40px;
      margin: 0;
    }
    h1 {
      margin-top: 30px;
      margin-bottom: 40px;
    }
    .review-box {
      border: 1px solid #aaa;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      background-color: rgb(71, 71, 71);
    }
    .review-box p {
      margin: 8px 0;
    }
    .delete-form {
      margin-top: 10px;
      text-align: right;
    }
    .delete-form button {
      padding: 8px 12px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
      background-color: #e74c3c;
      color: white;
      font-weight: bold;
    }
    .delete-form button:hover {
      background-color: #c0392b;
    }
    .pagination {
      margin-top: 30px;
      text-align: center;
    }
    .pagination a, .pagination strong {
      color: white;
      margin: 0 8px;
      text-decoration: none;
      font-size: 16px;
    }
    .pagination strong {
      font-weight: bold;
    }
  </style>
</head>
<body>
<?php if (isset($_GET['message'])): ?>
<script>
    alert("<?= htmlspecialchars($_GET['message']) ?>");
</script>
<?php endif; ?>
<div class="nav">
  <a href="main.php" class="nav-logo">
    <img src="images/logo.png" alt="logo" />
  </a>
  <div class="nav-links">
    <a href="logout.php">로그아웃</a>
  </div>
</div>

<h1><?= htmlspecialchars($_SESSION['user_id']) ?>님의 리뷰 내역</h1>

<?php if (empty($reviews)): ?>
  <p>작성한 리뷰가 없습니다.</p>
<?php else: ?>
  <?php foreach ($reviews as $row): ?>
    <div class="review-box">
      <p><strong>영화 제목:</strong> <?= htmlspecialchars($row['MOVIE_TITLE']) ?></p>
      <p><strong>리뷰 내용:</strong><br><?= nl2br(htmlspecialchars($row['REVIEW_CONTENT'])) ?></p>
      <p><strong>평점:</strong> <?= htmlspecialchars($row['RATING']) ?> / 5</p>
      <p><strong>작성일:</strong> <?= htmlspecialchars($row['CREATED_AT']) ?></p>

      <form method="post" action="delete_review.php" class="delete-form" onsubmit="return confirm('정말 삭제하시겠습니까?');">
        <input type="hidden" name="review_id" value="<?= htmlspecialchars($row['REVIEW_ID']) ?>">
        <button type="submit">리뷰 삭제</button>
      </form>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php
$total_pages = ceil($total_rows / $rows_per_page);
if ($total_pages > 1):
?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <?php if ($i == $page): ?>
        <strong>[<?= $i ?>]</strong>
      <?php else: ?>
        <a href="my_reviews.php?page=<?= $i ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>
</body>
</html>
