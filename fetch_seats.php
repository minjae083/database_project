<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conn.php';

header('Content-Type: application/json; charset=utf-8');

$screening_id = isset($_GET['screening_id']) ? intval($_GET['screening_id']) : 0;
if ($screening_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid screening_id']);
    exit;
}

$screen_id = isset($_GET['screen_id']) ? intval($_GET['screen_id']) : 0;
if ($screen_id <= 0) {
    $stmt = oci_parse($conn, "SELECT screen_id FROM Screenings WHERE screening_id = :sid");
    oci_bind_by_name($stmt, ":sid", $screening_id);
    oci_execute($stmt);
    if ($row = oci_fetch_assoc($stmt)) {
        $screen_id = intval($row['SCREEN_ID']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'screen_id not found from screening']);
        exit;
    }
    oci_free_statement($stmt);
}

$sql = "
SELECT s.seat_id, s.seat_number, s.is_active,
  CASE 
    WHEN EXISTS (
      SELECT 1
      FROM ReservedSeats rs
      JOIN Reservations r ON rs.reservation_id = r.reservation_id
      WHERE rs.seat_id = s.seat_id
        AND r.screening_id = :screening_id
        AND r.cancel_status = 'N'
    ) THEN 1
    ELSE 0
  END AS is_reserved
FROM Seats s
WHERE s.screen_id = :screen_id
ORDER BY 
  REGEXP_SUBSTR(s.seat_number, '^[A-Z]'),
  TO_NUMBER(REGEXP_SUBSTR(s.seat_number, '[0-9]+'))
";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":screening_id", $screening_id);
oci_bind_by_name($stid, ":screen_id", $screen_id);
if (!oci_execute($stid)) {
    $e = oci_error($stid);
    http_response_code(500);
    echo json_encode([
        'error' => 'DB query failed',
        'message' => $e['message'],
        'query' => $sql
    ]);
    exit;
}

$seats = [];
while ($row = oci_fetch_assoc($stid)) {
    if ($row['IS_ACTIVE'] !== 'Y') continue;

    $seats[] = [
        'seat_id' => $row['SEAT_ID'],
        'seat_number' => $row['SEAT_NUMBER'],
        'is_reserved' => (bool)$row['IS_RESERVED'],
        'is_active' => true
    ];
}

oci_free_statement($stid);
oci_close($conn);

echo json_encode(['seats' => $seats], JSON_UNESCAPED_UNICODE);
