<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
require_once 'conn.php';

$genres = ['전체', 'SF', '드라마', '코미디', '액션', '스릴러', '범죄', '로맨스', '판타지'];
$selected_genre = $_GET['genre'] ?? '전체';

$search = $_GET['search'] ?? '';
$search = trim($search);

if (!empty($search)) {
  $sql = "
    SELECT title, poster_path 
    FROM Movies 
    WHERE LOWER(title) LIKE '%' || LOWER(:search) || '%'
       OR LOWER(genre) LIKE '%' || LOWER(:search) || '%'
    ORDER BY movie_id
  ";
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ":search", $search);
} else {
  $sql = "SELECT title, poster_path FROM Movies ORDER BY movie_id";
  $stid = oci_parse($conn, $sql);
}
oci_execute($stid);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <title>메인 페이지</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background:#d4d4d4;
      color: white;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
      background:rgb(71, 71, 71);
    }
    .logo-img {
      height: 40px;
      vertical-align: middle;
    }
    .search-bar input {
      padding: 8px;
      width: 250px;
      border-radius: 5px;
      border: none;
    }
    .search-bar button {
      padding: 8px 12px;
      margin-left: 10px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
    }
    .auth-buttons {
      position: relative;
      display: flex;
      align-items: center;
    }
    .auth-buttons button {
      margin-left: 10px;
      padding: 8px 12px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
      background: #4444aa;
      color: white;
    }
    .username-display {
      margin-right: 15px;
      font-weight: bold;
      font-size: 16px;
    }
    .mypage-wrapper {
      position: relative;
      display: inline-block;
    }
    .mypage-popup {
      display: none;
      position: absolute;
      top: 110%;
      right: 0;
      background: #222255;
      border-radius: 6px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.6);
      padding: 8px 12px;
      white-space: nowrap;
      z-index: 100;
    }
    .mypage-popup.active {
      display: block;
    }
    .mypage-popup button {
      background: none;
      border: none;
      color: white;
      padding: 6px 10px;
      cursor: pointer;
      font-size: 14px;
      display: block;
      width: 100%;
      text-align: left;
    }
    .mypage-popup button:hover {
      background: #4444aa;
    }
    .filter-bar {
      padding: 20px;
      background:rgb(199, 199, 199);
    }
    .filter-bar select {
      padding: 6px 10px;
      border-radius: 5px;
      font-size: 16px;
    }
    .grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      padding: 20px;
      justify-content: center;
      box-sizing: border-box;
    }
    .card {
      width: 210px;
      height: 350px;
      background: white;
      color: black;
      border-radius: 10px;
      overflow: hidden;
      text-align: center;
      box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
      text-decoration: none;
      display: flex;
      flex-direction: column;
    }
    .card img {
      width: 100%;
      height: 310px;
      object-fit: cover;
    }
    .card p {
      height: 40px;
      margin: 0;
      font-weight: bold;
      font-size: 14px;
      line-height: 40px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .hidden {
      display: none;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.7);
      justify-content: center;
      align-items: center;
    }
    .modal.active {
      display: flex;
    }
    .modal-content {
      position: relative;
      background: #222255;
      padding: 20px 30px;
      border-radius: 10px;
      width: 320px;
      color: white;
      box-shadow: 0 0 10px #000;
      display: flex;
      flex-direction: column;
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }
    .modal-header h2 {
      margin: 0;
      font-size: 24px;
    }
    .modal-content label {
      margin-top: 10px;
    }

    .modal-content input[type="text"],
    .modal-content input[type="email"],
    .modal-content input[type="tel"],
    .modal-content input[type="password"] {
      width: 100%;
      padding: 10px 12px;
      margin: 8px 0;
      border-radius: 5px;
      border: none;
      font-size: 14px;
      box-sizing: border-box;
    }

    #loginModal button[type="submit"] {
      width: 100%;
      background: #4444aa;
      border: none;
      color: white;
      padding: 10px;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      margin-top: 10px;
    }
    #loginModal button[type="submit"]:hover {
      background: #333388;
    }
    /* 회원가입 모달 버튼 */
    #signupModal .submit button {
      margin-top: 15px;
      padding: 10px 0;
      width: 100%;
      background-color: #28a745;
      border: none;
      border-radius: 5px;
      font-size: 18px;
      color: white;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    #signupModal .submit button:hover {
      background-color: #218838;
      color: white;
      transform: none;
    }

    .modal-content .close-btn {
      background: none;
      border: none;
      font-size: 28px;
      color: white;
      cursor: pointer;
      padding: 0;
      line-height: 1;
    }
  </style>
</head>
<body>
<body>
 <?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
<script>
  alert("회원가입이 완료되었습니다.");
  window.location.href = 'main.php';
</script>
<?php endif; ?>

  <div class="header">
    <a href="main.php"><img src="images/logo.png" alt="로고" class="logo-img"></a>
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="영화 제목 입력" />
      <button onclick="goToSearch()">검색</button>
    </div>
    <div class="auth-buttons">
      <?php if ($is_logged_in): ?>
        <span class="username-display"><?= htmlspecialchars($user_name) ?> 님</span>
        <div class="mypage-wrapper">
          <button id="mypageBtn">마이페이지 ▼</button>
          <div class="mypage-popup" id="mypagePopup">
            <?php if ($_SESSION['is_admin'] == 1): ?>
              <button onclick="location.href='admin_index.php'">관리자 페이지</button>
            <?php endif; ?>
            <button onclick="location.href='mypage.php'">예매 내역</button>
            <button onclick="location.href='my_reviews.php'">리뷰 조회</button>
            <button onclick="location.href='logout.php'">로그아웃</button>
          </div>
        </div>
      <?php else: ?>
        <button id="loginBtn">로그인</button>
        <button id="signupBtn">회원 가입</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="filter-bar">
    <form method="GET" action="main.php">
      <label for="genre">장르 선택:</label>
      <select name="genre" id="genre" onchange="this.form.submit()">
        <?php foreach ($genres as $g): ?>
          <option value="<?= $g ?>" <?= $g === $selected_genre ? 'selected' : '' ?>><?= $g ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <div class="grid">
    <?php while ($row = oci_fetch_assoc($stid)): ?>
      <?php
        $title = htmlspecialchars($row['TITLE']);
        $poster = htmlspecialchars($row['POSTER_PATH']);
        $encoded_title = urlencode($title);
      ?>
      <a href="detail.php?title=<?= $encoded_title ?>" class="card" data-title="<?= $title ?>">
        <img src="<?= $poster ?>" alt="<?= $title ?>" />
        <p><?= $title ?></p>
      </a>
    <?php endwhile; ?>
  </div>

  <?php
  oci_free_statement($stid);
  oci_close($conn);
  ?>

  <div id="loginModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>로그인</h2>
        <button class="close-btn" id="closeLoginModal">&times;</button>
      </div>
      <form method="post" action="login_process.php">
        <label for="userid">아이디</label>
        <input type="text" id="userid" name="user_id" required />
        <label for="password">비밀번호</label>
        <input type="password" id="password" name="password" required />
        <button type="submit">로그인</button>
      </form>
    </div>
  </div>

  <div id="signupModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>회원 가입</h2>
        <button class="close-btn" id="closeSignupModal">&times;</button>
      </div>
      <form action="signup_process.php" method="post">
        <input type="text" name="user_id" placeholder="아이디" required />
        <input type="text" name="name" placeholder="이름" required />
        <input type="email" name="email" placeholder="이메일" required />
        <input type="tel" name="phone" placeholder="휴대전화" required />
        <input type="password" name="password" placeholder="비밀번호" required />
        <input type="password" name="confirm_password" placeholder="비밀번호 확인" required />
        <div class="submit">
          <button type="submit">회원가입</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function goToSearch() {
      const input = document.getElementById('searchInput').value.trim();
      const url = `main.php?search=${encodeURIComponent(input)}`;
      window.location.href = url;
    }
    const loginBtn = document.getElementById('loginBtn');
    const loginModal = document.getElementById('loginModal');
    const closeLoginModalBtn = document.getElementById('closeLoginModal');
    if (loginBtn) {
      loginBtn.addEventListener('click', () => {
        loginModal.classList.add('active');

        signupModal.classList.remove('active');
      });
    }
    closeLoginModalBtn.addEventListener('click', () => {
      loginModal.classList.remove('active');
    });
    window.addEventListener('click', (e) => {
      if (e.target === loginModal) {
        loginModal.classList.remove('active');
      }
    });

    const signupBtn = document.getElementById('signupBtn');
    const signupModal = document.getElementById('signupModal');
    const closeSignupModalBtn = document.getElementById('closeSignupModal');
    if (signupBtn) {
      signupBtn.addEventListener('click', () => {
        signupModal.classList.add('active');
        loginModal.classList.remove('active');
      });
    }
    closeSignupModalBtn.addEventListener('click', () => {
      signupModal.classList.remove('active');
    });
    window.addEventListener('click', (e) => {
      if (e.target === signupModal) {
        signupModal.classList.remove('active');
      }
    });

    const mypageBtn = document.getElementById('mypageBtn');
    const mypagePopup = document.getElementById('mypagePopup');
    if (mypageBtn) {
      mypageBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        mypagePopup.classList.toggle('active');
      });
      window.addEventListener('click', () => {
        mypagePopup.classList.remove('active');
      });
      mypagePopup.addEventListener('click', e => e.stopPropagation());
    }
  </script>
</body>
</html>
