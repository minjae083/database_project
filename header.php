<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
?>

<?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
  <script>
    alert("회원가입이 완료되었습니다.");
    window.location.href = 'main.php';
  </script>
<?php endif; ?>

<div class="header">
  <div class="logo">
    <a href="main.php" style="color:white; text-decoration:none; font-size:24px;">MyCinema</a>
  </div>

  <div class="search-bar">
    <input type="text" id="searchInput" placeholder="영화 제목 입력" />
    <button onclick="search()">검색</button>
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

<script>
document.addEventListener("DOMContentLoaded", function () {
  const loginBtn = document.getElementById('loginBtn');
  const loginModal = document.getElementById('loginModal');
  const closeLoginModalBtn = document.getElementById('closeLoginModal');

  const signupBtn = document.getElementById('signupBtn');
  const signupModal = document.getElementById('signupModal');
  const closeSignupModalBtn = document.getElementById('closeSignupModal');

  if (loginBtn) {
    loginBtn.addEventListener('click', () => {
      loginModal.classList.add('active');
      signupModal.classList.remove('active');
    });
  }
  closeLoginModalBtn?.addEventListener('click', () => {
    loginModal.classList.remove('active');
  });
  window.addEventListener('click', (e) => {
    if (e.target === loginModal) {
      loginModal.classList.remove('active');
    }
  });

  if (signupBtn) {
    signupBtn.addEventListener('click', () => {
      signupModal.classList.add('active');
      loginModal.classList.remove('active');
    });
  }
  closeSignupModalBtn?.addEventListener('click', () => {
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
});
</script>