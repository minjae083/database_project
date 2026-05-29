<?php
$db_user = "dbuser222166";  
$db_pass = "ce1234";   
$db_host = "earth.gwangju.ac.kr/orcl";    

$conn = oci_connect($db_user, $db_pass, $db_host, "AL32UTF8");

if (!$conn) {
    $e = oci_error();
    die("Oracle 연결 실패: " . $e['message']);
}
?>
