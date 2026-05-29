<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'conn.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_GET)) {
    http_response_code(400);
    echo json_encode(['error' => 'No GET parameters received']);
    exit;
}

$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';

if ($movie_id <= 0 || !$selected_date) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid movie_id or date']);
    exit;
}

$sql = "
    SELECT 
        s.screening_id,
        s.screen_id,
        TO_CHAR(s.start_time, 'YYYY-MM-DD HH24:MI:SS') AS start_time,
        TO_CHAR(s.start_time, 'YYYY-MM-DD') AS screening_date,
        scr.name AS screen_name
    FROM Screenings s
    JOIN Screens scr ON s.screen_id = scr.screen_id
    WHERE s.movie_id = :movie_id"
    . ($selected_date !== '2000-01-01' ? " AND TRUNC(s.start_time) = TO_DATE(:selected_date, 'YYYY-MM-DD')" : "") . "
    ORDER BY s.start_time
";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":movie_id", $movie_id);
if ($selected_date !== '2000-01-01') {
    oci_bind_by_name($stid, ":selected_date", $selected_date);
}

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    http_response_code(500);
    echo json_encode(['error' => 'DB query failed', 'message' => $e['message']]);
    exit;
}

$result = [];
while ($row = oci_fetch_assoc($stid)) {
    $result[] = [
        'screening_id'   => $row['SCREENING_ID'],
        'screen_id'      => $row['SCREEN_ID'],
        'start_time'     => $row['START_TIME'],
        'screening_date' => $row['SCREENING_DATE'],
        'screen_name'    => $row['SCREEN_NAME']
    ];
}

oci_free_statement($stid);
oci_close($conn);

echo json_encode($result);
