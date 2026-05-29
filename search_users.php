<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['type'], $_GET['query'])) {
    echo json_encode(['error' => '잘못된 요청입니다.']);
    exit;
}

$allowed_types = ['user_id', 'email'];
$type = in_array($_GET['type'], $allowed_types) ? $_GET['type'] : 'user_id';
$query = trim($_GET['query']);

if ($query === '') {
    echo json_encode(['error' => '검색어가 비어 있습니다.']);
    exit;
}

try {
    $sql = "SELECT user_id, name, email, phone FROM Users WHERE $type = :query";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':query', $query);
    oci_execute($stmt);

    if ($row = oci_fetch_assoc($stmt)) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => '해당 유저를 찾을 수 없습니다.']);
    }

    oci_free_statement($stmt);
    oci_close($conn);

} catch (Exception $e) {
    echo json_encode(['error' => '서버 오류: ' . $e->getMessage()]);
}
