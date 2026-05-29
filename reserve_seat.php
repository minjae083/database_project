<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: main.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$screening_id = $_POST['screening_id'] ?? null;
$seat_ids = $_POST['seat_ids'] ?? [];

if (!$screening_id || empty($seat_ids)) {
    die("좌석을 선택해주세요.");
}

require_once 'conn.php';

try {
$info_sql = "
    SELECT m.title AS movie_title,
           s.name AS screen_name,
           TO_CHAR(sc.start_time, 'YYYY-MM-DD HH24:MI:SS') AS start_time
    FROM Screenings sc
    JOIN Movies m ON sc.movie_id = m.movie_id
    JOIN Screens s ON sc.screen_id = s.screen_id
    WHERE sc.screening_id = :screening_id
";

    $info_stmt = oci_parse($conn, $info_sql);
    oci_bind_by_name($info_stmt, ":screening_id", $screening_id);
    oci_execute($info_stmt);
    $info = oci_fetch_assoc($info_stmt);
    oci_free_statement($info_stmt);

    if (!$info) {
        throw new Exception("상영 정보를 찾을 수 없습니다.");
    }

$sql_reservation = "
    INSERT INTO Reservations (
        reservation_id, user_id, screening_id,
        movie_title, screen_name, start_time_snapshot
    )
    VALUES (
        Reservations_seq.NEXTVAL, :user_id, :screening_id,
        :movie_title, :screen_name, TO_DATE(:start_time_snapshot, 'YYYY-MM-DD HH24:MI:SS')
    )
    RETURNING reservation_id INTO :res_id
";
    $stid = oci_parse($conn, $sql_reservation);
    oci_bind_by_name($stid, ":user_id", $user_id);
    oci_bind_by_name($stid, ":screening_id", $screening_id);
    oci_bind_by_name($stid, ":movie_title", $info['MOVIE_TITLE']);
    oci_bind_by_name($stid, ":screen_name", $info['SCREEN_NAME']);
    oci_bind_by_name($stid, ":start_time_snapshot", $info['START_TIME']);
    $reservation_id = 0;
    oci_bind_by_name($stid, ":res_id", $reservation_id, -1, SQLT_INT);

    if (!oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
        oci_rollback($conn);
        throw new Exception("예매 실패: 예약 정보 저장 오류");
    }
    oci_free_statement($stid);

    foreach ($seat_ids as $seat_id) {
        $check_sql = "
            SELECT rs.seat_id
            FROM ReservedSeats rs
            JOIN Reservations r ON rs.reservation_id = r.reservation_id
            WHERE r.screening_id = :screening_id AND rs.seat_id = :seat_id AND r.cancel_status = 'N'
            FOR UPDATE NOWAIT
        ";
        $check_stid = oci_parse($conn, $check_sql);
        oci_bind_by_name($check_stid, ":screening_id", $screening_id);
        oci_bind_by_name($check_stid, ":seat_id", $seat_id);
        if (!oci_execute($check_stid, OCI_NO_AUTO_COMMIT)) {
            throw new Exception("동시 예약 충돌 발생");
        }
        $row = oci_fetch_assoc($check_stid);
        oci_free_statement($check_stid);
        if ($row) throw new Exception("이미 예약된 좌석입니다.");

        $insert_sql = "INSERT INTO ReservedSeats (reservation_id, seat_id) VALUES (:reservation_id, :seat_id)";
        $insert_stid = oci_parse($conn, $insert_sql);
        oci_bind_by_name($insert_stid, ":reservation_id", $reservation_id);
        oci_bind_by_name($insert_stid, ":seat_id", $seat_id);
        if (!oci_execute($insert_stid, OCI_NO_AUTO_COMMIT)) {
            throw new Exception("좌석 예약 중 오류 발생");
        }
        oci_free_statement($insert_stid);
    }

    oci_commit($conn);
    oci_close($conn);
    header("Location: reservation_complete.php?reservation_id=" . urlencode($reservation_id));
    exit;

} catch (Exception $e) {
    oci_rollback($conn);
    oci_close($conn);
    echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>예매 실패</title></head>
    <body style='background-color:#0d0c3b; color:white; font-family:sans-serif; text-align:center; padding:50px'>
    <h2>❌ 예매 실패</h2>
    <p>" . htmlspecialchars($e->getMessage()) . "</p>
    <a href='javascript:history.back()' style='color:white; text-decoration:underline;'>← 다시 시도</a>
    </body></html>";
}
?>