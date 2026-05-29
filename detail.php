<?php
session_start();
require_once 'conn.php';

if (!isset($_GET['title'])) {
    echo "영화 제목이 지정되지 않았습니다.";
    exit;
}

$title = trim($_GET['title']);
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? ''; 

$query = "SELECT movie_id, poster_path, teaser_url, genre, duration FROM Movies WHERE TRIM(LOWER(title)) = LOWER(:title)";
$stmt = oci_parse($conn, $query);
oci_bind_by_name($stmt, ":title", $title);
oci_execute($stmt);

if ($row = oci_fetch_assoc($stmt)) {
    $movie_id = $row['MOVIE_ID'];
    $poster_path = $row['POSTER_PATH'];
    $teaser_url = $row['TEASER_URL'];
    $genre = $row['GENRE'];
    $duration_raw = $row['DURATION'];

    if (preg_match('/\+?(\d+) (\d+):(\d+):(\d+).*/', $duration_raw, $matches)) {
        $hours = (int)$matches[2] + ((int)$matches[1] * 24);
        $minutes = (int)$matches[3];
        $formatted_duration = '';
        if ($hours > 0) $formatted_duration .= "{$hours}시간 ";
        $formatted_duration .= "{$minutes}분";
    } else {
        $formatted_duration = $duration_raw;
    }
} else {
    echo "해당 영화 정보를 찾을 수 없습니다.";
    exit;
}
oci_free_statement($stmt);

$sql = "SELECT ROUND(AVG(rating), 1) AS avg_rating FROM Reviews WHERE movie_id = :mid";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":mid", $movie_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$avg_rating = $row['AVG_RATING'] ?? '평점 없음';
oci_free_statement($stmt);

$sql = "SELECT 
    SUM(CASE WHEN is_like = 1 THEN 1 ELSE 0 END) AS likes,
    SUM(CASE WHEN is_like = 0 THEN 1 ELSE 0 END) AS dislikes
    FROM MovieLikes WHERE movie_id = :mid";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":mid", $movie_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$likes = $row['LIKES'] ?? 0;
$dislikes = $row['DISLIKES'] ?? 0;
oci_free_statement($stmt);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?> - 상세 정보</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color:#d4d4d4;
      color: white;
    }

    .header {
      background-color:rgb(71, 71, 71);
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
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
    .mypage-wrapper {
      position: relative;
      display: inline-block;
    }

    #mypageBtn {
      background-color: #4444aa;
      border: none;
      border-radius: 5px;
      padding: 8px 14px;
      color: white;
      cursor: pointer;
      font-weight: bold;
      font-size: 16px;
      user-select: none;
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

    .mypage-popup {
      display: none;
      position: absolute;
      top: 110%;
      right: 0;
      background: #222255;
      border-radius: 6px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.6);
      padding: 8px 0;
      min-width: 140px;
      z-index: 1000;
    }

    .mypage-popup.active {
      display: block;
    }

    .mypage-popup button {
      background: none;
      border: none;
      color: white;
      width: 100%;
      padding: 10px 20px;
      text-align: left;
      cursor: pointer;
      font-size: 15px;
      transition: background 0.2s;
    }

    .mypage-popup button:hover {
      background: #4444aa;
    }

    .btn {
      background-color: #2a2a2a;
      color: white;
      border: none;
      border-radius: 20px;
      padding: 10px 20px;
      cursor: pointer;
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
    .container {
      max-width: 1100px;
      margin: 0 auto;
      padding: 40px 20px;
      display: flex;
      gap: 40px;
      justify-content: center;
    }

    .side-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      order: 1;
    }

    .teaser {
      flex: 1;
      max-width: 720px;
      order: 2;
    }

    .teaser iframe {
      width: 100%;
      aspect-ratio: 16 / 9;
      border: none;
      border-radius: 10px;
      max-width: 720px;
    }

    .poster {
      width: 180px;
      height: 270px;
      object-fit: cover;
      background: white;
      margin-bottom: 20px;
    }

    .movie-title {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 10px;
    }

    .movie-info {
      font-size: 16px;
      margin-bottom: 20px;
    }

    .btn-group {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .review-section {
      max-width: 1100px;
      margin: 50px auto 20px;
      padding: 30px 20px 20px;
      background-color: rgb(90, 90, 90);
      border-radius: 10px;
      box-sizing: border-box;
    }

    .review-section h2 {
      margin-top: 0;
      margin-bottom: 20px;
    }

    .review-form textarea {
      width: 95%;
      padding: 12px;
      border: none;
      border-radius: 10px;
      background: #aaa;
      color: black;
      font-size: 14px;
      margin-bottom: 10px;
      resize: vertical;
    }

    .star-rating {
      direction: rtl;
      font-size: 2rem;
      unicode-bidi: bidi-override;
      display: inline-flex;
      gap: 5px;
    }

    .star-rating input {
      display: none;
    }

    .star-rating label {
      color: #ccc;
      cursor: pointer;
      transition: color 0.2s;
    }

    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
      color: gold;
    }
  </style>
</head>
<body>

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

<div class="container">
  <div class="side-info">
    <img src="<?= htmlspecialchars($poster_path) ?>" alt="포스터" class="poster">
    <div class="movie-title"><?= htmlspecialchars($title) ?></div>
    <div class="movie-info">
      <p><strong>장르:</strong> <?= htmlspecialchars($genre) ?></p>
      <p><strong>러닝타임:</strong> <?= htmlspecialchars($formatted_duration) ?></p>
      <p><strong>평점:</strong> <?= htmlspecialchars($avg_rating) ?> / 5</p>
      <p><strong>👍 좋아요:</strong> <?= $likes ?> &nbsp;&nbsp; 👎 싫어요: <?= $dislikes ?></p>
    </div>
    <div class="btn-group">
      <form method="GET" action="select_screening.php">
        <input type="hidden" name="movie_id" value="<?= $movie_id ?>">
        <button type="submit" class="btn">예매하기</button>
      </form>
      <?php if ($is_logged_in): ?>
        <form method="POST" action="like_movie.php">
          <input type="hidden" name="movie_id" value="<?= $movie_id ?>">
          <input type="hidden" name="title" value="<?= htmlspecialchars($title) ?>">
          <button type="submit" name="is_like" value="1" class="btn">좋아요</button>
          <button type="submit" name="is_like" value="0" class="btn">싫어요</button>
        </form>
      <?php else: ?>
        <p>로그인 후 좋아요/싫어요 가능</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="teaser">
    <?php if (!empty($teaser_url)): ?>
      <iframe src="<?= htmlspecialchars($teaser_url) ?>" allowfullscreen></iframe>
    <?php else: ?>
      <div style="width: 100%; height: 315px; background: #333; display: flex; align-items: center; justify-content: center;">
        <span>예고편 없음</span>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="review-section">
  <h2>리뷰</h2>

  <div style="margin-bottom: 20px;">
    <span id="sort-high" style="cursor:pointer; margin-right: 15px; text-decoration: underline;">별점 높은 순</span>
    <span id="sort-low" style="cursor:pointer; text-decoration: underline;">별점 낮은 순</span>
  </div>

  <?php if ($is_logged_in): ?>
    <form method="POST" action="submit_review.php" class="review-form" style="margin-bottom: 30px;">
      <input type="hidden" name="movie_id" value="<?= $movie_id ?>">
      <div class="star-rating" style="margin-bottom: 10px;">
        <input type="radio" name="rating" id="star5" value="5"><label for="star5">★</label>
        <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
        <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
        <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
        <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
      </div>
      <textarea name="review_content" rows="4" placeholder="리뷰를 입력해주세요."></textarea>
      <br><br>
      <button type="submit" class="btn">리뷰 등록</button>
    </form>
  <?php else: ?>
    <p>리뷰 작성을 위해 로그인하세요.</p>
  <?php endif; ?>

  <div id="review-list"></div>

  <script>
    const movieId = <?= $movie_id ?>;

    function loadReviews(order = 'desc') {
      fetch(`load_reviews.php?movie_id=${movieId}&sort=${order}`)
        .then(res => res.text())
        .then(html => document.getElementById('review-list').innerHTML = html);
    }

    document.getElementById('sort-high').addEventListener('click', () => loadReviews('desc'));
    document.getElementById('sort-low').addEventListener('click', () => loadReviews('asc'));

    loadReviews('desc');
  </script>
</div>

  <script>
    function goToSearch() {
      const input = document.getElementById('searchInput').value.trim();
      window.location.href = `main.php?search=${encodeURIComponent(input)}`;
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