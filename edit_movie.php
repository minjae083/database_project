<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['movie_id'])) {
    $movie_id = intval($_GET['movie_id']);

    $sql = "SELECT * FROM Movies WHERE movie_id = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $movie_id);
    oci_execute($stmt);

    $movie = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$movie) {
        echo "<p>해당 영화가 존재하지 않습니다.</p>";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = $_POST['movie_id'];
    $title = $_POST['title'];
    $duration = $_POST['duration'];
    $genre = $_POST['genre'];
    $teaser_url = $_POST['teaser_url'];

    $poster_path = $_POST['existing_poster'] ?? '';
    if (!empty($_FILES['poster']['name'])) {
        $upload_dir = 'images/';
        $file_name = basename($_FILES['poster']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['poster']['tmp_name'], $target_path)) {
            $poster_path = $target_path;
        } else {
            echo "<p>포스터 업로드 실패</p>";
        }
    }

    $sql = "
    UPDATE Movies
    SET title = :title,
        duration = INTERVAL '" . intval($duration) . "' MINUTE,
        genre = :genre,
        poster_path = :poster_path,
        teaser_url = :teaser_url
    WHERE movie_id = :id
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':title', $title);
oci_bind_by_name($stmt, ':genre', $genre);
oci_bind_by_name($stmt, ':poster_path', $poster_path);
oci_bind_by_name($stmt, ':teaser_url', $teaser_url);
oci_bind_by_name($stmt, ':id', $movie_id);

    if (oci_execute($stmt)) {
        echo "<p>영화 정보가 성공적으로 수정되었습니다.</p>";
    } else {
        $e = oci_error($stmt);
        echo "<p>오류 발생: {$e['message']}</p>";
    }
    oci_free_statement($stmt);
    oci_close($conn);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>영화 정보 수정</title>
</head>
<body>
    <h1>영화 정보 수정</h1>
    <form method="POST" action="edit_movie.php" enctype="multipart/form-data">
        <input type="hidden" name="movie_id" value="<?= htmlspecialchars($movie['MOVIE_ID']) ?>">
        <input type="hidden" name="existing_poster" value="<?= htmlspecialchars($movie['POSTER_PATH']) ?>">

        <label for="title">영화 제목:</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($movie['TITLE']) ?>" required><br><br>

        <label for="duration">상영 시간 (분):</label>
        <input type="number" id="duration" name="duration" value="<?= (int)$movie['DURATION'] ?>" required><br><br>

        <label for="genre">장르:</label>
        <select id="genre" name="genre" required>
            <?php
            $genres = ['SF', '드라마', '코미디', '액션', '스릴러', '범죄', '로맨스', '판타지'];
            foreach ($genres as $g) {
                $selected = ($g === $movie['GENRE']) ? 'selected' : '';
                echo "<option value=\"$g\" $selected>$g</option>";
            }
            ?>
        </select><br><br>

        <label for="poster">포스터 업로드 (선택):</label>
        <input type="file" id="poster" name="poster"><br>
        현재 포스터: <?= htmlspecialchars($movie['POSTER_PATH']) ?><br><br>

        <label for="teaser_url">티저 URL:</label>
        <input type="url" id="teaser_url" name="teaser_url" value="<?= htmlspecialchars($movie['TEASER_URL']) ?>"><br><br>

        <button type="submit">수정 완료</button>
    </form>
</body>
</html>
