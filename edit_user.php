<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['is_admin']) !== 1) {
    header("Location: main.php");
    exit;
}

if (!isset($_GET['user_id'])) {
    echo "유저 ID가 없습니다.";
    exit;
}

$user_id = $_GET['user_id'];
$name = $email = $phone = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($name === "" || $email === "" || $phone === "") {
        $error = "모든 필드를 입력해주세요.";
    } else {
        $sql = "SELECT COUNT(*) AS CNT FROM Users 
                WHERE (email = :email OR phone = :phone) AND user_id != :user_id";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":email", $email);
        oci_bind_by_name($stmt, ":phone", $phone);
        oci_bind_by_name($stmt, ":user_id", $user_id);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($row['CNT'] > 0) {
            $error = "중복된 이메일 또는 전화번호입니다.";
        } else {
            $sql = "UPDATE Users SET name = :name, email = :email, phone = :phone WHERE user_id = :user_id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":name", $name);
            oci_bind_by_name($stmt, ":email", $email);
            oci_bind_by_name($stmt, ":phone", $phone);
            oci_bind_by_name($stmt, ":user_id", $user_id);

            if (oci_execute($stmt)) {
                oci_free_statement($stmt);
                oci_close($conn);
                header("Location: manage_users.php");
                exit;
            } else {
                $error = "업데이트에 실패했습니다.";
            }
        }
    }
} else {
    $sql = "SELECT name, email, phone FROM Users WHERE user_id = :user_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":user_id", $user_id);
    oci_execute($stmt);

    if ($row = oci_fetch_assoc($stmt)) {
        $name = $row['NAME'];
        $email = $row['EMAIL'];
        $phone = $row['PHONE'];
    } else {
        echo "유저를 찾을 수 없습니다.";
        exit;
    }
    oci_free_statement($stmt);
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>유저 수정</title>
</head>
<body>
  <h1>유저 수정</h1>
  <form method="POST">
    <p><strong>유저 ID:</strong> <?= htmlspecialchars($user_id) ?></p>
    <p>
      이름: <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
    </p>
    <p>
      이메일: <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
    </p>
    <p>
      전화번호: <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
    </p>
    <?php if ($error): ?>
      <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <p>
      <button type="submit">저장</button>
      <a href="manage_users.php">취소</a>
    </p>
  </form>
</body>
</html>
