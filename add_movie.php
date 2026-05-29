<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php"); 
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $teaser_url = $_POST['teaser_url'] ?? '';

    $poster_path = '';
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
    INSERT INTO Movies (movie_id, title, duration, genre, poster_path, teaser_url)
    VALUES (Movies_seq.NEXTVAL, :title, INTERVAL '" . intval($duration) . "' MINUTE, :genre, :poster_path, :teaser_url)
";

    
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':title', $title);
oci_bind_by_name($stmt, ':genre', $genre);
oci_bind_by_name($stmt, ':poster_path', $poster_path);
oci_bind_by_name($stmt, ':teaser_url', $teaser_url);

    $result = oci_execute($stmt);
    oci_free_statement($stmt);

    if ($result) {
        echo "<p>영화가 성공적으로 추가되었습니다.</p>";
    } else {
        $e = oci_error($stmt);
        echo "<p>오류 발생: {$e['message']}</p>";
    }

    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>영화 추가</title>
</head>
<body>
    <h1>영화 추가</h1>
    <form method="POST" action="add_movie.php" enctype="multipart/form-data">
        <label for="title">영화 제목:</label>
        <input type="text" id="title" name="title" required><br><br>

        <label for="duration">상영 시간 (분):</label>
        <input type="number" id="duration" name="duration" required><br><br>

        <label for="genre">장르:</label>
        <select id="genre" name="genre" required>
            <option value="">-- 선택하세요 --</option>
            <option value="SF">SF</option>
            <option value="드라마">드라마</option>
            <option value="코미디">코미디</option>
            <option value="액션">액션</option>
            <option value="스릴러">스릴러</option>
            <option value="범죄">범죄</option>
            <option value="로맨스">로맨스</option>
            <option value="판타지">판타지</option>
        </select><br><br>

        <label for="poster">포스터 업로드:</label>
        <input type="file" id="poster" name="poster"><br><br>

        <label for="teaser_url">티저 URL:</label>
        <input type="url" id="teaser_url" name="teaser_url"><br><br>

        <button type="submit">영화 추가</button>
    </form>
    <p><a href="admin_index.php">← 관리자 대시보드로</a></p>
</body>
</html>
